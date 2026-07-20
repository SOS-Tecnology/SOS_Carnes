<?php

namespace App\Controllers;

use Medoo\Medoo;

/**
 * Estado de Pedidos — vista transversal del ciclo PV → AP.
 *
 * Etapas (calculadas, no almacenadas):
 *   facturado        AP del PV con estado='O'          → sin modificación
 *   preparado        PV estadorm='R'                    (AP cerrada 'C')
 *   en_preparacion   PV estado='O' + estadorm='A' + AP con estado en blanco
 *   listo_preparar   PV estado='O' + estadorm='A' sin AP abierta
 *   en_alistamiento  PV estado='C' + estadorm<>'A'
 *   otro             cualquier combinación distinta (solo informativa)
 */
class EstadoPedidosController
{
    public function __construct(private Medoo $db) {}

    private function canalUsuario(): string
    {
        if (!isset($_SESSION['user']['codtipocli'])) {
            $u = $this->db->get('users', ['tipocliente'], ['id' => $_SESSION['user']['id']]);
            $_SESSION['user']['codtipocli'] = trim($u['tipocliente'] ?? '');
        }
        return trim($_SESSION['user']['codtipocli'] ?? '');
    }

    /**
     * Estados de las AP asociadas a un conjunto de PV (una sola consulta).
     * Retorna mapa 'docaux|prefaux' => ['facturada'=>bool,'abierta'=>bool,'cerrada'=>bool]
     */
    private function mapaAPs(array $docs): array
    {
        if (empty($docs)) {
            return [];
        }
        $ph = implode(',', array_fill(0, count($docs), '?'));
        $stmt = $this->db->pdo->prepare("
            SELECT TRIM(c.docaux)  AS docaux,
                   TRIM(c.prefaux) AS prefaux,
                   TRIM(c.estado)  AS estado
            FROM   cabezamov c
            WHERE  TRIM(c.tm)    = 'AP'
              AND  TRIM(c.tmaux) = 'PV'
              AND  c.docaux IN ($ph)
        ");
        $stmt->execute($docs);

        $map = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $ap) {
            $key = $ap['docaux'] . '|' . $ap['prefaux'];
            if (!isset($map[$key])) {
                $map[$key] = ['facturada' => false, 'abierta' => false, 'cerrada' => false];
            }
            if ($ap['estado'] === 'O') {
                $map[$key]['facturada'] = true;
            } elseif ($ap['estado'] === 'C') {
                $map[$key]['cerrada'] = true;
            } elseif ($ap['estado'] === '') {
                $map[$key]['abierta'] = true;
            }
        }
        return $map;
    }

    /** Etapa del PV según sus estados y sus AP */
    private function etapa(array $p, ?array $ap): string
    {
        if ($ap && $ap['facturada']) {
            return 'facturado';
        }
        if ($p['estadorm'] === 'R') {
            return 'preparado';
        }
        if ($p['estado'] === 'O' && $p['estadorm'] === 'A') {
            return ($ap && $ap['abierta']) ? 'en_preparacion' : 'listo_preparar';
        }
        if ($p['estado'] === 'C' && $p['estadorm'] !== 'A') {
            return 'en_alistamiento';
        }
        return 'otro';
    }

