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

        // Para cada pedido, verificar si tiene una AP abierta (en proceso)
        foreach ($pedidos as &$p) {
            $apStmt = $this->db->pdo->prepare("
                SELECT COUNT(*) as cnt
                FROM   cabezamov c
                WHERE  TRIM(c.tm)    = 'AP'
                  AND  TRIM(c.tmaux) = 'PV'
                  AND  TRIM(c.docaux) = :docaux
                  AND  TRIM(c.prefaux) = :prefaux
                  AND  c.estado IN ('', ' ')
            ");
            $apStmt->execute([':docaux' => $p['nrodoc'], ':prefaux' => $p['prefijo']]);
            $apData = $apStmt->fetch(\PDO::FETCH_ASSOC);
            $p['en_proceso'] = ($apData['cnt'] ?? 0) > 0 ? 1 : 0;
        }

        return renderView($response, __DIR__ . '/../Views/preparacion-pedido/index.php',
            'Preparación de Pedido', ['pedidos' => $pedidos]);
    }

    public function preparar($request, $response, $args): mixed
    {
        $nrodoc = trim($args['nrodoc']);

        // Consultar el PV primero para obtener prefijo y otros datos
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
                   TRIM(g.codtipocli)                 AS codtipocli,
                   TRIM(c.estadorm)                   AS estadorm
            FROM   cabezamov c
            INNER  JOIN geclientes g ON c.codcp = g.codcli
            WHERE  TRIM(c.tm)      = 'PV'
              AND  TRIM(c.documento) = :doc
              AND  c.estado        = 'C'
            LIMIT 1
        ");
        $cabStmt->execute([':doc' => $nrodoc]);
        $pedidoBase = $cabStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$pedidoBase) {
            return $response->withHeader('Location', '/preparacion-pedido')->withStatus(302);
        }

        // Ahora con el prefijo conocido, buscar si hay AP abierta
        $apAbiertaStmt = $this->db->pdo->prepare("
            SELECT TRIM(c.documento) AS doc_ap,
                   TRIM(c.prefijo)   AS pref_ap
            FROM   cabezamov c
            WHERE  TRIM(c.tm)    = 'AP'
              AND  TRIM(c.tmaux) = 'PV'
              AND  TRIM(c.docaux) = :docaux
              AND  TRIM(c.prefaux) = :prefaux
              AND  c.estado IN ('', ' ')
            LIMIT 1
        ");
        $apAbiertaStmt->execute([':docaux' => $nrodoc, ':prefaux' => $pedidoBase['prefijo']]);
        $apAbierta = $apAbiertaStmt->fetch(\PDO::FETCH_ASSOC);

        // Si no hay AP abierta, filtrar por estadorm 'A'
        if (!$apAbierta && trim($pedidoBase['estadorm']) !== 'A') {
            return $response->withHeader('Location', '/preparacion-pedido')->withStatus(302);
        }

        $pedido = $pedidoBase;

        // Verificar si existe una AP ya creada (preparación parcial)
        $apExistenteStmt = $this->db->pdo->prepare("
            SELECT TRIM(c.documento) AS doc_ap,
                   TRIM(c.prefijo)   AS pref_ap
            FROM   cabezamov c
            WHERE  TRIM(c.tm)    = 'AP'
              AND  TRIM(c.tmaux) = 'PV'
              AND  TRIM(c.docaux) = :docaux
              AND  TRIM(c.prefaux) = :prefaux
              AND  c.estado IN ('', ' ')
            ORDER  BY c.fecha DESC, c.hora DESC
            LIMIT 1
        ");
        $apExistenteStmt->execute([':docaux' => $nrodoc, ':prefaux' => trim($pedido['prefijo'])]);
        $apExistente = $apExistenteStmt->fetch(\PDO::FETCH_ASSOC);

        // Debugging: Log AP document and prefix
        if ($apExistente) {
            error_log("AP Document: " . $apExistente['doc_ap']);
            error_log("AP Prefix: " . $apExistente['pref_ap']);
        } else {
            error_log("No existing AP found.");
        }

        // Debugging: Log the result of the $pesosAP query
        $pesosAP = [];

        // Validar si $apExistente tiene datos antes de acceder a sus índices
        if ($apExistente) {
            $apDoc = $apExistente['doc_ap'] ?? null;
            $apPref = $apExistente['pref_ap'] ?? null;

            if ($apDoc && $apPref) {
                $pesosAPStmt = $this->db->pdo->prepare("
                    SELECT TRIM(cm.registro) AS registro,
                           cm.cantidad      AS peso
                    FROM   cuerpomov cm
                    WHERE  TRIM(cm.tm)        = 'AP'
                      AND  TRIM(cm.prefijo)   = :prefijo
                      AND  TRIM(cm.documento) = :documento
                    ORDER  BY cm.numreg
                ");

                $pesosAPStmt->execute([':prefijo' => $apPref, ':documento' => $apDoc]);
                $pesosAPData = $pesosAPStmt->fetchAll(\PDO::FETCH_ASSOC);

                if ($pesosAPData) {
                    foreach ($pesosAPData as $p) {
                        if (is_array($p) && isset($p['registro'], $p['peso'])) {
                            $registroKey = trim($p['registro']);
                            $pesosAP[$registroKey] = (float)$p['peso'];
                        } else {
                            error_log("Elemento inválido en pesosAP: " . print_r($p, true));
                        }
                    }
                }
            }
        }

        // Debugging: Verificar contenido de $pesosAP
        error_log("Contenido de pesosAP: " . print_r($pesosAP, true));

        // Normalizar claves del arreglo $pesosAP
        foreach ($pesosAP as $p) {
            if (is_array($p) && isset($p['registro'], $p['peso'])) {
                $registroKey = trim($p['registro']);
                $pesosAP[$registroKey] = (float)$p['peso'];
            } else {
                error_log("Elemento inválido en pesosAP: " . print_r($p, true));
            }
        }

        // Debugging: Verificar claves normalizadas
        error_log("Claves normalizadas en pesosAP: " . print_r(array_keys($pesosAP), true));

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
            'Preparar Pedido', [
                'pedido'      => $pedido,
                'items'       => $items,
                'pesosAP'     => $pesosAP,
                'apExistente' => $apExistente
            ]);
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
                'registro' => trim($it['registro']),
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

        // Verificar si existe una AP sin cerrar para este PV
        $apExistenteStmt = $this->db->pdo->prepare("
            SELECT TRIM(c.documento) AS doc_ap,
                   TRIM(c.prefijo)   AS pref_ap,
                   TRIM(c.prefaux)   AS pref_pv
            FROM   cabezamov c
            WHERE  TRIM(c.tm)    = 'AP'
              AND  TRIM(c.tmaux) = 'PV'
              AND  TRIM(c.docaux) = :docaux
              AND  TRIM(c.prefaux) = :prefaux
              AND  c.estado IN ('', ' ')
            ORDER  BY c.fecha DESC, c.hora DESC
            LIMIT 1
        ");
        $apExistenteStmt->execute([':docaux' => $nrodoc, ':prefaux' => trim($pedido['prefijo'])]);
        $apExistente = $apExistenteStmt->fetch(\PDO::FETCH_ASSOC);

        $pdo  = $this->db->pdo;
        $hoy  = date('Y-m-d');
        $hora = date('H:i:s');
        $pdo->beginTransaction();
        try {
            if ($apExistente) {
                // Actualizar AP existente
                $docAP = $apExistente['doc_ap'];
                $prefAP = $apExistente['pref_ap'];

                // Eliminar items existentes de la AP
                $pdo->prepare("
                    DELETE FROM cuerpomov
                    WHERE TRIM(tm)        = 'AP'
                      AND TRIM(prefijo)   = :pref
                      AND TRIM(documento) = :doc
                ")->execute([':pref' => $prefAP, ':doc' => $docAP]);

                // Insertar nuevos items
                $insItem = $pdo->prepare("
                    INSERT INTO cuerpomov
                        (tm, prefijo, documento, numreg, registro,
                         codr, descr, comencpo, unidad, cantidad, cantent,
                         valor, bodega, piva, descto,
                         docaux, tmaux, prefaux)
                    VALUES
                        ('AP', :pref, :doc, :numreg, :registro,
                         :codr, :descr, :comencpo, :unidad, :cantidad, 0,
                         :valor, :bodega, :piva, :descto,
                         :docaux, 'PV', :prefaux)
                ");
                foreach ($lineItems as $li) {
                    $insItem->execute([
                        ':pref'     => $prefAP,
                        ':doc'      => $docAP,
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

                // Actualizar totales en cabezamov
                $pdo->prepare("
                    UPDATE cabezamov
                    SET    vriva = :vriva, valortotal = :valortotal,
                           fecha = :fecha, hora = :hora
                    WHERE  TRIM(tm)       = 'AP'
                      AND  TRIM(documento) = :doc
                      AND  TRIM(prefijo)   = :pref
                ")->execute([
                    ':vriva'      => round($totalVriva, 3),
                    ':valortotal' => round($totalValor, 3),
                    ':fecha'      => $hoy,
                    ':hora'       => $hora,
                    ':doc'        => $docAP,
                    ':pref'       => $prefAP,
                ]);

            } else {
                // Crear nueva AP
                // Next AP document number
                $conStmt = $pdo->prepare(
                    "SELECT ultmov FROM inconsemov WHERE TRIM(tipomv) = 'AP' LIMIT 1"
                );
                $conStmt->execute();
                $conRow = $conStmt->fetch(\PDO::FETCH_ASSOC);

                if ($conRow) {
                    $lastNum   = (int)$conRow['ultmov'];
                    $newDocNum = str_pad($lastNum + 1, 8, '0', STR_PAD_LEFT);
                } else {
                    $maxStmt = $pdo->prepare(
                        "SELECT COALESCE(MAX(CAST(TRIM(documento) AS UNSIGNED)), 0)
                         FROM cabezamov WHERE TRIM(tm) = 'AP'"
                    );
                    $maxStmt->execute();
                    $lastNum   = (int)$maxStmt->fetchColumn();
                    $newDocNum = str_pad($lastNum + 1, 8, '0', STR_PAD_LEFT);
                    $conRow    = null;
                }

                $pdo->prepare("
                    INSERT INTO cabezamov
                        (tm, prefijo, documento, codcp, codsuc,
                         fecha, hora, fechent, estado, estadorm,
                         vriva, valortotal,
                         docaux, tmaux, prefaux)
                    VALUES
                        ('AP', '00', :doc, :codcp, :codsuc,
                         :fecha, :hora, :fechent, ' ', '',
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
            }

            // NO cambiar estadorm aquí - mantener en 'A' para que siga en la lista
            // Solo se cambia a 'C' cuando se hace "Cierra Planilla"

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            return $response->withHeader('Location',
                '/preparacion-pedido/' . urlencode($nrodoc) . '/preparar'
            )->withStatus(302);
        }

        return $response->withHeader('Location', '/preparacion-pedido')->withStatus(302);
    }

    /**
     * Cierra la planilla (AP) cambiando su estado de ' ' (blanco) a 'C' (cerrado)
     */
    public function cerrar($request, $response, $args): mixed
    {
        $nrodoc = trim($args['nrodoc']);

        try {
            // Verificar que la PV exista
            $pvStmt = $this->db->pdo->prepare("
                SELECT TRIM(c.documento) AS nrodoc,
                       TRIM(c.prefijo)   AS prefijo
                FROM   cabezamov c
                WHERE  TRIM(c.tm)       = 'PV'
                  AND  TRIM(c.documento) = :doc
                LIMIT 1
            ");
            $pvStmt->execute([':doc' => $nrodoc]);
            $pv = $pvStmt->fetch(\PDO::FETCH_ASSOC);

            if (!$pv) {
                return $response->withHeader('Location', '/preparacion-pedido')->withStatus(302);
            }

            // Buscar la AP más reciente (cerrada o sin cerrar)
            $apStmt = $this->db->pdo->prepare("
                SELECT TRIM(c.documento) AS doc_ap,
                       TRIM(c.prefijo)   AS pref_ap
                FROM   cabezamov c
                WHERE  TRIM(c.tm)    = 'AP'
                  AND  TRIM(c.tmaux) = 'PV'
                  AND  TRIM(c.docaux) = :docaux
                  AND  TRIM(c.prefaux) = :prefaux
                  AND  c.estado IN ('', ' ')
                ORDER  BY c.fecha DESC, c.hora DESC
                LIMIT 1
            ");
            $apStmt->execute([':docaux' => $nrodoc, ':prefaux' => $pv['prefijo']]);
            $ap = $apStmt->fetch(\PDO::FETCH_ASSOC);

            if (!$ap) {
                // No hay AP sin cerrar
                return $response->withHeader('Location', '/preparacion-pedido')->withStatus(302);
            }

            // Cambiar estado de la AP a 'C' (cerrada)
            $this->db->pdo->prepare("
                UPDATE cabezamov
                SET    estado = 'C'
                WHERE  TRIM(tm)       = 'AP'
                  AND  TRIM(documento) = :doc
                  AND  TRIM(prefijo)   = :pref
            ")->execute([':doc' => $ap['doc_ap'], ':pref' => $ap['pref_ap']]);

            // Cambiar estadorm del PV a 'C' para marcar como completado
            $this->db->pdo->prepare("
                UPDATE cabezamov
                SET    estadorm = 'C'
                WHERE  TRIM(tm)       = 'PV'
                  AND  TRIM(documento) = :doc
            ")->execute([':doc' => $nrodoc]);

            $_SESSION['success'] = "Planilla cerrada correctamente.";
        } catch (\Exception $e) {
            $_SESSION['errors'] = ["Error al cerrar la planilla: " . $e->getMessage()];
        }

        return $response->withHeader('Location', '/preparacion-pedido')->withStatus(302);
    }
}
