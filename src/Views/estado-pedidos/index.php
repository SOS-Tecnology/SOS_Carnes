<?php
$nrodocFmt = fn(string $n): string => str_pad($n, 8, '0', STR_PAD_LEFT);

$etapas = [
    'en_alistamiento' => ['label' => 'En alistamiento',    'bg' => '#fef9c3', 'fg' => '#854d0e'],
    'listo_preparar'  => ['label' => 'Listo para preparar','bg' => '#dbeafe', 'fg' => '#1d4ed8'],
    'en_preparacion'  => ['label' => 'En preparación',     'bg' => '#fef3c7', 'fg' => '#b45309'],
    'preparado'       => ['label' => 'Preparado',          'bg' => '#dcfce7', 'fg' => '#15803d'],
    'facturado'       => ['label' => 'Facturado',          'bg' => '#e0e7ff', 'fg' => '#3730a3'],
    'otro'            => ['label' => 'Otro',               'bg' => '#e5e7eb', 'fg' => '#374151'],
];
?>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="ep-alert ep-alert-ok">✔ <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
<?php endif; ?>
<?php if (!empty($_SESSION['errors'])): ?>
    <div class="ep-alert ep-alert-err">
        <?php foreach ($_SESSION['errors'] as $e): ?>
            <div>✖ <?= htmlspecialchars($e) ?></div>
        <?php endforeach; unset($_SESSION['errors']); ?>
    </div>
<?php endif; ?>

