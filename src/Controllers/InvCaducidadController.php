<?php

namespace App\Controllers;

use Medoo\Medoo;

class InvCaducidadController
{
    private Medoo $db;

    public function __construct(Medoo $db)
    {
        $this->db = $db;
    }

    // ── Listado ───────────────────────────────────────────────────
    public function index($request, $response)
    {
        $stmt = $this->db->pdo->prepare("
            SELECT
                ic.canal,
                ic.codgrupo,
                ic.codsubg,
                ic.codc,
                ic.presentacion,
                ic.cantidad,
                TRIM(gt.nombretc)  AS canal_desc,
                TRIM(ig.nomgrupo)  AS grupo_desc,
                TRIM(is2.nomsubg)  AS subgrupo_desc,
                TRIM(gc.nombrecli)  AS cliente_desc
            FROM inv_caducidad ic
            LEFT JOIN getipoter  gt  ON TRIM(gt.codigotc)  = TRIM(ic.canal)
            LEFT JOIN ingrupos   ig  ON TRIM(ig.codgrupo)  = TRIM(ic.codgrupo)
            LEFT JOIN insubgrupo is2 ON TRIM(is2.codgrupo) = TRIM(ic.codgrupo)
                                    AND TRIM(is2.codsubg)  = TRIM(ic.codsubg)
            LEFT JOIN geclientes gc  ON TRIM(gc.codcli)    = TRIM(ic.codc)
            ORDER BY ic.canal, ic.codgrupo, ic.codsubg, ic.codc, ic.presentacion
        ");
        $stmt->execute();
        $registros = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return renderView(
            $response,
            __DIR__ . '/../Views/Inventario/InvCaducidad/index.php',
            'Caducidad en Productos',
            ['registros' => $registros]
        );
    }

    // ── Formulario crear ─────────────────────────────────────────
    public function create($request, $response)
    {
        $stmtC  = $this->db->pdo->query("SELECT TRIM(codigotc) AS codigotc, TRIM(nombretc) AS descripcion FROM getipoter ORDER BY codigotc");
        $stmtG  = $this->db->pdo->query("SELECT TRIM(codgrupo) AS codgrupo, TRIM(nomgrupo) AS descripcion FROM ingrupos ORDER BY codgrupo");
        $stmtS  = $this->db->pdo->query("SELECT TRIM(codgrupo) AS codgrupo, TRIM(codsubg) AS codsubg, TRIM(nomsubg) AS descripcion FROM insubgrupo ORDER BY codgrupo, codsubg");
        $stmtCl = $this->db->pdo->query("SELECT TRIM(codcli) AS codcli, TRIM(nombrecli) AS nombrecli FROM geclientes ORDER BY nombrecli");

        return renderView(
            $response,
            __DIR__ . '/../Views/Inventario/InvCaducidad/create.php',
            'Nueva Caducidad',
            [
                'canales'   => $stmtC->fetchAll(\PDO::FETCH_ASSOC),
                'grupos'    => $stmtG->fetchAll(\PDO::FETCH_ASSOC),
                'subgrupos' => $stmtS->fetchAll(\PDO::FETCH_ASSOC),
                'clientes'  => $stmtCl->fetchAll(\PDO::FETCH_ASSOC),
            ]
        );
    }

    // ── Guardar nuevo registro ────────────────────────────────────
    public function store($request, $response)
    {
        $data = $request->getParsedBody();

        $canal        = trim($data['canal']        ?? '');
        $codgrupo     = trim($data['codgrupo']     ?? '');
        $codsubg      = trim($data['codsubg']      ?? '');
        $codc         = trim($data['codc']         ?? '');   // vacío = genérico (NULL)
        $presentacion = trim($data['presentacion'] ?? '');
        $cantidad     = (int)($data['cantidad']    ?? 0);

        if ($canal === '' || $codgrupo === '' || $codsubg === '' || $presentacion === '') {
            $_SESSION['errors'] = ['Canal, grupo, subgrupo y presentación son obligatorios.'];
            return $response->withHeader('Location', '/inv-caducidad/create')->withStatus(302);
        }

        // Verificar duplicado considerando codc (NULL o valor)
        if ($codc !== '') {
            $existe = $this->db->pdo->prepare("
                SELECT COUNT(*) FROM inv_caducidad
                WHERE TRIM(canal) = :canal AND TRIM(codgrupo) = :codgrupo
                  AND TRIM(codsubg) = :codsubg AND TRIM(codc) = :codc
                  AND TRIM(presentacion) = :presentacion
            ");
            $existe->execute([':canal' => $canal, ':codgrupo' => $codgrupo, ':codsubg' => $codsubg,
                              ':codc' => $codc, ':presentacion' => $presentacion]);
        } else {
            $existe = $this->db->pdo->prepare("
                SELECT COUNT(*) FROM inv_caducidad
                WHERE TRIM(canal) = :canal AND TRIM(codgrupo) = :codgrupo
                  AND TRIM(codsubg) = :codsubg AND codc IS NULL
                  AND TRIM(presentacion) = :presentacion
            ");
            $existe->execute([':canal' => $canal, ':codgrupo' => $codgrupo,
                              ':codsubg' => $codsubg, ':presentacion' => $presentacion]);
        }

        if ((int)$existe->fetchColumn() > 0) {
            $_SESSION['errors'] = ['Ya existe un registro con esa combinación de campos.'];
            return $response->withHeader('Location', '/inv-caducidad/create')->withStatus(302);
        }

        $this->db->pdo->prepare("
            INSERT INTO inv_caducidad (canal, codgrupo, codsubg, codc, presentacion, cantidad)
            VALUES (:canal, :codgrupo, :codsubg, :codc, :presentacion, :cantidad)
        ")->execute([
            ':canal'        => $canal,
            ':codgrupo'     => $codgrupo,
            ':codsubg'      => $codsubg,
            ':codc'         => $codc !== '' ? $codc : null,
            ':presentacion' => $presentacion,
            ':cantidad'     => $cantidad,
        ]);

        $_SESSION['success'] = 'Registro de caducidad creado correctamente.';
        return $response->withHeader('Location', '/inv-caducidad')->withStatus(302);
    }

    // ── Formulario editar ─────────────────────────────────────────
    public function edit($request, $response, $args)
    {
        $codc = trim($args['codc'] ?? '');

        if ($codc !== '' && $codc !== '_') {
            $stmt = $this->db->pdo->prepare("
                SELECT ic.*, TRIM(gc.nombrecli) AS cliente_desc
                FROM inv_caducidad ic
                LEFT JOIN geclientes gc ON TRIM(gc.codcli) = TRIM(ic.codc)
                WHERE TRIM(ic.canal) = :canal AND TRIM(ic.codgrupo) = :codgrupo
                  AND TRIM(ic.codsubg) = :codsubg AND TRIM(ic.codc) = :codc
                  AND TRIM(ic.presentacion) = :presentacion
                LIMIT 1
            ");
            $stmt->execute([
                ':canal'        => trim($args['canal']),
                ':codgrupo'     => trim($args['codgrupo']),
                ':codsubg'      => trim($args['codsubg']),
                ':codc'         => $codc,
                ':presentacion' => trim($args['presentacion']),
            ]);
        } else {
            $stmt = $this->db->pdo->prepare("
                SELECT ic.*
                FROM inv_caducidad ic
                WHERE TRIM(ic.canal) = :canal AND TRIM(ic.codgrupo) = :codgrupo
                  AND TRIM(ic.codsubg) = :codsubg AND ic.codc IS NULL
                  AND TRIM(ic.presentacion) = :presentacion
                LIMIT 1
            ");
            $stmt->execute([
                ':canal'        => trim($args['canal']),
                ':codgrupo'     => trim($args['codgrupo']),
                ':codsubg'      => trim($args['codsubg']),
                ':presentacion' => trim($args['presentacion']),
            ]);
        }

        $registro = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$registro) {
            $_SESSION['errors'] = ['Registro no encontrado.'];
            return $response->withHeader('Location', '/inv-caducidad')->withStatus(302);
        }

        return renderView(
            $response,
            __DIR__ . '/../Views/Inventario/InvCaducidad/edit.php',
            'Editar Caducidad',
            ['registro' => $registro]
        );
    }

    // ── Actualizar registro ───────────────────────────────────────
    public function update($request, $response, $args)
    {
        $data     = $request->getParsedBody();
        $cantidad = (int)($data['cantidad'] ?? 0);
        $codc     = trim($args['codc'] ?? '');

        if ($codc !== '' && $codc !== '_') {
            $this->db->pdo->prepare("
                UPDATE inv_caducidad
                SET cantidad = :cantidad
                WHERE TRIM(canal) = :canal AND TRIM(codgrupo) = :codgrupo
                  AND TRIM(codsubg) = :codsubg AND TRIM(codc) = :codc
                  AND TRIM(presentacion) = :presentacion
            ")->execute([
                ':cantidad'     => $cantidad,
                ':canal'        => trim($args['canal']),
                ':codgrupo'     => trim($args['codgrupo']),
                ':codsubg'      => trim($args['codsubg']),
                ':codc'         => $codc,
                ':presentacion' => trim($args['presentacion']),
            ]);
        } else {
            $this->db->pdo->prepare("
                UPDATE inv_caducidad
                SET cantidad = :cantidad
                WHERE TRIM(canal) = :canal AND TRIM(codgrupo) = :codgrupo
                  AND TRIM(codsubg) = :codsubg AND codc IS NULL
                  AND TRIM(presentacion) = :presentacion
            ")->execute([
                ':cantidad'     => $cantidad,
                ':canal'        => trim($args['canal']),
                ':codgrupo'     => trim($args['codgrupo']),
                ':codsubg'      => trim($args['codsubg']),
                ':presentacion' => trim($args['presentacion']),
            ]);
        }

        $_SESSION['success'] = 'Registro actualizado correctamente.';
        return $response->withHeader('Location', '/inv-caducidad')->withStatus(302);
    }

    // ── Ver detalle ───────────────────────────────────────────────
    public function show($request, $response, $args)
    {
        $codc = trim($args['codc'] ?? '');

        if ($codc !== '' && $codc !== '_') {
            $stmt = $this->db->pdo->prepare("
                SELECT ic.*,
                       TRIM(gt.nombretc)  AS canal_desc,
                       TRIM(ig.nomgrupo)  AS grupo_desc,
                       TRIM(is2.nomsubg)  AS subgrupo_desc,
                       TRIM(gc.nombrecli)  AS cliente_desc
                FROM inv_caducidad ic
                LEFT JOIN getipoter  gt  ON TRIM(gt.codigotc)  = TRIM(ic.canal)
                LEFT JOIN ingrupos   ig  ON TRIM(ig.codgrupo)  = TRIM(ic.codgrupo)
                LEFT JOIN insubgrupo is2 ON TRIM(is2.codgrupo) = TRIM(ic.codgrupo)
                                        AND TRIM(is2.codsubg)  = TRIM(ic.codsubg)
                LEFT JOIN geclientes gc  ON TRIM(gc.codcli)    = TRIM(ic.codc)
                WHERE TRIM(ic.canal) = :canal AND TRIM(ic.codgrupo) = :codgrupo
                  AND TRIM(ic.codsubg) = :codsubg AND TRIM(ic.codc) = :codc
                  AND TRIM(ic.presentacion) = :presentacion
                LIMIT 1
            ");
            $stmt->execute([
                ':canal'        => trim($args['canal']),
                ':codgrupo'     => trim($args['codgrupo']),
                ':codsubg'      => trim($args['codsubg']),
                ':codc'         => $codc,
                ':presentacion' => trim($args['presentacion']),
            ]);
        } else {
            $stmt = $this->db->pdo->prepare("
                SELECT ic.*,
                       TRIM(gt.nombretc)  AS canal_desc,
                       TRIM(ig.nomgrupo)  AS grupo_desc,
                       TRIM(is2.nomsubg)  AS subgrupo_desc
                FROM inv_caducidad ic
                LEFT JOIN getipoter  gt  ON TRIM(gt.codigotc)  = TRIM(ic.canal)
                LEFT JOIN ingrupos   ig  ON TRIM(ig.codgrupo)  = TRIM(ic.codgrupo)
                LEFT JOIN insubgrupo is2 ON TRIM(is2.codgrupo) = TRIM(ic.codgrupo)
                                        AND TRIM(is2.codsubg)  = TRIM(ic.codsubg)
                WHERE TRIM(ic.canal) = :canal AND TRIM(ic.codgrupo) = :codgrupo
                  AND TRIM(ic.codsubg) = :codsubg AND ic.codc IS NULL
                  AND TRIM(ic.presentacion) = :presentacion
                LIMIT 1
            ");
            $stmt->execute([
                ':canal'        => trim($args['canal']),
                ':codgrupo'     => trim($args['codgrupo']),
                ':codsubg'      => trim($args['codsubg']),
                ':presentacion' => trim($args['presentacion']),
            ]);
        }

        $registro = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$registro) {
            $_SESSION['errors'] = ['Registro no encontrado.'];
            return $response->withHeader('Location', '/inv-caducidad')->withStatus(302);
        }

        return renderView(
            $response,
            __DIR__ . '/../Views/Inventario/InvCaducidad/show.php',
            'Detalle Caducidad',
            ['registro' => $registro]
        );
    }

    // ── Eliminar ──────────────────────────────────────────────────
    public function delete($request, $response, $args)
    {
        $codc = trim($args['codc'] ?? '');

        if ($codc !== '' && $codc !== '_') {
            $this->db->pdo->prepare("
                DELETE FROM inv_caducidad
                WHERE TRIM(canal) = :canal AND TRIM(codgrupo) = :codgrupo
                  AND TRIM(codsubg) = :codsubg AND TRIM(codc) = :codc
                  AND TRIM(presentacion) = :presentacion
            ")->execute([
                ':canal'        => trim($args['canal']),
                ':codgrupo'     => trim($args['codgrupo']),
                ':codsubg'      => trim($args['codsubg']),
                ':codc'         => $codc,
                ':presentacion' => trim($args['presentacion']),
            ]);
        } else {
            $this->db->pdo->prepare("
                DELETE FROM inv_caducidad
                WHERE TRIM(canal) = :canal AND TRIM(codgrupo) = :codgrupo
                  AND TRIM(codsubg) = :codsubg AND codc IS NULL
                  AND TRIM(presentacion) = :presentacion
            ")->execute([
                ':canal'        => trim($args['canal']),
                ':codgrupo'     => trim($args['codgrupo']),
                ':codsubg'      => trim($args['codsubg']),
                ':presentacion' => trim($args['presentacion']),
            ]);
        }

        $_SESSION['success'] = 'Registro eliminado correctamente.';
        return $response->withHeader('Location', '/inv-caducidad')->withStatus(302);
    }
}