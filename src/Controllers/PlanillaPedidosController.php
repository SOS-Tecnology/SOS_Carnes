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
        $documento = $args['nrodoc'];
        $this->db->update("cabezamov", ["estado" => "O"], [
            "documento" => $documento,
            "tm"        => "PV",
        ]);
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
        $nrodoc = trim($args['nrodoc']);
        $numreg = (int) $args['numreg'];

        $cabeza = $this->db->pdo->prepare("
            SELECT TRIM(c.documento) AS nrodoc, TRIM(c.prefijo) AS prefijo,
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
            SELECT cm.numreg, TRIM(cm.codr) AS codart,
                   TRIM(cm.descr) AS descripcion,
                   cm.unidad, cm.cantidad, cm.cantent
            FROM cuerpomov cm
            WHERE cm.tm = 'PV'
              AND TRIM(cm.prefijo)   = :prefijo
              AND TRIM(cm.documento) = :doc
              AND cm.numreg = :numreg
            LIMIT 1
        ");
        $stmt->execute([':prefijo' => $pedido['prefijo'], ':doc' => $nrodoc, ':numreg' => $numreg]);
        $item = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$item) {
            return $response->withHeader('Location', '/planilla-pedidos/' . urlencode($nrodoc) . '/detalle')->withStatus(302);
        }

        return renderView($response, __DIR__ . '/../Views/planilla-pedidos/item.php', 'Alistar Ítem', [
            'pedido' => $pedido,
            'item'   => $item,
        ]);
    }

    public function actualizarItem($request, $response, $args): mixed
    {
        $nrodoc  = trim($args['nrodoc']);
        $numreg  = (int) $args['numreg'];
        $body    = $request->getParsedBody();
        $cantent = (float) ($body['cantent'] ?? 0);

        $prefijo = $this->db->pdo->prepare("
            SELECT TRIM(prefijo) AS prefijo FROM cabezamov
            WHERE tm = 'PV' AND TRIM(documento) = :doc LIMIT 1
        ");
        $prefijo->execute([':doc' => $nrodoc]);
        $row = $prefijo->fetch(\PDO::FETCH_ASSOC);

        if ($row) {
            $this->db->pdo->prepare("
                UPDATE cuerpomov SET cantent = :cantent
                WHERE tm = 'PV'
                  AND TRIM(prefijo)   = :prefijo
                  AND TRIM(documento) = :doc
                  AND numreg          = :numreg
            ")->execute([
                ':cantent' => $cantent,
                ':prefijo' => $row['prefijo'],
                ':doc'     => $nrodoc,
                ':numreg'  => $numreg,
            ]);
        }

        return $response->withHeader('Location', '/planilla-pedidos/' . urlencode($nrodoc) . '/detalle')->withStatus(302);
    }

    private function getItems(string $nrodoc, string $prefijo): array
    {
        $stmt = $this->db->pdo->prepare("
            SELECT cm.numreg,
                   TRIM(cm.codr)       AS codart,
                   TRIM(cm.descr)  AS descripcion,
                   cm.unidad,
                   cm.cantidad,
                   cm.cantent,
                   GREATEST(cm.cantidad - cm.cantent, 0) AS diferencia
            FROM cuerpomov cm
            WHERE cm.tm = 'PV'
              AND TRIM(cm.prefijo)   = :prefijo
              AND TRIM(cm.documento) = :doc
            ORDER BY cm.numreg
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
                WHERE tm = 'PV' AND a.estado IN ('M', 'C')
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
