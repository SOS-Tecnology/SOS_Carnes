<?php

namespace App\Controllers;

use Medoo\Medoo;

class PlanillaPedidosController
{
    public function __construct(private Medoo $db) {}

    public function index($request, $response): mixed
    {
        // Refresca codtipocli si la sesión es anterior a este campo
        if (!isset($_SESSION['user']['codtipocli'])) {
            $u = $this->db->get('users', ['tipocliente'], ['id' => $_SESSION['user']['id']]);
            $_SESSION['user']['codtipocli'] = trim($u['tipocliente'] ?? '');
        }

        $codtipocli = trim($_SESSION['user']['codtipocli'] ?? '');
        $pedidos    = $this->getPedidos($codtipocli);

        return renderView($response, __DIR__ . '/../Views/planilla-pedidos/index.php', 'Planilla de Pedidos', [
            'pedidos'    => $pedidos,
            'codtipocli' => $codtipocli,
        ]);
    }

    public function cerrar($request, $response, $args): mixed
    {
        $documento = trim($args['nrodoc']);
        $this->db->pdo->prepare("
            UPDATE cabezamov
            SET estadorm = 'A', estado = 'O'
            WHERE TRIM(tm) = 'PV' AND TRIM(documento) = :doc
        ")->execute([':doc' => $documento]);

        $response->getBody()->write(json_encode(['ok' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function apiPedidos($request, $response): mixed
    {
        if (!isset($_SESSION['user']['codtipocli'])) {
            $u = $this->db->get('users', ['tipocliente'], ['id' => $_SESSION['user']['id']]);
            $_SESSION['user']['codtipocli'] = trim($u['tipocliente'] ?? '');
        }

        $codtipocli = trim($_SESSION['user']['codtipocli'] ?? '');
        $pedidos    = $this->getPedidos($codtipocli);
        $response->getBody()->write(json_encode($pedidos));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function detalle($request, $response, $args): mixed
    {
        $nrodoc = trim($args['nrodoc']);

        $cabeza = $this->db->pdo->prepare("
            SELECT TRIM(c.documento) AS nrodoc, TRIM(c.prefijo) AS prefijo,
                   DATE_FORMAT(c.fechent, '%d/%m/%Y') AS fecentrega_fmt,
                   c.estado,
                   TRIM(g.nombrecli) AS nomcli, TRIM(g.codtipocli) AS codtipocli
            FROM cabezamov c
            INNER JOIN geclientes g ON c.codcp = g.codcli
            WHERE c.tm = 'PV' AND TRIM(c.documento) = :doc
            LIMIT 1
        ");
        $cabeza->execute([':doc' => $nrodoc]);
        $pedido = $cabeza->fetch(\PDO::FETCH_ASSOC);

        if (!$pedido) {
            return $response->withHeader('Location', '/planilla-pedidos')->withStatus(302);
        }

        $items = $this->getItems($nrodoc, $pedido['prefijo']);

        return renderView($response, __DIR__ . '/../Views/planilla-pedidos/detalle.php', 'Detalle Pedido', [
            'pedido' => $pedido,
            'items'  => $items,
        ]);
    }

    public function verItem($request, $response, $args): mixed
    {
        $nrodoc   = trim($args['nrodoc']);
        $registro = trim($args['registro']);

        $cabeza = $this->db->pdo->prepare("
            SELECT TRIM(c.documento) AS nrodoc, TRIM(c.prefijo) AS prefijo,
                   TRIM(c.codcp)     AS codcp,  TRIM(c.codsuc)  AS codsuc,
                   TRIM(g.nombrecli) AS nomcli
            FROM cabezamov c
            INNER JOIN geclientes g ON c.codcp = g.codcli
            WHERE c.tm = 'PV' AND TRIM(c.documento) = :doc
            LIMIT 1
        ");
        $cabeza->execute([':doc' => $nrodoc]);
        $pedido = $cabeza->fetch(\PDO::FETCH_ASSOC);
        if (!$pedido) {
            return $response->withHeader('Location', '/planilla-pedidos')->withStatus(302);
        }

        $stmt = $this->db->pdo->prepare("
            SELECT TRIM(cm.registro)  AS registro,
                   TRIM(cm.codr)      AS codart,
                   TRIM(cm.descr)     AS descripcion,
                   TRIM(cm.comencpo)  AS comencpo,
                   cm.unidad, cm.cantidad, cm.cantent
            FROM cuerpomov cm
            WHERE TRIM(cm.tm)        = 'PV'
              AND TRIM(cm.prefijo)   = :prefijo
              AND TRIM(cm.documento) = :doc
              AND TRIM(cm.registro)  = :registro
            LIMIT 1
        ");
        $stmt->execute([':prefijo' => $pedido['prefijo'], ':doc' => $nrodoc, ':registro' => $registro]);
        $item = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$item) {
            return $response->withHeader('Location', '/planilla-pedidos/' . urlencode($nrodoc) . '/detalle')->withStatus(302);
        }

        $fichaTecnica = $this->getFichaTecnica($pedido['codcp'], $pedido['codsuc'], $item['codart']);
        $lotes        = $this->getItemmovEntries($nrodoc, $pedido['prefijo'], $item['codart'], $registro);

        return renderView($response, __DIR__ . '/../Views/planilla-pedidos/item.php', 'Alistar Ítem', [
            'pedido'       => $pedido,
            'item'         => $item,
            'fichaTecnica' => $fichaTecnica,
            'lotes'        => $lotes,
        ]);
    }

    public function actualizarItem($request, $response, $args): mixed
    {
        $nrodoc   = trim($args['nrodoc']);
        $registro = trim($args['registro']);
        $body     = $request->getParsedBody();
        $lote     = trim($body['lote']      ?? '');
        $temp     = (float)($body['temp']   ?? 0);
        $cantidad = (float)($body['cantidad'] ?? 0);

        if ($cantidad <= 0) {
            return $response->withHeader('Location',
                '/planilla-pedidos/' . urlencode($nrodoc) . '/item/' . urlencode($registro)
            )->withStatus(302);
        }

        try {
            // Inicia transacción para garantizar consistencia
            $this->db->pdo->beginTransaction();

            // PV header + item info in one query
            $itStmt = $this->db->pdo->prepare("
                SELECT TRIM(cm.codr)    AS codr,
                       TRIM(cm.descr)   AS descr,
                       TRIM(cm.prefijo) AS prefijo
                FROM cuerpomov cm
                WHERE TRIM(cm.tm)        = 'PV'
                  AND TRIM(cm.documento) = :doc
                  AND TRIM(cm.registro)  = :registro
                LIMIT 1
            ");
            $itStmt->execute([':doc' => $nrodoc, ':registro' => $registro]);
            $item = $itStmt->fetch(\PDO::FETCH_ASSOC);
            if (!$item) {
                $this->db->pdo->rollBack();
                return $response->withHeader('Location', '/planilla-pedidos/' . urlencode($nrodoc) . '/detalle')->withStatus(302);
            }

            // Next itemre for this registro
            $nrStmt = $this->db->pdo->prepare("
                SELECT COALESCE(MAX(CAST(itemre AS UNSIGNED)), 0) + 1
                FROM itemmov
                WHERE TRIM(tm) = 'PV' AND TRIM(prefijo) = :prefijo
                  AND TRIM(documento) = :doc AND TRIM(registro) = :registro
            ");
            $nrStmt->execute([':prefijo' => $item['prefijo'], ':doc' => $nrodoc, ':registro' => $registro]);
            $newItemre = (string)($nrStmt->fetchColumn() ?: 1);

            $hora = date('H:i:s');

            // Insertar en itemmov
            $this->db->pdo->prepare("
                INSERT INTO itemmov (tm, prefijo, documento, codr, descr, cantidad, lote, registro, itemre, temp, hora)
                VALUES ('PV', :prefijo, :doc, :codr, :descr, :cantidad, :lote, :registro, :itemre, :temp, :hora)
            ")->execute([
                ':prefijo'  => trim($item['prefijo']),
                ':doc'      => $nrodoc,
                ':codr'     => $item['codr'],
                ':descr'    => $item['descr'],
                ':cantidad' => $cantidad,
                ':lote'     => $lote,
                ':registro' => $registro,
                ':itemre'   => $newItemre,
                ':temp'     => $temp,
                ':hora'     => $hora,
            ]);

            // Recalcular cantent en la misma transacción
            $this->recalcCantent($nrodoc, $item['prefijo'], $item['codr'], $registro);

            // Commit transacción
            $this->db->pdo->commit();
        } catch (\Exception $e) {
            $this->db->pdo->rollBack();
            throw $e;
        }

        return $response->withHeader('Location',
            '/planilla-pedidos/' . urlencode($nrodoc) . '/item/' . urlencode($registro)
        )->withStatus(302);
    }

    public function eliminarLote($request, $response, $args): mixed
    {
        $nrodoc   = trim($args['nrodoc']);
        $registro = trim($args['registro']);
        $hora     = trim($request->getParsedBody()['hora'] ?? '');

        try {
            $this->db->pdo->beginTransaction();

            $itStmt = $this->db->pdo->prepare("
                SELECT TRIM(cm.codr) AS codr, TRIM(cm.prefijo) AS prefijo
                FROM cuerpomov cm
                WHERE TRIM(cm.tm)        = 'PV'
                  AND TRIM(cm.documento) = :doc
                  AND TRIM(cm.registro)  = :registro
                LIMIT 1
            ");
            $itStmt->execute([':doc' => $nrodoc, ':registro' => $registro]);
            $item = $itStmt->fetch(\PDO::FETCH_ASSOC);
            if (!$item) {
                $this->db->pdo->rollBack();
                return $response->withHeader('Location', '/planilla-pedidos/' . urlencode($nrodoc) . '/detalle')->withStatus(302);
            }

            $this->db->pdo->prepare("
                DELETE FROM itemmov
                WHERE TRIM(tm)        = 'PV'
                  AND TRIM(prefijo)   = :prefijo
                  AND TRIM(documento) = :doc
                  AND TRIM(codr)      = :codr
                  AND TRIM(registro)  = :registro
                  AND hora            = :hora
                LIMIT 1
            ")->execute([
                ':prefijo'  => $item['prefijo'],
                ':doc'      => $nrodoc,
                ':codr'     => $item['codr'],
                ':registro' => $registro,
                ':hora'     => $hora,
            ]);

            $this->recalcCantent($nrodoc, $item['prefijo'], $item['codr'], $registro);

            $this->db->pdo->commit();
        } catch (\Exception $e) {
            $this->db->pdo->rollBack();
            throw $e;
        }

        return $response->withHeader('Location',
            '/planilla-pedidos/' . urlencode($nrodoc) . '/item/' . urlencode($registro)
        )->withStatus(302);
    }

    // ── Private helpers ────────────────────────────────────────────────────

    private function recalcCantent(string $pvDoc, string $pvPrefijo, string $codr, string $registro): void
    {
        $sumStmt = $this->db->pdo->prepare("
            SELECT COALESCE(SUM(cantidad), 0)
            FROM itemmov
            WHERE TRIM(tm)        = 'PV'
              AND TRIM(prefijo)   = :prefijo
              AND TRIM(documento) = :doc
              AND TRIM(codr)      = :codr
              AND TRIM(registro)  = :registro
        ");
        $sumStmt->execute([
            ':prefijo'  => $pvPrefijo,
            ':doc'      => $pvDoc,
            ':codr'     => $codr,
            ':registro' => $registro,
        ]);
        $total = (float) $sumStmt->fetchColumn();

        $this->db->pdo->prepare("
            UPDATE cuerpomov SET cantent = :total
            WHERE TRIM(tm)        = 'PV'
              AND TRIM(prefijo)   = :prefijo
              AND TRIM(documento) = :doc
              AND TRIM(codr)      = :codr
              AND TRIM(registro)  = :registro
        ")->execute([
            ':total'    => $total,
            ':prefijo'  => $pvPrefijo,
            ':doc'      => $pvDoc,
            ':codr'     => $codr,
            ':registro' => $registro,
        ]);
    }

    private function getFichaTecnica(string $codcp, string $codsuc, string $codr): ?array
    {
        $stmt = $this->db->pdo->prepare("
            SELECT pp.empaque, pp.conservacion, pp.embalaje,
                   pp.tolerancia, pp.diasmaduracion, pp.pesoxemp
            FROM platoproyecto pp
            WHERE TRIM(pp.codigoproy)  = :codcp
              AND TRIM(pp.codsuc)      = :codsuc
              AND TRIM(pp.codigoplato) = :codr
            LIMIT 1
        ");
        $stmt->execute([':codcp' => $codcp, ':codsuc' => $codsuc, ':codr' => $codr]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function getItemmovEntries(string $pvDoc, string $pvPrefijo, string $codr, string $registro): array
    {
        $stmt = $this->db->pdo->prepare("
            SELECT i.hora, i.lote, i.temp, i.cantidad
            FROM itemmov i
            WHERE TRIM(i.tm)        = 'PV'
              AND TRIM(i.prefijo)   = :prefijo
              AND TRIM(i.documento) = :doc
              AND TRIM(i.codr)      = :codr
              AND TRIM(i.registro)  = :registro
            ORDER BY i.hora
        ");
        $stmt->execute([
            ':prefijo'  => $pvPrefijo,
            ':doc'      => $pvDoc,
            ':codr'     => $codr,
            ':registro' => $registro,
        ]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getItems(string $nrodoc, string $prefijo): array
    {
        $stmt = $this->db->pdo->prepare("
            SELECT TRIM(cm.registro)                           AS registro,
                   TRIM(cm.codr)                               AS codart,
                   TRIM(cm.descr)                              AS descripcion,
                   cm.unidad,
                   cm.cantidad,
                   cm.cantent,
                   GREATEST(cm.cantidad - cm.cantent, 0)       AS diferencia
            FROM cuerpomov cm
            WHERE TRIM(cm.tm)        = 'PV'
              AND TRIM(cm.prefijo)   = :prefijo
              AND TRIM(cm.documento) = :doc
            ORDER BY cm.registro
        ");
        $stmt->execute([':prefijo' => $prefijo, ':doc' => $nrodoc]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getPedidos(string $codtipocli): array
    {
        $whereClause = $codtipocli !== '' ? 'WHERE TRIM(c.codtipocli) = :codtipocli' : '';

        $sql = "
            WITH cabezatmp AS (
                SELECT a.documento, prefijo, tm, fecha, fechent, codcp, b.codtipocli, estado
                FROM cabezamov a
                INNER JOIN geclientes b ON a.codcp = b.codcli
                WHERE tm = 'PV' AND a.estado IN ('C') AND TRIM(a.estadorm) <> 'A'
            )
            SELECT
                TRIM(c.documento)                                                          AS nrodoc,
                TRIM(c.prefijo)                                                            AS prefijo,
                DATE_FORMAT(c.fechent, '%d/%m/%Y')                                        AS fecentrega_fmt,
                c.estado,
                TRIM(g.nombrecli)                                                          AS nomcli,
                TRIM(c.documento)                                                          AS documento_cli,
                COALESCE(COUNT(cm.numreg), 0)                                              AS total_items,
                COALESCE(SUM(CASE WHEN cm.cantent >= cm.cantidad AND cm.cantidad > 0
                                  THEN 1 ELSE 0 END), 0)                                   AS items_completos,
                COALESCE(SUM(CASE WHEN cm.cantent > 0 THEN 1 ELSE 0 END), 0)              AS items_iniciados
            FROM cabezatmp c
            LEFT JOIN cuerpomov cm ON c.tm = cm.tm AND c.prefijo = cm.prefijo AND c.documento = cm.documento
            INNER JOIN geclientes g ON c.codcp = g.codcli
            {$whereClause}
            GROUP BY c.documento, c.prefijo
            ORDER BY c.fechent ASC
        ";

        $params = [];
        if ($codtipocli !== '') {
            $params[':codtipocli'] = $codtipocli;
        }

        $stmt = $this->db->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
