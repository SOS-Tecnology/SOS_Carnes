<?php

namespace App\Controllers;

use Medoo\Medoo;

class PreparacionPedidoController
{
    public function __construct(private Medoo $db) {}

    // ─────────────────────────────────────────────────────────────────────────
    // INDEX — lista de pedidos agrupados por cliente
    // ─────────────────────────────────────────────────────────────────────────
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
              AND  c.estado   in ('O')
              AND  TRIM(c.estadorm) = 'A'
              {$whereExtra}
            ORDER  BY g.nombrecli ASC, c.fechent ASC
        ");
        $params = $codtipocli !== '' ? [':codtipocli' => $codtipocli] : [];
        $stmt->execute($params);
        $pedidos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Para cada pedido, verificar si tiene AP abierta
        foreach ($pedidos as &$p) {
            $apStmt = $this->db->pdo->prepare("
                SELECT COUNT(*) as cnt
                FROM   cabezamov c
                WHERE  TRIM(c.tm)     = 'AP'
                  AND  TRIM(c.tmaux)  = 'PV'
                  AND  TRIM(c.docaux) = :docaux
                  AND  TRIM(c.prefaux)= :prefaux
                  AND  c.estado IN ('', ' ')
            ");
            $apStmt->execute([':docaux' => $p['nrodoc'], ':prefaux' => $p['prefijo']]);
            $apData = $apStmt->fetch(\PDO::FETCH_ASSOC);
            $p['en_proceso'] = ($apData['cnt'] ?? 0) > 0 ? 1 : 0;
        }

        return renderView(
            $response,
            __DIR__ . '/../Views/preparacion-pedido/index.php',
            'Preparación de Pedido',
            ['pedidos' => $pedidos]
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // INTEGRAR — recibe docs separados por coma y redirige a preparar integrado
    // GET /preparacion-pedido/integrar?docs=X,Y&prefs=A,B
    // ─────────────────────────────────────────────────────────────────────────
    public function integrar($request, $response): mixed
    {
        $params = $request->getQueryParams();
        $docsRaw  = trim($params['docs']  ?? '');
        $prefsRaw = trim($params['prefs'] ?? '');

        if ($docsRaw === '') {
            return $response->withHeader('Location', '/preparacion-pedido')->withStatus(302);
        }

        $docs  = array_map('trim', explode(',', $docsRaw));
        $prefs = array_map('trim', explode(',', $prefsRaw));

        // El primer doc es el "pedido base" (ancla de la AP)
        $docBase  = $docs[0];
        $prefBase = $prefs[0] ?? '00';

        $docsParam  = urlencode(implode(',', $docs));
        $prefsParam = urlencode(implode(',', $prefs));

        return $response->withHeader(
            'Location',
            '/preparacion-pedido/' . urlencode($docBase) . '/preparar'
            . '?integrado=1&docs=' . $docsParam . '&prefs=' . $prefsParam
        )->withStatus(302);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PREPARAR — muestra el formulario de pesajes
    // ─────────────────────────────────────────────────────────────────────────
    public function preparar($request, $response, $args): mixed
    {
        $nrodoc     = trim($args['nrodoc']);
        $qp         = $request->getQueryParams();
        $esIntegrado = !empty($qp['integrado']);
        $docsRaw    = trim($qp['docs']  ?? $nrodoc);
        $prefsRaw   = trim($qp['prefs'] ?? '');

        $docs  = array_filter(array_map('trim', explode(',', $docsRaw)));
        $prefs = array_filter(array_map('trim', explode(',', $prefsRaw)));

        if (empty($docs)) {
            $docs = [$nrodoc];
        }

        // ── Pedido base (primer doc) ──────────────────────────────────────
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
            WHERE  TRIM(c.tm)       = 'PV'
              AND  TRIM(c.documento) = :doc
              AND  c.estado         in ('O')
            LIMIT 1
        ");
        $cabStmt->execute([':doc' => $docs[0]]);
        $pedidoBase = $cabStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$pedidoBase) {
            return $response->withHeader('Location', '/preparacion-pedido')->withStatus(302);
        }

        // ── Verificar AP abierta del doc base ────────────────────────────
        $apAbiertaStmt = $this->db->pdo->prepare("
            SELECT TRIM(c.documento) AS doc_ap, TRIM(c.prefijo) AS pref_ap
            FROM   cabezamov c
            WHERE  TRIM(c.tm)     = 'AP'
              AND  TRIM(c.tmaux)  = 'PV'
              AND  TRIM(c.docaux) = :docaux
              AND  TRIM(c.prefaux)= :prefaux
              AND  c.estado IN ('', ' ')
            LIMIT 1
        ");
        $apAbiertaStmt->execute([':docaux' => $docs[0], ':prefaux' => $pedidoBase['prefijo']]);
        $apAbierta = $apAbiertaStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$apAbierta && trim($pedidoBase['estadorm']) !== 'A') {
            return $response->withHeader('Location', '/preparacion-pedido')->withStatus(302);
        }

        $pedido = $pedidoBase;

        // ── Pedidos integrados (todos los docs) ──────────────────────────
        $pedidosIntegrados = [];
        foreach ($docs as $i => $doc) {
            $pref = $prefs[$i] ?? $pedidoBase['prefijo'];
            $piStmt = $this->db->pdo->prepare("
                SELECT TRIM(c.documento) AS nrodoc,
                       TRIM(c.prefijo)   AS prefijo,
                       DATE_FORMAT(c.fechent,'%d/%m/%Y') AS fecentrega_fmt
                FROM   cabezamov c
                WHERE  TRIM(c.tm)       = 'PV'
                  AND  TRIM(c.documento) = :doc
                  AND  c.estado         in ('O')
                LIMIT 1
            ");
            $piStmt->execute([':doc' => $doc]);
            $pi = $piStmt->fetch(\PDO::FETCH_ASSOC);
            if ($pi) {
                $pedidosIntegrados[] = $pi;
            }
        }

        // ── Pesos AP existente ────────────────────────────────────────────
        $apExistenteStmt = $this->db->pdo->prepare("
            SELECT TRIM(c.documento) AS doc_ap, TRIM(c.prefijo) AS pref_ap
            FROM   cabezamov c
            WHERE  TRIM(c.tm)     = 'AP'
              AND  TRIM(c.tmaux)  = 'PV'
              AND  TRIM(c.docaux) = :docaux
              AND  TRIM(c.prefaux)= :prefaux
              AND  c.estado IN ('', ' ')
            ORDER  BY c.fecha DESC, c.hora DESC
            LIMIT 1
        ");
        $apExistenteStmt->execute([':docaux' => $docs[0], ':prefaux' => trim($pedido['prefijo'])]);
        $apExistente = $apExistenteStmt->fetch(\PDO::FETCH_ASSOC);

        $pesosAP = [];
        if ($apExistente) {
            $pesosAPStmt = $this->db->pdo->prepare("
                SELECT TRIM(cm.registro) AS registro, cm.cantidad AS peso
                FROM   cuerpomov cm
                WHERE  TRIM(cm.tm)        = 'AP'
                  AND  TRIM(cm.prefijo)   = :prefijo
                  AND  TRIM(cm.documento) = :documento
                ORDER  BY cm.numreg
            ");
            $pesosAPStmt->execute([':prefijo' => $apExistente['pref_ap'], ':documento' => $apExistente['doc_ap']]);
            foreach ($pesosAPStmt->fetchAll(\PDO::FETCH_ASSOC) as $p) {
                $pesosAP[trim($p['registro'])] = (float)$p['peso'];
            }
        }

        // ── Ítems: uno o varios pedidos ───────────────────────────────────
        $items = [];
        foreach ($docs as $i => $doc) {
            $pref = $prefs[$i] ?? $pedidoBase['prefijo'];

            // Buscar prefijo real si no fue pasado
            if ($pref === '') {
                $pfStmt = $this->db->pdo->prepare("
                    SELECT TRIM(prefijo) AS prefijo FROM cabezamov
                    WHERE TRIM(tm)='PV' AND TRIM(documento)=:doc LIMIT 1");
                $pfStmt->execute([':doc' => $doc]);
                $pfRow = $pfStmt->fetch(\PDO::FETCH_ASSOC);
                $pref  = $pfRow['prefijo'] ?? '00';
            }

            $itemsStmt = $this->db->pdo->prepare("
                SELECT TRIM(cm.registro) AS registro,
                       TRIM(cm.codr)     AS codart,
                       TRIM(cm.descr)    AS descripcion,
                       TRIM(cm.comencpo) AS comencpo,
                       TRIM(cm.lote)     AS lote,
                       cm.unidad,
                       cm.cantidad,
                       cm.cantent,
                       TRIM(cm.documento) AS nrodoc_origen,
                       TRIM(cm.prefijo)   AS prefijo_origen,
                       COALESCE((
                           SELECT SUM(i2.cantidad)
                           FROM   itemmov i2
                           WHERE  TRIM(i2.tm)        = 'PV'
                             AND  TRIM(i2.prefijo)   = cm.prefijo
                             AND  TRIM(i2.documento) = cm.documento
                             AND  TRIM(i2.registro)  = cm.registro
                       ), 0) AS total_alistado
                FROM   cuerpomov cm
                WHERE  TRIM(cm.tm)        = 'PV'
                  AND  TRIM(cm.prefijo)   = :prefijo
                  AND  TRIM(cm.documento) = :doc
                ORDER  BY cm.registro
            ");
            $itemsStmt->execute([':prefijo' => $pref, ':doc' => $doc]);
            $docItems = $itemsStmt->fetchAll(\PDO::FETCH_ASSOC);

            // Cargar lotes de itemmov para cada ítem
            foreach ($docItems as &$it) {
                $lotesStmt = $this->db->pdo->prepare("
                    SELECT TRIM(i.registro)  AS registro,
                           TRIM(i.lote)      AS lote,
                           i.cantidad,
                           i.presentacion
                    FROM   itemmov i
                    WHERE  TRIM(i.tm)        = 'PV'
                      AND  TRIM(i.prefijo)   = :prefijo
                      AND  TRIM(i.documento) = :doc
                      AND  TRIM(i.registro)  = :reg
                    ORDER  BY i.hora ASC
                ");
                $lotesStmt->execute([
                    ':prefijo' => $pref,
                    ':doc'     => $doc,
                    ':reg'     => trim($it['registro'])
                ]);
                $lotes = $lotesStmt->fetchAll(\PDO::FETCH_ASSOC);
                $it['lotes'] = $lotes;

                // ── Presentación del ítem: valor del último lote registrado ──
                $lastLote = !empty($lotes) ? end($lotes) : null;
                $presInt  = (int)($lastLote['presentacion'] ?? 1);
                // Mapeo entero → valor exacto del enum en inv_caducidad
                $presEnumMap = [
                    1 => 'Refrigeración',
                    2 => 'Congelación',
                ];
                $it['presentacion_label'] = $presEnumMap[$presInt] ?? 'Refrigeración';
                $it['presentacion_int']   = $presInt;

                // ── Días de vencimiento desde inv_caducidad ──────────────────
                // Obtener codgrupo/codsubg del artículo desde inrefinv
                $refStmt = $this->db->pdo->prepare("
                    SELECT TRIM(r.codgrupo) AS codgrupo,
                           TRIM(r.codsubg)  AS codsubg
                    FROM   inrefinv r
                    WHERE  TRIM(r.codr) = :codr
                    LIMIT 1
                ");
                $refStmt->execute([':codr' => trim($it['codart'])]);
                $ref = $refStmt->fetch(\PDO::FETCH_ASSOC);

                $it['dias_vencimiento'] = null; // null => N/A

                if ($ref && $ref['codgrupo'] !== '' && $ref['codsubg'] !== '') {
                    $canal    = trim($pedidoBase['codtipocli'] ?? '');
                    $codcli   = trim($pedidoBase['codcp'] ?? '');
                    $presLabel = $it['presentacion_label']; // 'Refrigeración' o 'Congelación'

                    // 1. Buscar con cliente específico
                    $cadStmt = $this->db->pdo->prepare("
                        SELECT ic.cantidad
                        FROM   inv_caducidad ic
                        WHERE  TRIM(ic.canal)         = :canal
                          AND  TRIM(ic.codgrupo)      = :codgrupo
                          AND  TRIM(ic.codsubg)       = :codsubg
                          AND  TRIM(ic.presentacion)  = :presentacion
                          AND  TRIM(ic.codc)          = :codc
                        LIMIT 1
                    ");
                    $cadStmt->execute([
                        ':canal'        => $canal,
                        ':codgrupo'     => $ref['codgrupo'],
                        ':codsubg'      => $ref['codsubg'],
                        ':presentacion' => $presLabel,
                        ':codc'         => $codcli,
                    ]);
                    $cadRow = $cadStmt->fetch(\PDO::FETCH_ASSOC);

                    // 2. Si no existe, buscar genérico (codc IS NULL)
                    if (!$cadRow) {
                        $cadGenStmt = $this->db->pdo->prepare("
                            SELECT ic.cantidad
                            FROM   inv_caducidad ic
                            WHERE  TRIM(ic.canal)        = :canal
                              AND  TRIM(ic.codgrupo)     = :codgrupo
                              AND  TRIM(ic.codsubg)      = :codsubg
                              AND  TRIM(ic.presentacion) = :presentacion
                              AND  ic.codc IS NULL
                            LIMIT 1
                        ");
                        $cadGenStmt->execute([
                            ':canal'        => $canal,
                            ':codgrupo'     => $ref['codgrupo'],
                            ':codsubg'      => $ref['codsubg'],
                            ':presentacion' => $presLabel,
                        ]);
                        $cadRow = $cadGenStmt->fetch(\PDO::FETCH_ASSOC);
                    }

                    if ($cadRow) {
                        $it['dias_vencimiento'] = (int)$cadRow['cantidad'];
                    }
                }
            }
            unset($it);

            $items = array_merge($items, $docItems);
        }

        return renderView(
            $response,
            __DIR__ . '/../Views/preparacion-pedido/preparar.php',
            'Preparar Pedido',
            [
                'pedido'            => $pedido,
                'pedidosIntegrados' => $pedidosIntegrados,
                'items'             => $items,
                'pesosAP'           => $pesosAP,
                'apExistente'       => $apExistente,
            ]
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GUARDAR — procesa el formulario y genera/actualiza AP
    // ─────────────────────────────────────────────────────────────────────────
    public function guardar($request, $response, $args): mixed
    {
        $nrodoc  = trim($args['nrodoc']);
        $body    = $request->getParsedBody();
        $accion  = $body['accion'] ?? '';

        if ($accion === 'cerrar') {
            return $this->cerrar($request, $response, $args);
        }

        $registros       = $body['registros']       ?? [];
        $pesos           = $body['pesos']            ?? [];
        $nrodocOrigenes  = $body['nrodoc_origen']    ?? [];
        $docsIntegrados  = $body['docs_integrados']  ?? [];
        $prefsIntegrados = $body['prefs_integrados'] ?? [];
        $esIntegrado     = !empty($body['integrado']);

        // ── Pedido base ───────────────────────────────────────────────────
        $cabStmt = $this->db->pdo->prepare("
            SELECT TRIM(c.documento) AS nrodoc,
                   TRIM(c.prefijo)   AS prefijo,
                   TRIM(c.codcp)     AS codcp,
                   TRIM(c.codsuc)    AS codsuc,
                   c.fecha, c.fechent
            FROM   cabezamov c
            WHERE  TRIM(c.tm)       = 'PV'
              AND  TRIM(c.documento) = :doc
              AND  c.estado          = 'O'
            LIMIT 1
        ");
        $cabStmt->execute([':doc' => $nrodoc]);
        $pedido = $cabStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$pedido) {
            return $response->withHeader('Location', '/preparacion-pedido')->withStatus(302);
        }

        // ── Reconstruir ítems desde todos los docs implicados ─────────────
        $allDocs  = $esIntegrado && !empty($docsIntegrados) ? $docsIntegrados  : [$nrodoc];
        $allPrefs = $esIntegrado && !empty($prefsIntegrados) ? $prefsIntegrados : [$pedido['prefijo']];

        $allItems = [];
        foreach ($allDocs as $i => $doc) {
            $pref = $allPrefs[$i] ?? $pedido['prefijo'];
            $iStmt = $this->db->pdo->prepare("
                SELECT TRIM(cm.registro) AS registro,
                       TRIM(cm.codr)     AS codart,
                       TRIM(cm.descr)    AS descripcion,
                       TRIM(cm.comencpo) AS comencpo,
                       cm.unidad, cm.valor, TRIM(cm.bodega) AS bodega,
                       cm.piva, cm.descto,
                       TRIM(cm.documento) AS nrodoc_origen
                FROM   cuerpomov cm
                WHERE  TRIM(cm.tm)        = 'PV'
                  AND  TRIM(cm.prefijo)   = :prefijo
                  AND  TRIM(cm.documento) = :doc
                ORDER  BY cm.registro
            ");
            $iStmt->execute([':prefijo' => $pref, ':doc' => $doc]);
            foreach ($iStmt->fetchAll(\PDO::FETCH_ASSOC) as $it) {
                $allItems[] = $it;
            }
        }

        // ── Armar lineItems con los pesos enviados ────────────────────────
        $lineItems  = [];
        $totalVriva = 0.0;
        $totalValor = 0.0;

        foreach ($allItems as $idx => $it) {
            $pesoVal = (float)($pesos[$idx] ?? 0);
            $valor   = (float)$it['valor'];
            $piva    = (float)$it['piva'];
            $descto  = (float)$it['descto'];

            $totalVriva += $pesoVal * $valor * ($piva / 100);
            $totalValor += $pesoVal * $valor * (1 + $piva / 100) * (1 - $descto / 100);

            $lineItems[] = [
                'numreg'       => $idx + 1,
                'registro'     => trim($it['registro']),
                'codr'         => $it['codart'],
                'descr'        => $it['descripcion'],
                'comencpo'     => $it['comencpo'],
                'unidad'       => $it['unidad'],
                'cantidad'     => $pesoVal,
                'valor'        => $valor,
                'bodega'       => $it['bodega'],
                'piva'         => $piva,
                'descto'       => $descto,
                'docaux'       => $it['nrodoc_origen'] ?? $nrodoc,
                'prefaux'      => $pedido['prefijo'],
            ];
        }

        // ── AP existente (solo sobre el doc base) ─────────────────────────
        $apExistenteStmt = $this->db->pdo->prepare("
            SELECT TRIM(c.documento) AS doc_ap, TRIM(c.prefijo) AS pref_ap
            FROM   cabezamov c
            WHERE  TRIM(c.tm)     = 'AP'
              AND  TRIM(c.tmaux)  = 'PV'
              AND  TRIM(c.docaux) = :docaux
              AND  TRIM(c.prefaux)= :prefaux
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
                // ── Actualizar AP existente ───────────────────────────────
                $docAP  = $apExistente['doc_ap'];
                $prefAP = $apExistente['pref_ap'];

                $pdo->prepare("
                    DELETE FROM cuerpomov
                    WHERE TRIM(tm)='AP' AND TRIM(prefijo)=:pref AND TRIM(documento)=:doc
                ")->execute([':pref' => $prefAP, ':doc' => $docAP]);

                $insItem = $pdo->prepare("
                    INSERT INTO cuerpomov
                        (tm, prefijo, documento, numreg, registro,
                         codr, descr, comencpo, unidad, cantidad, cantent,
                         valor, bodega, piva, descto, docaux, tmaux, prefaux)
                    VALUES
                        ('AP', :pref, :doc, :numreg, :registro,
                         :codr, :descr, :comencpo, :unidad, :cantidad, 0,
                         :valor, :bodega, :piva, :descto, :docaux, 'PV', :prefaux)
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
                        ':docaux'   => $li['docaux'],
                        ':prefaux'  => $li['prefaux'],
                    ]);
                }

                $pdo->prepare("
                    UPDATE cabezamov
                    SET vriva=:vriva, valortotal=:valortotal, fecha=:fecha, hora=:hora
                    WHERE TRIM(tm)='AP' AND TRIM(documento)=:doc AND TRIM(prefijo)=:pref
                ")->execute([
                    ':vriva'      => round($totalVriva, 3),
                    ':valortotal' => round($totalValor, 3),
                    ':fecha'      => $hoy,
                    ':hora'       => $hora,
                    ':doc'        => $docAP,
                    ':pref'       => $prefAP,
                ]);

            } else {
                // ── Crear nueva AP ────────────────────────────────────────
                $conStmt = $pdo->prepare("SELECT ultmov FROM inconsemov WHERE TRIM(tipomv)='AP' LIMIT 1");
                $conStmt->execute();
                $conRow = $conStmt->fetch(\PDO::FETCH_ASSOC);

                if ($conRow) {
                    $lastNum   = (int)$conRow['ultmov'];
                    $newDocNum = str_pad($lastNum + 1, 8, '0', STR_PAD_LEFT);
                } else {
                    $maxStmt = $pdo->prepare("
                        SELECT COALESCE(MAX(CAST(TRIM(documento) AS UNSIGNED)),0)
                        FROM cabezamov WHERE TRIM(tm)='AP'");
                    $maxStmt->execute();
                    $lastNum   = (int)$maxStmt->fetchColumn();
                    $newDocNum = str_pad($lastNum + 1, 8, '0', STR_PAD_LEFT);
                    $conRow    = null;
                }

                $pdo->prepare("
                    INSERT INTO cabezamov
                        (tm, prefijo, documento, codcp, codsuc,
                         fecha, hora, fechent, estado, estadorm,
                         vriva, valortotal, docaux, tmaux, prefaux)
                    VALUES
                        ('AP','00',:doc,:codcp,:codsuc,
                         :fecha,:hora,:fechent,' ','',
                         :vriva,:valortotal,
                         :docaux,'PV',:prefaux)
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
                         valor, bodega, piva, descto, docaux, tmaux, prefaux)
                    VALUES
                        ('AP','00',:doc,:numreg,:registro,
                         :codr,:descr,:comencpo,:unidad,:cantidad, 0,
                         :valor,:bodega,:piva,:descto,:docaux,'PV',:prefaux)
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
                        ':docaux'   => $li['docaux'],
                        ':prefaux'  => $li['prefaux'],
                    ]);
                }

                if ($conRow) {
                    $pdo->prepare("UPDATE inconsemov SET ultmov=:num WHERE TRIM(tipomv)='AP'")
                        ->execute([':num' => $lastNum + 1]);
                }
            }

            $pdo->commit();

        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log('PreparacionPedidoController::guardar error: ' . $e->getMessage());
            return $response->withHeader(
                'Location',
                '/preparacion-pedido/' . urlencode($nrodoc) . '/preparar'
            )->withStatus(302);
        }

        $extraParams = '';
        if ($esIntegrado && !empty($docsIntegrados)) {
            $extraParams = '&integrado=1'
                . '&docs='  . urlencode(implode(',', $allDocs))
                . '&prefs=' . urlencode(implode(',', $allPrefs));
        }

        return $response->withHeader(
            'Location',
            '/preparacion-pedido/' . urlencode($nrodoc) . '/preparar?modalCierre=1' . $extraParams
        )->withStatus(302);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CERRAR — cambia AP a 'C' y PV(s) a estadorm='R'
    // ─────────────────────────────────────────────────────────────────────────
    public function cerrar($request, $response, $args): mixed
    {
        $nrodoc  = trim($args['nrodoc']);
        $body    = $request->getParsedBody();
        $prefijo = trim($body['prefijo'] ?? '');
        $docsIntegrados  = $body['docs_integrados']  ?? [];
        $prefsIntegrados = $body['prefs_integrados'] ?? [];

        if ($prefijo === '') {
            $_SESSION['errors'] = ['Falta el prefijo del pedido.'];
            return $response->withHeader('Location', '/preparacion-pedido')->withStatus(302);
        }

        try {
            // Verificar PV base
            $pvStmt = $this->db->pdo->prepare("
                SELECT TRIM(c.documento) AS nrodoc, TRIM(c.prefijo) AS prefijo
                FROM   cabezamov c
                WHERE  TRIM(c.tm)='PV' AND TRIM(c.prefijo)=:pref AND TRIM(c.documento)=:doc
                LIMIT 1
            ");
            $pvStmt->execute([':doc' => $nrodoc, ':pref' => $prefijo]);
            $pv = $pvStmt->fetch(\PDO::FETCH_ASSOC);

            if (!$pv) {
                return $response->withHeader('Location', '/preparacion-pedido')->withStatus(302);
            }

            // AP más reciente sin cerrar
            $apStmt = $this->db->pdo->prepare("
                SELECT TRIM(c.documento) AS doc_ap, TRIM(c.prefijo) AS pref_ap
                FROM   cabezamov c
                WHERE  TRIM(c.tm)='AP' AND TRIM(c.tmaux)='PV'
                  AND  TRIM(c.docaux)=:docaux AND TRIM(c.prefaux)=:prefaux
                  AND  c.estado IN ('',' ')
                ORDER  BY c.fecha DESC, c.hora DESC
                LIMIT 1
            ");
            $apStmt->execute([':docaux' => $nrodoc, ':prefaux' => $pv['prefijo']]);
            $ap = $apStmt->fetch(\PDO::FETCH_ASSOC);

            if (!$ap) {
                return $response->withHeader('Location', '/preparacion-pedido')->withStatus(302);
            }

            // Cerrar AP
            $this->db->pdo->prepare("
                UPDATE cabezamov SET estado='C'
                WHERE TRIM(tm)='AP' AND TRIM(documento)=:doc AND TRIM(prefijo)=:pref
            ")->execute([':doc' => $ap['doc_ap'], ':pref' => $ap['pref_ap']]);

            // Marcar todos los PV implicados como 'R'
            $allDocs  = !empty($docsIntegrados)  ? $docsIntegrados  : [$nrodoc];
            $allPrefs = !empty($prefsIntegrados) ? $prefsIntegrados : [$prefijo];

            foreach ($allDocs as $i => $doc) {
                $pref = $allPrefs[$i] ?? $prefijo;
                $this->db->pdo->prepare("
                    UPDATE cabezamov SET estadorm='R'
                    WHERE TRIM(tm)='PV' AND TRIM(documento)=:doc AND TRIM(prefijo)=:pref
                ")->execute([':doc' => $doc, ':pref' => $pref]);
            }

            $_SESSION['success'] = "Planilla cerrada correctamente.";

        } catch (\Exception $e) {
            $_SESSION['errors'] = ["Error al cerrar la planilla: " . $e->getMessage()];
        }

        return $response->withHeader('Location', '/preparacion-pedido')->withStatus(302);
    }
}