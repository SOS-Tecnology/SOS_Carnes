<?php

namespace App\Controllers;

use Medoo\Medoo;

/**
 * Prioridad de Alistamiento — alertas de urgencia sobre pedidos PV
 * que están en proceso de alistamiento (Planilla de Pedidos).
 *
 * Tabla propia: prioridadmov (InnoDB/utf8mb4). estado: 'A' activa, 'C' cerrada.
 * Regla: máximo una alerta ACTIVA por documento (reforzada también por
 * índice único a nivel de BD, ver database/prioridadmov_create.sql).
 */
class PrioridadAlistamientoController
{
    public function __construct(private Medoo $db) {}

    private function nombreUsuario(): string
    {
        $n = trim($_SESSION['user']['name'] ?? 'Usuario');
        return substr($n, 0, 50);
    }

    // ─────────────────────────────────────────────────────────────────────
    // GET /prioridad-alistamiento
    // ─────────────────────────────────────────────────────────────────────
    public function index($request, $response): mixed
    {
        $qp     = $request->getQueryParams();
        $estado = $qp['estado'] ?? 'A'; // A=Activas (por defecto), C=Cerradas, '' =Todas
        if (!in_array($estado, ['A', 'C', ''], true)) {
            $estado = 'A';
        }

        $whereEstado = $estado !== '' ? 'AND p.estado = :estado' : '';

        $stmt = $this->db->pdo->prepare("
            SELECT p.id, p.tm, TRIM(p.prefijo) AS prefijo, TRIM(p.documento) AS documento,
                   TRIM(p.canal) AS canal, p.comentario, p.estado,
                   p.usuariocrea,
                   DATE_FORMAT(p.fechacrea, '%d/%m/%Y') AS fecha_fmt,
                   p.horacrea,
                   TRIM(gt.nombretc)   AS nombrecanal,
                   TRIM(g.nombrecli)   AS nomcli
            FROM   prioridadmov p
            LEFT   JOIN getipoter  gt ON TRIM(gt.codigotc) = TRIM(p.canal)
            LEFT   JOIN cabezamov  c  ON TRIM(c.tm) = TRIM(p.tm)
                                      AND TRIM(c.prefijo) = TRIM(p.prefijo)
                                      AND TRIM(c.documento) = TRIM(p.documento)
            LEFT   JOIN geclientes g  ON c.codcp = g.codcli
            WHERE  1=1 {$whereEstado}
            ORDER  BY p.fechacrea DESC, p.horacrea DESC, p.id DESC
        ");
        $params = $estado !== '' ? [':estado' => $estado] : [];
        $stmt->execute($params);
        $solicitudes = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return renderView(
            $response,
            __DIR__ . '/../Views/prioridad-alistamiento/index.php',
            'Prioridad de Alistamiento',
            ['solicitudes' => $solicitudes, 'estadoFiltro' => $estado]
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // GET /prioridad-alistamiento/crear
    // ─────────────────────────────────────────────────────────────────────
    public function crear($request, $response): mixed
    {
        $canales = $this->db->pdo->query("
            SELECT TRIM(codigotc) AS codigotc, TRIM(nombretc) AS nombretc
            FROM   getipoter
            WHERE  TRIM(cliprov) = 'C'
            ORDER  BY nombretc
        ")->fetchAll(\PDO::FETCH_ASSOC);

        return renderView(
            $response,
            __DIR__ . '/../Views/prioridad-alistamiento/crear.php',
            'Nueva Solicitud de Prioridad',
            ['canales' => $canales]
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // GET /api/prioridad-alistamiento/pedidos?canal=XX
    // Pedidos PV activos en alistamiento (estado='C' AND estadorm<>'A')
    // para el canal indicado — usado por la pantalla "crear" vía fetch.
    // ─────────────────────────────────────────────────────────────────────
    public function apiPedidosPorCanal($request, $response): mixed
    {
        $canal = trim($request->getQueryParams()['canal'] ?? '');
        $data  = [];

        if ($canal !== '') {
            $stmt = $this->db->pdo->prepare("
                SELECT TRIM(c.documento) AS documento,
                       TRIM(c.prefijo)   AS prefijo,
                       TRIM(g.nombrecli) AS nomcli,
                       DATE_FORMAT(c.fechent, '%d/%m/%Y') AS fecentrega_fmt,
                       CASE WHEN pm.id IS NOT NULL THEN 1 ELSE 0 END AS ya_tiene_prioridad
                FROM   cabezamov c
                INNER  JOIN geclientes g ON c.codcp = g.codcli
                LEFT   JOIN prioridadmov pm
                       ON  TRIM(pm.tm) = TRIM(c.tm)
                       AND TRIM(pm.prefijo) = TRIM(c.prefijo)
                       AND TRIM(pm.documento) = TRIM(c.documento)
                       AND pm.estado = 'A'
                WHERE  TRIM(c.tm) = 'PV'
                  AND  c.estado IN ('C')
                  AND  TRIM(c.estadorm) <> 'A'
                  AND  TRIM(g.codtipocli) = :canal
                ORDER  BY c.fechent ASC
            ");
            $stmt->execute([':canal' => $canal]);
            $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // ─────────────────────────────────────────────────────────────────────
    // POST /prioridad-alistamiento/guardar
    // ─────────────────────────────────────────────────────────────────────
    public function guardar($request, $response): mixed
    {
        $body       = $request->getParsedBody();
        $canal      = trim($body['canal']      ?? '');
        $prefijo    = trim($body['prefijo']    ?? '');
        $documento  = trim($body['documento']  ?? '');
        $comentario = trim($body['comentario'] ?? '');

        $errores = [];
        if ($canal === '')      $errores[] = 'Seleccione un canal.';
        if ($documento === '')  $errores[] = 'Seleccione un pedido (documento PV).';
        if ($comentario === '') $errores[] = 'Escriba un comentario de la urgencia.';
        if (strlen($comentario) > 500) $errores[] = 'El comentario supera los 500 caracteres.';

        if (empty($errores)) {
            // Confirmar que el documento sigue activo en alistamiento
            $chk = $this->db->pdo->prepare("
                SELECT 1 FROM cabezamov
                WHERE  TRIM(tm) = 'PV' AND TRIM(documento) = :doc AND TRIM(prefijo) = :pref
                  AND  estado IN ('C') AND TRIM(estadorm) <> 'A'
                LIMIT 1
            ");
            $chk->execute([':doc' => $documento, ':pref' => $prefijo]);
            if (!$chk->fetch()) {
                $errores[] = 'El pedido ya no está activo en alistamiento; no se puede crear la alerta.';
            }
        }

        if (empty($errores)) {
            // Bloquear duplicado: máximo una alerta activa por documento
            $dup = $this->db->pdo->prepare("
                SELECT 1 FROM prioridadmov
                WHERE  TRIM(tm) = 'PV' AND TRIM(documento) = :doc AND TRIM(prefijo) = :pref
                  AND  estado = 'A'
                LIMIT 1
            ");
            $dup->execute([':doc' => $documento, ':pref' => $prefijo]);
            if ($dup->fetch()) {
                $errores[] = 'Ya existe una alerta activa para este pedido. Ciérrela antes de crear otra.';
            }
        }

        if (!empty($errores)) {
            $_SESSION['errors'] = $errores;
            $_SESSION['old']    = $body;
            return $response->withHeader('Location', '/prioridad-alistamiento/crear')->withStatus(302);
        }

        try {
            $ins = $this->db->pdo->prepare("
                INSERT INTO prioridadmov
                    (tm, prefijo, documento, canal, comentario, estado, usuariocrea, fechacrea, horacrea)
                VALUES
                    ('PV', :prefijo, :documento, :canal, :comentario, 'A', :usuario, CURDATE(), DATE_FORMAT(NOW(), '%H:%i'))
            ");
            $ins->execute([
                ':prefijo'    => $prefijo,
                ':documento'  => $documento,
                ':canal'      => $canal,
                ':comentario' => $comentario,
                ':usuario'    => $this->nombreUsuario(),
            ]);
            $_SESSION['success'] = "Alerta de prioridad creada para el pedido PV {$documento}.";
        } catch (\Exception $e) {
            error_log('PrioridadAlistamientoController::guardar error: ' . $e->getMessage());
            $_SESSION['errors'] = ['No se pudo guardar la alerta: ' . $e->getMessage()];
            $_SESSION['old']    = $body;
            return $response->withHeader('Location', '/prioridad-alistamiento/crear')->withStatus(302);
        }

        return $response->withHeader('Location', '/prioridad-alistamiento')->withStatus(302);
    }

    // ─────────────────────────────────────────────────────────────────────
    // POST /prioridad-alistamiento/{id}/cancelar   (body: motivo)
    // ─────────────────────────────────────────────────────────────────────
    public function cancelar($request, $response, $args): mixed
    {
        $id     = (int)$args['id'];
        $motivo = trim($request->getParsedBody()['motivo'] ?? '');

        if ($motivo === '') {
            $_SESSION['errors'] = ['Escriba un comentario indicando el motivo de cierre.'];
            return $response->withHeader('Location', '/prioridad-alistamiento')->withStatus(302);
        }

        $sel = $this->db->pdo->prepare("SELECT comentario, estado, documento FROM prioridadmov WHERE id = :id LIMIT 1");
        $sel->execute([':id' => $id]);
        $row = $sel->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            $_SESSION['errors'] = ['No se encontró la solicitud.'];
            return $response->withHeader('Location', '/prioridad-alistamiento')->withStatus(302);
        }
        if ($row['estado'] !== 'A') {
            $_SESSION['errors'] = ['La solicitud ya se encuentra cerrada.'];
            return $response->withHeader('Location', '/prioridad-alistamiento')->withStatus(302);
        }

        $usuario = $this->nombreUsuario();
        $hora    = date('H:i');
        $nuevoComentario = $row['comentario'] . " | CANCELADA ({$usuario} {$hora}): {$motivo}";
        if (strlen($nuevoComentario) > 500) {
            $nuevoComentario = substr($nuevoComentario, 0, 500);
        }

        $upd = $this->db->pdo->prepare("
            UPDATE prioridadmov
            SET    estado = 'C', comentario = :comentario
            WHERE  id = :id
            LIMIT 1
        ");
        $upd->execute([':comentario' => $nuevoComentario, ':id' => $id]);

        $_SESSION['success'] = "Alerta del pedido PV {$row['documento']} cancelada.";
        return $response->withHeader('Location', '/prioridad-alistamiento')->withStatus(302);
    }
}
