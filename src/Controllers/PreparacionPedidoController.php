<?php

namespace App\Controllers;

use Medoo\Medoo;

class PreparacionPedidoController
{
    public function __construct(private Medoo $db) {}

    public function index($request, $response): mixed
    {
        if (!isset($_SESSION['user']['codtipocli'])) {
            $u = $this->db->get('users', ['tipocliente'], ['id' => $_SESSION['user']['id']]);
            $_SESSION['user']['codtipocli'] = trim($u['tipocliente'] ?? '');
        }
        $codtipocli  = trim($_SESSION['user']['codtipocli'] ?? '');
        $whereExtra  = $codtipocli !== '' ? 'AND TRIM(g.codtipocli) = :codtipocli' : '';

        $stmt = $this->db->pdo->prepare("
            SELECT TRIM(c.documento)                          AS nrodoc,
                   TRIM(c.prefijo)                            AS prefijo,
                   DATE_FORMAT(c.fechent, '%d/%m/%Y')         AS fecentrega_fmt,
                   TRIM(g.nombrecli)                          AS nomcli,
                   TRIM(g.codtipocli)                         AS codtipocli
            FROM   cabezamov c
            INNER  JOIN geclientes g ON c.codcp = g.codcli
            WHERE  TRIM(c.tm) = 'PV'
              AND  c.estado   = 'C'
              AND  TRIM(c.estadorm) = 'A'
              {$whereExtra}
            ORDER  BY c.fechent ASC
        ");
        $params = $codtipocli !== '' ? [':codtipocli' => $codtipocli] : [];
        $stmt->execute($params);
        $pedidos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return renderView($response, __DIR__ . '/../Views/preparacion-pedido/index.php',
            'Preparación de Pedido', ['pedidos' => $pedidos]);
    }

    public function preparar($request, $response, $args): mixed
    {
        $nrodoc = trim($args['nrodoc']);

        $cabStmt = $this->db->pdo->prepare("
            SELECT TRIM(c.documento)                  AS nrodoc,
                   TRIM(c.prefijo)                    AS prefijo,
                   TRIM(c.codcp)                      AS codcp,
                   TRIM(c.codsuc)                     AS codsuc,
                   c.fecha                            AS fecha_raw,
                   c.fechent                          AS fechent_raw,
                   DATE_FORMAT(c.fecha,   '%d/%m/%Y') AS fecha_fmt,
                   DATE_FORMAT(c.fechent, '%d/%m/%Y') AS fecentrega_fmt,
                   TRIM(g.nombrecli)                  AS nomcli,
                   TRIM(g.codtipocli)                 AS codtipocli
            FROM   cabezamov c
            INNER  JOIN geclientes g ON c.codcp = g.codcli
            WHERE  TRIM(c.tm)      = 'PV'
              AND  TRIM(c.documento) = :doc
              AND  c.estado        = 'C'
              AND  TRIM(c.estadorm) = 'A'
            LIMIT 1
        ");
        $cabStmt->execute([':doc' => $nrodoc]);
        $pedido = $cabStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$pedido) {
            return $response->withHeader('Location', '/preparacion-pedido')->withStatus(302);
        }

        $itemsStmt = $this->db->pdo->prepare("
            SELECT TRIM(cm.registro)  AS registro,
                   TRIM(cm.codr)      AS codart,
                   TRIM(cm.descr)     AS descripcion,
                   TRIM(cm.comencpo)  AS comencpo,
                   cm.unidad,
                   cm.cantidad,
                   cm.cantent,
                   COALESCE((
                       SELECT SUM(i.cantidad)
                       FROM   itemmov i
                       WHERE  TRIM(i.tm)        = 'PV'
                         AND  TRIM(i.prefijo)   = cm.prefijo
                         AND  TRIM(i.documento) = cm.documento
                         AND  TRIM(i.registro)  = cm.registro
                   ), 0) AS total_alistado
            FROM   cuerpomov cm
            WHERE  TRIM(cm.tm)        = 'PV'
              AND  TRIM(cm.prefijo)   = :prefijo
              AND  TRIM(cm.documento) = :doc
            ORDER  BY cm.registro
        ");
        $itemsStmt->execute([':prefijo' => $pedido['prefijo'], ':doc' => $nrodoc]);
        $items = $itemsStmt->fetchAll(\PDO::FETCH_ASSOC);

        return renderView($response, __DIR__ . '/../Views/preparacion-pedido/preparar.php',
            'Preparar Pedido', ['pedido' => $pedido, 'items' => $items]);
    }

    public function guardar($request, $response, $args): mixed
    {
        $nrodoc    = trim($args['nrodoc']);
        $body      = $request->getParsedBody();
        $registros = $body['registros'] ?? [];
        $pesos     = $body['pesos']     ?? [];

        $cabStmt = $this->db->pdo->prepare("
            SELECT TRIM(c.documento) AS nrodoc,
                   TRIM(c.prefijo)   AS prefijo,
                   TRIM(c.codcp)     AS codcp,
                   TRIM(c.codsuc)    AS codsuc,
                   c.fecha, c.fechent
            FROM   cabezamov c
            WHERE  TRIM(c.tm)       = 'PV'
              AND  TRIM(c.documento) = :doc
              AND  c.estado         = 'C'
              AND  TRIM(c.estadorm) = 'A'
            LIMIT 1
        ");
        $cabStmt->execute([':doc' => $nrodoc]);
        $pedido = $cabStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$pedido) {
            return $response->withHeader('Location', '/preparacion-pedido')->withStatus(302);
        }

        $itemsStmt = $this->db->pdo->prepare("
            SELECT TRIM(cm.registro)  AS registro,
                   TRIM(cm.codr)      AS codart,
                   TRIM(cm.descr)     AS descripcion,
                   TRIM(cm.comencpo)  AS comencpo,
                   cm.unidad,
                   cm.valor,
                   TRIM(cm.bodega)    AS bodega,
                   cm.piva,
                   cm.descto
            FROM   cuerpomov cm
            WHERE  TRIM(cm.tm)        = 'PV'
              AND  TRIM(cm.prefijo)   = :prefijo
              AND  TRIM(cm.documento) = :doc
            ORDER  BY cm.registro
        ");
        $itemsStmt->execute([':prefijo' => $pedido['prefijo'], ':doc' => $nrodoc]);
        $items = $itemsStmt->fetchAll(\PDO::FETCH_ASSOC);

        // Calcular totales AP
        $lineItems   = [];
        $totalVriva  = 0.0;
        $totalValor  = 0.0;
        foreach ($items as $idx => $it) {
            $pesoVal = (float)($pesos[$idx] ?? 0);
            $valor   = (float)$it['valor'];
            $piva    = (float)$it['piva'];
            $descto  = (float)$it['descto'];

            $totalVriva += $pesoVal * $valor * ($piva / 100);
            $totalValor += $pesoVal * $valor * (1 + $piva / 100) * (1 - $descto / 100);

            $lineItems[] = [
                'numreg'   => $idx + 1,
                'registro' => (string)($idx + 1),
                'codr'     => $it['codart'],
                'descr'    => $it['descripcion'],
                'comencpo' => $it['comencpo'],
                'unidad'   => $it['unidad'],
                'cantidad' => $pesoVal,
                'valor'    => $valor,
                'bodega'   => $it['bodega'],
                'piva'     => $piva,
                'descto'   => $descto,
            ];
        }

        // Next AP document number
        $conStmt = $this->db->pdo->prepare(
            "SELECT ultmov FROM inconsemov WHERE TRIM(tipomv) = 'AP' LIMIT 1"
        );
        $conStmt->execute();
        $conRow = $conStmt->fetch(\PDO::FETCH_ASSOC);

        if ($conRow) {
            $lastNum   = (int)$conRow['ultmov'];
            $newDocNum = str_pad($lastNum + 1, 8, '0', STR_PAD_LEFT);
        } else {
            $maxStmt = $this->db->pdo->prepare(
                "SELECT COALESCE(MAX(CAST(TRIM(documento) AS UNSIGNED)), 0)
                 FROM cabezamov WHERE TRIM(tm) = 'AP'"
            );
            $maxStmt->execute();
            $lastNum   = (int)$maxStmt->fetchColumn();
            $newDocNum = str_pad($lastNum + 1, 8, '0', STR_PAD_LEFT);
            $conRow    = null;
        }

        $pdo  = $this->db->pdo;
        $hoy  = date('Y-m-d');
        $hora = date('H:i:s');
        $pdo->beginTransaction();
        try {
            $pdo->prepare("
                INSERT INTO cabezamov
                    (tm, prefijo, documento, codcp, codsuc,
                     fecha, hora, fechent, estado, estadorm,
                     vriva, valortotal,
                     docaux, tmaux, prefaux)
                VALUES
                    ('AP', '00', :doc, :codcp, :codsuc,
                     :fecha, :hora, :fechent, 'C', '',
                     :vriva, :valortotal,
                     :docaux, 'PV', :prefaux)
            ")->execute([
                ':doc'        => $newDocNum,
                ':codcp'      => $pedido['codcp'],
                ':codsuc'     => $pedido['codsuc'],
                ':fecha'      => $hoy,
                ':hora'       => $hora,
                ':fechent'    => $hoy,
                ':vriva'      => round($totalVriva, 3),
                ':valortotal' => round($totalValor, 3),
                ':docaux'     => $pedido['nrodoc'],
                ':prefaux'    => $pedido['prefijo'],
            ]);

            $insItem = $pdo->prepare("
                INSERT INTO cuerpomov
                    (tm, prefijo, documento, numreg, registro,
                     codr, descr, comencpo, unidad, cantidad, cantent,
                     valor, bodega, piva, descto,
                     docaux, tmaux, prefaux)
                VALUES
                    ('AP', '00', :doc, :numreg, :registro,
                     :codr, :descr, :comencpo, :unidad, :cantidad, 0,
                     :valor, :bodega, :piva, :descto,
                     :docaux, 'PV', :prefaux)
            ");

            foreach ($lineItems as $li) {
                $insItem->execute([
                    ':doc'      => $newDocNum,
                    ':numreg'   => $li['numreg'],
                    ':registro' => $li['registro'],
                    ':codr'     => $li['codr'],
                    ':descr'    => $li['descr'],
                    ':comencpo' => $li['comencpo'],
                    ':unidad'   => $li['unidad'],
                    ':cantidad' => $li['cantidad'],
                    ':valor'    => $li['valor'],
                    ':bodega'   => $li['bodega'],
                    ':piva'     => $li['piva'],
                    ':descto'   => $li['descto'],
                    ':docaux'   => $pedido['nrodoc'],
                    ':prefaux'  => $pedido['prefijo'],
                ]);
            }

            if ($conRow) {
                $pdo->prepare(
                    "UPDATE inconsemov SET ultmov = :num WHERE TRIM(tipomv) = 'AP'"
                )->execute([':num' => $lastNum + 1]);
            }

            $pdo->prepare("
                UPDATE cabezamov SET estadorm = 'R'
                WHERE  TRIM(tm)       = 'PV'
                  AND  TRIM(documento) = :doc
            ")->execute([':doc' => $nrodoc]);

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            return $response->withHeader('Location',
                '/preparacion-pedido/' . urlencode($nrodoc) . '/preparar'
            )->withStatus(302);
        }

        return $response->withHeader('Location', '/preparacion-pedido')->withStatus(302);
    }
}
