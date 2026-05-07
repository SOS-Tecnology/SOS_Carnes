<?php
$nrodocFmt = fn(string $n): string => str_pad($n, 8, '0', STR_PAD_LEFT);

// Agrupar pedidos por cliente
$pedidosPorCliente = [];
foreach ($pedidos as $p) {
    $key = $p['codtipocli'] . '||' . $p['nomcli'];
    $pedidosPorCliente[$key][] = $p;
}
?>

<style>
    .pp2-header {
        background: #1a4dad;
        color: #fff;
        padding: .6rem 1.2rem;
        border-radius: .5rem .5rem 0 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-weight: 600;
        font-size: .95rem;
    }
    .pp2-body {
        background: #e8eaf0;
        border: 1px solid #b0b8d0;
        border-top: none;
        border-radius: 0 0 .5rem .5rem;
        padding: 1rem;
        min-height: 200px;
    }
    .cliente-bloque {
        background: #fff;
        border: 1px solid #d0d8ec;
        border-radius: .5rem;
        margin-bottom: .9rem;
        box-shadow: 0 2px 8px rgba(0,0,0,.06);
        overflow: hidden;
    }
    .cliente-bloque:last-child { margin-bottom: 0; }
    .cliente-header {
        background: #f0f4ff;
        border-bottom: 1px solid #d0d8ec;
        padding: .55rem 1rem;
        display: flex;
        align-items: center;
        gap: .8rem;
        flex-wrap: wrap;
    }
    .cliente-nombre {
        font-weight: 700;
        font-size: .88rem;
        color: #1a2e6a;
        flex: 1;
        min-width: 0;
    }
    .cliente-canal {
        font-size: .72rem;
        background: #1a4dad;
        color: #fff;
        border-radius: .25rem;
        padding: .15rem .5rem;
        font-weight: 600;
    }
    .badge-multi {
        font-size: .7rem;
        background: #f59e0b;
        color: #fff;
        border-radius: .25rem;
        padding: .15rem .5rem;
        font-weight: 700;
    }
    .pp2-table {
        width: 100%;
        border-collapse: collapse;
        font-size: .84rem;
    }
    .pp2-table thead tr { background: #e8edf8; }
    .pp2-table thead th {
        padding: .45rem .8rem;
        font-weight: 700;
        font-size: .75rem;
        letter-spacing: .02em;
        white-space: nowrap;
        color: #1a4dad;
    }
    .pp2-table tbody tr { border-top: 1px solid #e8edf8; }
    .pp2-table tbody tr:hover { background: #f5f8ff; }
    .pp2-table td {
        padding: .5rem .8rem;
        vertical-align: middle;
        color: #222;
        font-size: .83rem;
    }
    .pp2-table td.center { text-align: center; }
    .pp2-table td.mono   { font-family: monospace; }
    .cb-pedido {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: #1a4dad;
    }
    .bloque-actions {
        padding: .6rem 1rem;
        background: #f8f9fe;
        border-top: 1px solid #e0e6f5;
        display: flex;
        align-items: center;
        gap: .5rem;
        flex-wrap: wrap;
    }
    .btn-preparar-uno {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        background: #1a4dad;
        color: #fff;
        border: none;
        border-radius: .35rem;
        padding: .4rem .9rem;
        font-size: .78rem;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        transition: background .15s, transform .1s;
        box-shadow: 0 2px 5px rgba(0,0,0,.12);
    }
    .btn-preparar-uno:hover { background: #163fa0; transform: translateY(-1px); }
    .btn-preparar-uno.en-proceso { background: #f59e0b; }
    .btn-preparar-uno.en-proceso:hover { background: #d97706; }
    .btn-integrar {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        background: #15803d;
        color: #fff;
        border: none;
        border-radius: .35rem;
        padding: .4rem .9rem;
        font-size: .78rem;
        font-weight: 700;
        cursor: pointer;
        transition: background .15s, transform .1s;
        box-shadow: 0 2px 5px rgba(0,0,0,.12);
    }
    .btn-integrar:hover { background: #166534; transform: translateY(-1px); }
    .btn-integrar:disabled { background: #9ca3af; cursor: not-allowed; transform: none; }
    .sel-counter { font-size: .74rem; color: #4b5563; font-weight: 600; }
    .pp2-empty {
        text-align: center; color: #888; font-size: .85rem; padding: 2rem 0;
    }
    .pp2-legend {
        display: flex; align-items: center; justify-content: flex-end; margin-top: .8rem;
    }
    @media (max-width: 1280px) {
        .pp2-table thead th { padding: .55rem .8rem; }
        .pp2-table td { padding: .6rem .8rem; }
        .btn-preparar-uno, .btn-integrar { min-height: 44px; font-size: .85rem; padding: .55rem 1.1rem; }
    }
    @media (max-width: 640px) {
        .pp2-body { padding: .6rem; }
    }
</style>

<!-- Modal integración -->
<div id="modal-integrar" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);
     z-index:9000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;padding:28px 32px;max-width:440px;
                width:93%;box-shadow:0 20px 60px rgba(0,0,0,.3);text-align:center;
                font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
        <div style="width:56px;height:56px;background:#d1fae5;border-radius:50%;
                    display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#15803d" stroke-width="2.2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            </svg>
        </div>
        <h3 style="margin:0 0 10px;font-size:1.15rem;color:#1f2937;">Generar AP integrada</h3>
        <p id="modal-integrar-txt" style="margin:0 0 22px;color:#4b5563;font-size:.88rem;line-height:1.55;"></p>
        <div style="display:flex;gap:12px;justify-content:center;">
            <button onclick="cerrarModalIntegrar()"
                style="flex:1;padding:10px 18px;background:#e5e7eb;border:none;border-radius:8px;
                       font-size:.9rem;font-weight:600;color:#374151;cursor:pointer;">
                Cancelar
            </button>
            <button onclick="confirmarIntegrar()"
                style="flex:1;padding:10px 18px;background:#15803d;border:none;border-radius:8px;
                       font-size:.9rem;font-weight:600;color:#fff;cursor:pointer;">
                Confirmar
            </button>
        </div>
    </div>
</div>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.6rem;">
    <a href="/dashboard_home"
       class="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Volver
    </a>
</div>

<div>
    <div class="pp2-header">
        <span>Preparación de Pedido</span>
        <span style="font-size:.78rem;opacity:.85;">Pedidos alistados pendientes de despacho</span>
    </div>

    <div class="pp2-body">
        <?php if (empty($pedidos)): ?>
            <div class="pp2-empty">No hay pedidos listos para preparar.</div>
        <?php else: ?>

            <?php foreach ($pedidosPorCliente as $key => $grupo):
                [$canal, $nomcli] = explode('||', $key, 2);
                $esMulti = count($grupo) > 1;
                $bloqueId = 'bloque_' . md5($key);
            ?>
            <div class="cliente-bloque">
                <div class="cliente-header">
                    <span class="cliente-nombre"><?= htmlspecialchars($nomcli) ?></span>
                    <span class="cliente-canal"><?= htmlspecialchars($canal) ?></span>
                    <?php if ($esMulti): ?>
                        <span class="badge-multi">
                            <?= count($grupo) ?> pedidos &middot; Se pueden integrar
                        </span>
                    <?php endif; ?>
                </div>

                <table class="pp2-table">
                    <thead>
                        <tr>
                            <th style="width:38px;text-align:center;">
                                <?php if ($esMulti): ?>
                                    <input type="checkbox" class="cb-pedido"
                                           id="selAll_<?= $bloqueId ?>"
                                           title="Seleccionar todos"
                                           onchange="toggleTodos('<?= $bloqueId ?>')">
                                <?php endif; ?>
                            </th>
                            <th>Documento</th>
                            <th>Fecha Entrega</th>
                            <th style="text-align:center;">Estado AP</th>
                            <th style="text-align:center;">Acción individual</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grupo as $p):
                            $enProceso = !empty($p['en_proceso']);
                        ?>
                        <tr>
                            <td class="center">
                                <?php if ($esMulti): ?>
                                    <input type="checkbox"
                                           class="cb-pedido cb-<?= $bloqueId ?>"
                                           value="<?= htmlspecialchars($p['nrodoc']) ?>"
                                           data-pref="<?= htmlspecialchars($p['prefijo']) ?>"
                                           onchange="actualizarContador('<?= $bloqueId ?>')">
                                <?php endif; ?>
                            </td>
                            <td class="mono" style="font-weight:600;">
                                <?= htmlspecialchars($nrodocFmt($p['nrodoc'])) ?>
                            </td>
                            <td><?= htmlspecialchars($p['fecentrega_fmt']) ?></td>
                            <td class="center">
                                <?php if ($enProceso): ?>
                                    <span style="background:#fef3c7;color:#92400e;border-radius:.25rem;
                                                 padding:.15rem .55rem;font-size:.72rem;font-weight:700;">
                                        En proceso
                                    </span>
                                <?php else: ?>
                                    <span style="background:#f3f4f6;color:#6b7280;border-radius:.25rem;
                                                 padding:.15rem .55rem;font-size:.72rem;font-weight:600;">
                                        Pendiente
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="center">
                                <a href="/preparacion-pedido/<?= urlencode($p['nrodoc']) ?>/preparar"
                                   class="btn-preparar-uno <?= $enProceso ? 'en-proceso' : '' ?>">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none"
                                         stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    <?= $enProceso ? 'Continuar' : 'Preparar' ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($esMulti): ?>
                <div class="bloque-actions">
                    <span class="sel-counter" id="counter_<?= $bloqueId ?>">
                        Seleccione pedidos para integrar en una AP
                    </span>
                    <button class="btn-integrar"
                            id="btnIntegrar_<?= $bloqueId ?>"
                            disabled
                            onclick="abrirModalIntegrar('<?= $bloqueId ?>')">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                        Integrar seleccionados en una AP
                    </button>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

        <?php endif; ?>
    </div>
</div>

<div class="pp2-legend">
    <a href="/dashboard_home"
       style="background:#e0e0e0;border:1px solid #999;border-radius:.35rem;
              padding:.3rem .8rem;font-size:.78rem;font-weight:600;color:#333;
              text-decoration:none;display:flex;align-items:center;gap:.3rem;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
        </svg>
        Salir
    </a>
</div>

<form id="form-integrar" method="GET" action="/preparacion-pedido/integrar">
    <input type="hidden" name="docs"  id="fi-docs">
    <input type="hidden" name="prefs" id="fi-prefs">
</form>

<script>
    let _bloqueActivo = null;

    function toggleTodos(bloqueId) {
        const selAll = document.getElementById('selAll_' + bloqueId);
        document.querySelectorAll('.cb-' + bloqueId)
                .forEach(cb => cb.checked = selAll.checked);
        actualizarContador(bloqueId);
    }

    function actualizarContador(bloqueId) {
        const cbs     = document.querySelectorAll('.cb-' + bloqueId);
        const checked = [...cbs].filter(cb => cb.checked);
        const counter = document.getElementById('counter_' + bloqueId);
        const btnInt  = document.getElementById('btnIntegrar_' + bloqueId);
        const selAll  = document.getElementById('selAll_' + bloqueId);

        if (selAll) {
            selAll.checked       = checked.length === cbs.length;
            selAll.indeterminate = checked.length > 0 && checked.length < cbs.length;
        }

        if (checked.length === 0) {
            counter.textContent = 'Seleccione pedidos para integrar en una AP';
            btnInt.disabled     = true;
        } else if (checked.length === 1) {
            counter.textContent = '1 pedido seleccionado — seleccione más para integrar o use "Preparar" individual';
            btnInt.disabled     = false;
        } else {
            counter.textContent = checked.length + ' pedidos seleccionados para integrar';
            btnInt.disabled     = false;
        }
    }

    function abrirModalIntegrar(bloqueId) {
        const cbs = [...document.querySelectorAll('.cb-' + bloqueId + ':checked')];
        if (cbs.length === 0) return;
        _bloqueActivo = bloqueId;

        const docs = cbs.map(cb => cb.value);
        const noms = cbs.map(cb => cb.closest('tr').cells[1].textContent.trim());

        document.getElementById('modal-integrar-txt').innerHTML =
            'Se generará <strong>una única AP</strong> integrando ' +
            '<strong>' + docs.length + ' pedido(s)</strong>:<br><br>' +
            '<span style="font-family:monospace;font-size:.82rem;">' +
            noms.join(' &nbsp;&bull;&nbsp; ') + '</span><br><br>' +
            'Podrá alistar todos los ítems en una sola pantalla y cada uno llevará la referencia de su pedido origen.';

        const modal = document.getElementById('modal-integrar');
        modal.style.display = 'flex';
    }

    function cerrarModalIntegrar() {
        document.getElementById('modal-integrar').style.display = 'none';
        _bloqueActivo = null;
    }

    function confirmarIntegrar() {
        if (!_bloqueActivo) return;
        const cbs   = [...document.querySelectorAll('.cb-' + _bloqueActivo + ':checked')];
        document.getElementById('fi-docs').value  = cbs.map(cb => cb.value).join(',');
        document.getElementById('fi-prefs').value = cbs.map(cb => cb.dataset.pref).join(',');
        document.getElementById('form-integrar').submit();
    }

    document.getElementById('modal-integrar').addEventListener('click', function(e) {
        if (e.target === this) cerrarModalIntegrar();
    });
</script>