<style>
    .ep-alert { border-radius:.5rem; padding:.6rem 1rem; margin-bottom:.8rem; font-size:.85rem; font-weight:600; }
    .ep-alert-ok  { background:#dcfce7; color:#15803d; border:1px solid #86efac; }
    .ep-alert-err { background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5; }

    .ep-header {
        background:#1a4dad; color:#fff; padding:.6rem 1.2rem;
        border-radius:.5rem .5rem 0 0; display:flex; align-items:center;
        justify-content:space-between; font-weight:600; font-size:.95rem;
    }
    .ep-header small { font-weight:400; font-size:.75rem; opacity:.85; }
    .ep-body {
        background:#e8eaf0; border:1px solid #b0b8d0; border-top:none;
        border-radius:0 0 .5rem .5rem; padding:1rem; min-height:200px;
    }
    .ep-filtros {
        background:#fff; border:1px solid #d0d8ec; border-radius:.5rem;
        padding:.6rem .9rem; margin-bottom:.9rem; display:flex;
        align-items:flex-end; gap:.9rem; flex-wrap:wrap;
        box-shadow:0 2px 8px rgba(0,0,0,.06);
    }
    .ep-campo { display:flex; flex-direction:column; gap:.2rem; }
    .ep-campo label {
        font-size:.7rem; font-weight:700; color:#1a4dad;
        letter-spacing:.02em; text-transform:uppercase;
    }
    .ep-campo input {
        border:1px solid #c3cde6; border-radius:.35rem; padding:.38rem .6rem;
        font-size:.82rem; color:#222; outline:none; min-width:150px;
    }
    .ep-campo input:focus { border-color:#1a4dad; box-shadow:0 0 0 2px rgba(26,77,173,.12); }
    .ep-campo-cliente input { min-width:260px; }
    .ep-btn-limpiar {
        background:#e5e7eb; border:1px solid #c9cdd4; border-radius:.35rem;
        padding:.38rem .8rem; font-size:.78rem; font-weight:600;
        color:#374151; cursor:pointer;
    }
    .ep-btn-limpiar:hover { background:#d7dae0; }
    .ep-info { font-size:.74rem; color:#4b5563; font-weight:600; margin-left:auto; }

    .ep-limites { display:flex; gap:.35rem; align-items:center; }
    .ep-limites .lbl-lim {
        font-size:.7rem; font-weight:700; color:#1a4dad;
        letter-spacing:.02em; text-transform:uppercase; margin-right:.2rem;
    }
    .ep-lim {
        display:inline-block; padding:.32rem .7rem; border-radius:.35rem;
        border:1px solid #c3cde6; background:#fff; color:#1a4dad;
        font-size:.76rem; font-weight:700; text-decoration:none;
    }
    .ep-lim:hover { background:#f0f4ff; }
    .ep-lim.activo { background:#1a4dad; color:#fff; border-color:#1a4dad; }

    .ep-tabla-wrap {
        background:#fff; border:1px solid #d0d8ec; border-radius:.5rem;
        box-shadow:0 2px 8px rgba(0,0,0,.06); overflow:auto;
    }
    .ep-tabla { width:100%; border-collapse:collapse; font-size:.82rem; }
    .ep-tabla thead th {
        background:#f0f4ff; color:#1a4dad; font-size:.72rem; font-weight:700;
        text-transform:uppercase; letter-spacing:.02em; text-align:left;
        padding:.55rem .8rem; border-bottom:1px solid #d0d8ec; white-space:nowrap;
    }
    .ep-tabla tbody td {
        padding:.5rem .8rem; border-bottom:1px solid #eef1f8;
        color:#222; white-space:nowrap;
    }
    .ep-tabla tbody tr:hover { background:#f7f9fe; }
    .ep-doc { font-family:monospace; font-weight:700; color:#1a2e6a; }
    .ep-cod { font-family:monospace; color:#444; }
    .ep-badge {
        display:inline-block; padding:.18rem .6rem; border-radius:999px;
        font-size:.7rem; font-weight:700; white-space:nowrap;
    }
    .ep-btn-mod {
        display:inline-flex; align-items:center; gap:.3rem;
        background:#1a4dad; color:#fff; border:none; border-radius:.35rem;
        padding:.32rem .75rem; font-size:.74rem; font-weight:700;
        cursor:pointer; text-decoration:none;
    }
    .ep-btn-mod:hover { background:#153d8a; }
    .ep-empty { text-align:center; color:#888; font-size:.85rem; padding:2rem 0; }
</style>

<div class="ep-header">
    <a href="/dashboard_home"
       class="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700"
       style="font-weight:600;color:#c3cde6 ;text-decoration:none;">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Volver
    </a>
    <span>Estado de Pedidos</span>
    <small>Ciclo PV: alistamiento → preparación → facturación</small>
</div>

<div class="ep-body">

    <div class="ep-filtros">
        <div class="ep-campo">
            <label for="ep-filtro-doc">N&deg; Documento PV</label>
            <input type="text" id="ep-filtro-doc" inputmode="numeric" autocomplete="off"
                   placeholder="Ej. 14884" oninput="epAplicarFiltros()">
        </div>
        <div class="ep-campo ep-campo-cliente">
            <label for="ep-filtro-cliente">Cliente (nombre o c&oacute;digo)</label>
            <input type="text" id="ep-filtro-cliente" list="ep-lista-clientes" autocomplete="off"
                   placeholder="Escriba el nombre del cliente..." oninput="epAplicarFiltros()">
            <datalist id="ep-lista-clientes">
                <?php
                $clientesDl = [];
                foreach ($pedidos as $p) {
                    $clientesDl[trim($p['codcli'])] = trim($p['nomcli']);
                }
                foreach ($clientesDl as $cod => $nom): ?>
                    <option value="<?= htmlspecialchars($nom) ?> (<?= htmlspecialchars($cod) ?>)"></option>
                <?php endforeach; ?>
            </datalist>
        </div>
        <button type="button" class="ep-btn-limpiar" onclick="epLimpiarFiltros()">Limpiar</button>

        <div class="ep-limites">
            <span class="lbl-lim">Mostrar:</span>
            <a href="/estado-pedidos?limite=100"  class="ep-lim <?= $limite === 100  ? 'activo' : '' ?>">Últimos 100</a>
            <a href="/estado-pedidos?limite=500"  class="ep-lim <?= $limite === 500  ? 'activo' : '' ?>">500</a>
            <a href="/estado-pedidos?limite=1000" class="ep-lim <?= $limite === 1000 ? 'activo' : '' ?>">1000</a>
            <a href="/estado-pedidos?limite=0"    class="ep-lim <?= $limite === 0    ? 'activo' : '' ?>">Todos</a>
        </div>

        <span class="ep-info" id="ep-info"><?= count($pedidos) ?> pedido(s)</span>
    </div>

    <?php if (empty($pedidos)): ?>
        <div class="ep-empty">No hay pedidos PV para mostrar.</div>
    <?php else: ?>

    <div class="ep-empty" id="ep-sin-resultados" style="display:none;">
        Ning&uacute;n pedido coincide con los filtros aplicados.
    </div>

    <div class="ep-tabla-wrap">
        <table class="ep-tabla">
            <thead>
                <tr>
                    <th>Documento</th>
                    <th>C&oacute;d. Cliente</th>
                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th>F. Entrega</th>
                    <th>Etapa</th>
                    <th>Acci&oacute;n</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pedidos as $p):
                    $et = $etapas[$p['etapa']] ?? $etapas['otro'];
                ?>
                <tr data-doc="<?= htmlspecialchars($nrodocFmt($p['nrodoc'])) ?>"
                    data-codcli="<?= htmlspecialchars(trim($p['codcli'])) ?>"
                    data-nomcli="<?= htmlspecialchars(trim($p['nomcli'])) ?>">
                    <td class="ep-doc"><?= htmlspecialchars($nrodocFmt($p['nrodoc'])) ?></td>
                    <td class="ep-cod"><?= htmlspecialchars($p['codcli']) ?></td>
                    <td><?= htmlspecialchars($p['nomcli']) ?></td>
                    <td><?= htmlspecialchars($p['fecha_fmt']) ?></td>
                    <td><?= htmlspecialchars($p['fecentrega_fmt']) ?></td>
                    <td>
                        <span class="ep-badge" style="background:<?= $et['bg'] ?>;color:<?= $et['fg'] ?>;">
                            <?= htmlspecialchars($et['label']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($p['etapa'] !== 'facturado'): ?>
                            <a class="ep-btn-mod"
                               href="/estado-pedidos/<?= urlencode($p['nrodoc']) ?>/modificar?prefijo=<?= urlencode($p['prefijo']) ?>">
                                ✎ Modificar
                            </a>
                        <?php else: ?>
                            <span style="font-size:.72rem;color:#888;">Sin modificación</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php endif; ?>
</div>

<script>
    function epAplicarFiltros() {
        const inpDoc = document.getElementById('ep-filtro-doc');
        const inpCli = document.getElementById('ep-filtro-cliente');
        if (!inpDoc || !inpCli) return;

        const doc    = inpDoc.value.replace(/\D/g, '').replace(/^0+/, '');
        const cliRaw = inpCli.value.trim().toUpperCase();

        // Si el valor viene del datalist "NOMBRE (CODIGO)", extraer el código
        const m         = cliRaw.match(/\(([^)]+)\)\s*$/);
        const cliCod    = m ? m[1].trim() : '';
        const cliNombre = m ? cliRaw.replace(/\s*\([^)]*\)\s*$/, '').trim() : cliRaw;

        let visibles = 0;
        document.querySelectorAll('.ep-tabla tbody tr').forEach(tr => {
            const trDoc = (tr.dataset.doc    || '').replace(/^0+/, '');
            const cod   = (tr.dataset.codcli || '').toUpperCase();
            const nom   = (tr.dataset.nomcli || '').toUpperCase();

            const docOk = doc === ''    || trDoc.includes(doc);
            const cliOk = cliRaw === '' ||
                (cliCod !== '' ? cod === cliCod
                               : (nom.includes(cliNombre) || cod.includes(cliNombre)));

            const show = docOk && cliOk;
            tr.style.display = show ? '' : 'none';
            if (show) visibles++;
        });

        const sinRes = document.getElementById('ep-sin-resultados');
        if (sinRes) sinRes.style.display = visibles === 0 ? '' : 'none';

        const info = document.getElementById('ep-info');
        if (info) info.textContent = visibles + ' pedido(s)';
    }

    function epLimpiarFiltros() {
        document.getElementById('ep-filtro-doc').value = '';
        document.getElementById('ep-filtro-cliente').value = '';
        epAplicarFiltros();
    }
</script>