    // ─────────────────────────────────────────────────────────────────────
    // GET /estado-pedidos?limite=100|500|1000|0 (0 = todos)
    // ─────────────────────────────────────────────────────────────────────
    public function index($request, $response): mixed
    {
        $canal      = $this->canalUsuario();
        $whereExtra = $canal !== '' ? 'AND TRIM(g.codtipocli) = :codtipocli' : '';

        $limite = (int)($request->getQueryParams()['limite'] ?? 100);
        if (!in_array($limite, [100, 500, 1000, 0], true)) {
            $limite = 100;
        }
        $limitSql = $limite > 0 ? "LIMIT {$limite}" : '';

        $stmt = $this->db->pdo->prepare("
            SELECT TRIM(c.documento)                  AS nrodoc,
                   TRIM(c.prefijo)                    AS prefijo,
                   DATE_FORMAT(c.fecha,   '%d/%m/%Y') AS fecha_fmt,
                   DATE_FORMAT(c.fechent, '%d/%m/%Y') AS fecentrega_fmt,
                   TRIM(c.estado)                     AS estado,
                   TRIM(c.estadorm)                   AS estadorm,
                   TRIM(g.codcli)                     AS codcli,
                   TRIM(g.nombrecli)                  AS nomcli
            FROM   cabezamov c
            INNER  JOIN geclientes g ON c.codcp = g.codcli
            WHERE  TRIM(c.tm) = 'PV'
              {$whereExtra}
            ORDER  BY c.documento DESC
            {$limitSql}
        ");
        $params = $canal !== '' ? [':codtipocli' => $canal] : [];
        $stmt->execute($params);
        $pedidos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Estados de AP en una sola consulta
        $mapAP = $this->mapaAPs(array_values(array_unique(array_column($pedidos, 'nrodoc'))));

        foreach ($pedidos as &$p) {
            $ap         = $mapAP[$p['nrodoc'] . '|' . $p['prefijo']] ?? null;
            $p['etapa'] = $this->etapa($p, $ap);
        }
        unset($p);

        return renderView(
            $response,
            __DIR__ . '/../Views/estado-pedidos/index.php',
            'Estado de Pedidos',
            ['pedidos' => $pedidos, 'limite' => $limite]
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // GET /estado-pedidos/{nrodoc}/modificar?prefijo=XX
    // ─────────────────────────────────────────────────────────────────────
    public function modificar($request, $response, $args): mixed
    {
        $nrodoc  = trim($args['nrodoc']);
        $prefijo = trim($request->getQueryParams()['prefijo'] ?? '');

        $pedido = $this->cargarPedido($nrodoc, $prefijo);
        if (!$pedido) {
            $_SESSION['errors'] = ["No se encontró el pedido PV {$nrodoc}."];
            return $response->withHeader('Location', '/estado-pedidos')->withStatus(302);
        }

        $mapAP = $this->mapaAPs([$pedido['nrodoc']]);
        $ap    = $mapAP[$pedido['nrodoc'] . '|' . $pedido['prefijo']] ?? null;
        $etapa = $this->etapa($pedido, $ap);

        // AP asociadas (detalle para mostrar en pantalla)
        $apsStmt = $this->db->pdo->prepare("
            SELECT TRIM(c.documento)                  AS doc_ap,
                   TRIM(c.prefijo)                    AS pref_ap,
                   TRIM(c.estado)                     AS estado_ap,
                   DATE_FORMAT(c.fecha, '%d/%m/%Y')   AS fecha_fmt
            FROM   cabezamov c
            WHERE  TRIM(c.tm)      = 'AP'
              AND  TRIM(c.tmaux)   = 'PV'
              AND  TRIM(c.docaux)  = :docaux
              AND  TRIM(c.prefaux) = :prefaux
            ORDER  BY c.documento DESC
        ");
        $apsStmt->execute([':docaux' => $pedido['nrodoc'], ':prefaux' => $pedido['prefijo']]);
        $aps = $apsStmt->fetchAll(\PDO::FETCH_ASSOC);

        return renderView(
            $response,
            __DIR__ . '/../Views/estado-pedidos/modificar.php',
            'Modificar Estado de Pedido',
            [
                'pedido' => $pedido,
                'etapa'  => $etapa,
                'aps'    => $aps,
            ]
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // POST /estado-pedidos/{nrodoc}/abrir-preparacion   (body: prefijo)
    // PV estadorm 'R' → 'A'  y  AP 'C' → ' ' (en blanco)
    // ─────────────────────────────────────────────────────────────────────
    public function abrirPreparacion($request, $response, $args): mixed
    {
        $nrodoc  = trim($args['nrodoc']);
        $prefijo = trim($request->getParsedBody()['prefijo'] ?? '');
        $volver  = '/estado-pedidos/' . urlencode($nrodoc) . '/modificar?prefijo=' . urlencode($prefijo);

        $pedido = $this->cargarPedido($nrodoc, $prefijo);
        if (!$pedido) {
            $_SESSION['errors'] = ["No se encontró el pedido PV {$nrodoc}."];
            return $response->withHeader('Location', '/estado-pedidos')->withStatus(302);
        }

        if ($this->estaFacturado($pedido)) {
            $_SESSION['errors'] = ['El pedido está facturado: no permite modificaciones.'];
            return $response->withHeader('Location', $volver)->withStatus(302);
        }
        if ($pedido['estadorm'] !== 'R') {
            $_SESSION['errors'] = ['El pedido no está en etapa Preparado; no hay preparación que abrir.'];
            return $response->withHeader('Location', $volver)->withStatus(302);
        }

        try {
            // PV: estadorm 'R' → 'A' (estado permanece 'O')
            $updPV = $this->db->pdo->prepare("
                UPDATE cabezamov
                SET    estadorm = 'A'
                WHERE  TRIM(tm)        = 'PV'
                  AND  TRIM(documento) = :doc
                  AND  TRIM(prefijo)   = :pref
                LIMIT 1
            ");
            $updPV->execute([':doc' => $pedido['nrodoc'], ':pref' => $pedido['prefijo']]);

            // AP cerrada más reciente: 'C' → ' ' (queda En proceso)
            $apStmt = $this->db->pdo->prepare("
                SELECT TRIM(documento) AS doc_ap, TRIM(prefijo) AS pref_ap
                FROM   cabezamov
                WHERE  TRIM(tm)      = 'AP'
                  AND  TRIM(tmaux)   = 'PV'
                  AND  TRIM(docaux)  = :docaux
                  AND  TRIM(prefaux) = :prefaux
                  AND  TRIM(estado)  = 'C'
                ORDER  BY documento DESC
                LIMIT 1
            ");
            $apStmt->execute([':docaux' => $pedido['nrodoc'], ':prefaux' => $pedido['prefijo']]);
            $ap = $apStmt->fetch(\PDO::FETCH_ASSOC);

            if ($ap) {
                $updAP = $this->db->pdo->prepare("
                    UPDATE cabezamov
                    SET    estado = ' '
                    WHERE  TRIM(tm)        = 'AP'
                      AND  TRIM(documento) = :doc
                      AND  TRIM(prefijo)   = :pref
                    LIMIT 1
                ");
                $updAP->execute([':doc' => $ap['doc_ap'], ':pref' => $ap['pref_ap']]);
                $_SESSION['success'] = "Preparación abierta: PV {$pedido['nrodoc']} volvió a 'A' y la AP {$ap['doc_ap']} quedó en proceso.";
            } else {
                $_SESSION['success'] = "Preparación abierta: PV {$pedido['nrodoc']} volvió a 'A' (no se encontró AP cerrada asociada).";
            }
        } catch (\Exception $e) {
            error_log('EstadoPedidosController::abrirPreparacion error: ' . $e->getMessage());
            $_SESSION['errors'] = ['No se pudo abrir la preparación: ' . $e->getMessage()];
        }

        return $response->withHeader('Location', $volver)->withStatus(302);
    }

    // ─────────────────────────────────────────────────────────────────────
    // POST /estado-pedidos/{nrodoc}/abrir-alistamiento   (body: prefijo)
    // PV estado 'O' → 'C'  y  estadorm 'A' → ' ' (en blanco)
    // ─────────────────────────────────────────────────────────────────────
    public function abrirAlistamiento($request, $response, $args): mixed
    {
        $nrodoc  = trim($args['nrodoc']);
        $prefijo = trim($request->getParsedBody()['prefijo'] ?? '');
        $volver  = '/estado-pedidos/' . urlencode($nrodoc) . '/modificar?prefijo=' . urlencode($prefijo);

        $pedido = $this->cargarPedido($nrodoc, $prefijo);
        if (!$pedido) {
            $_SESSION['errors'] = ["No se encontró el pedido PV {$nrodoc}."];
            return $response->withHeader('Location', '/estado-pedidos')->withStatus(302);
        }

        if ($this->estaFacturado($pedido)) {
            $_SESSION['errors'] = ['El pedido está facturado: no permite modificaciones.'];
            return $response->withHeader('Location', $volver)->withStatus(302);
        }
        if ($pedido['estadorm'] === 'R') {
            $_SESSION['errors'] = ['El pedido está Preparado: primero abra la preparación y luego el alistamiento.'];
            return $response->withHeader('Location', $volver)->withStatus(302);
        }
        if (!($pedido['estado'] === 'O' && $pedido['estadorm'] === 'A')) {
            $_SESSION['errors'] = ['El pedido no está alistado; no hay alistamiento que abrir.'];
            return $response->withHeader('Location', $volver)->withStatus(302);
        }

        try {
            // PV: estado 'O' → 'C' y estadorm 'A' → ' ' (vuelve a la planilla)
            $updPV = $this->db->pdo->prepare("
                UPDATE cabezamov
                SET    estado = 'C', estadorm = ' '
                WHERE  TRIM(tm)        = 'PV'
                  AND  TRIM(documento) = :doc
                  AND  TRIM(prefijo)   = :pref
                LIMIT 1
            ");
            $updPV->execute([':doc' => $pedido['nrodoc'], ':pref' => $pedido['prefijo']]);
            $_SESSION['success'] = "Alistamiento abierto: PV {$pedido['nrodoc']} volvió a la Planilla de Pedidos.";
        } catch (\Exception $e) {
            error_log('EstadoPedidosController::abrirAlistamiento error: ' . $e->getMessage());
            $_SESSION['errors'] = ['No se pudo abrir el alistamiento: ' . $e->getMessage()];
        }

        return $response->withHeader('Location', $volver)->withStatus(302);
    }

    // ─────────────────────────────────────────────────────────────────────

    private function cargarPedido(string $nrodoc, string $prefijo): ?array
    {
        $wherePref = $prefijo !== '' ? 'AND TRIM(c.prefijo) = :pref' : '';
        $stmt = $this->db->pdo->prepare("
            SELECT TRIM(c.documento)                  AS nrodoc,
                   TRIM(c.prefijo)                    AS prefijo,
                   DATE_FORMAT(c.fecha,   '%d/%m/%Y') AS fecha_fmt,
                   DATE_FORMAT(c.fechent, '%d/%m/%Y') AS fecentrega_fmt,
                   TRIM(c.estado)                     AS estado,
                   TRIM(c.estadorm)                   AS estadorm,
                   TRIM(g.codcli)                     AS codcli,
                   TRIM(g.nombrecli)                  AS nomcli
            FROM   cabezamov c
            INNER  JOIN geclientes g ON c.codcp = g.codcli
            WHERE  TRIM(c.tm)        = 'PV'
              AND  TRIM(c.documento) = :doc
              {$wherePref}
            LIMIT 1
        ");
        $params = [':doc' => $nrodoc];
        if ($prefijo !== '') {
            $params[':pref'] = $prefijo;
        }
        $stmt->execute($params);
        $pedido = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $pedido ?: null;
    }

    private function estaFacturado(array $pedido): bool
    {
        $mapAP = $this->mapaAPs([$pedido['nrodoc']]);
        $ap    = $mapAP[$pedido['nrodoc'] . '|' . $pedido['prefijo']] ?? null;
        return $ap !== null && $ap['facturada'];
    }
}
