<?php
$cantidad   = (float)$item['cantidad'];
$cantent    = (float)$item['cantent'];
$diferencia = max($cantidad - $cantent, 0);
$pct        = $cantidad > 0 ? min(round($cantent / $cantidad * 100), 100) : 0;
$totalLotes = array_sum(array_column($lotes, 'cantidad'));

if ($cantent <= 0)                              { $dotColor = '#dc2626'; $dotLabel = 'Sin procesar'; }
elseif ($cantent >= $cantidad && $cantidad > 0) { $dotColor = '#16a34a'; $dotLabel = 'Completo'; }
else                                            { $dotColor = '#ca8a04'; $dotLabel = 'Parcial'; }

$registroUrl = urlencode($item['registro']);
$nrodocUrl   = urlencode($pedido['nrodoc']);

// Normaliza comencpo: elimina líneas vacías y espacios redundantes
$comencpo = str_replace(['\r\n', '\r', '\n', '\\n'], "\n", $item['comencpo'] ?? '');
$lines    = array_filter(
    array_map(fn($l) => preg_replace('/[ \t]{2,}/', ' ', trim($l)), explode("\n", $comencpo)),
    fn($l) => $l !== ''
);
$comencpo = implode("\n", $lines);
?>

<style>
    .item-wrap { max-width: 700px; margin: 0 auto; }

    .item-header {
        background:#1a4dad; color:#fff; padding:.65rem 1.2rem;
        border-radius:.5rem .5rem 0 0;
        display:flex; align-items:center; gap:.7rem; font-weight:700; font-size:.9rem;
    }
    .item-header .btn-back {
        display:flex;align-items:center;gap:.25rem;color:#fff;text-decoration:none;
        background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);
        border-radius:.3rem;padding:.25rem .55rem;font-size:.75rem;flex-shrink:0;
    }
    .status-pill {
        margin-left:auto;display:flex;align-items:center;gap:.3rem;
        padding:.2rem .65rem;border-radius:9px;font-size:.72rem;font-weight:600;
        background:rgba(255,255,255,.18);
    }
    .dot-sm { width:9px;height:9px;border-radius:50%;flex-shrink:0;border:1.5px solid rgba(255,255,255,.5); }

    .item-body {
        background:#f0f2f8;border:1px solid #b0b8d0;border-top:none;
        border-radius:0 0 .5rem .5rem;padding:1rem 1.2rem;
        display:flex;flex-direction:column;gap:.9rem;
    }

    /* ── Producto ── */
    .prod-card { background:#fff;border-radius:.4rem;border:1px solid #d0d8ec;padding:.8rem 1rem;box-shadow:0 2px 6px rgba(0,0,0,.06); }
    .prod-cod  { font-size:.7rem;color:#888;font-weight:600;letter-spacing:.04em;margin-bottom:.1rem; }
    .prod-desc { font-size:1rem;font-weight:700;color:#1a2e1a;margin-bottom:.6rem; }
    .item-stats { display:grid;grid-template-columns:repeat(3,1fr);gap:.5rem;text-align:center; }
    .stat-box   { background:#f5f7ff;border:1px solid #dce4f8;border-radius:.35rem;padding:.45rem .4rem; }
    .stat-label { font-size:.63rem;color:#888;font-weight:600;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.1rem; }
    .stat-value { font-size:.9rem;font-weight:700;color:#222;font-family:monospace; }
    .progress-wrap { display:flex;align-items:center;gap:.5rem;margin-top:.65rem; }
    .pbar-outer    { flex:1;height:10px;background:#ddd;border-radius:9px;overflow:hidden; }
    .pbar-fill     { height:100%;border-radius:9px;transition:width .4s; }
    .badge-listo   { background:#16a34a;color:#fff;padding:.2rem .6rem;border-radius:9px;font-size:.72rem;font-weight:700; }

    /* ── Ficha técnica ── */
    .ficha-card { background:#fff8ed;border:1px solid #f0c070;border-radius:.4rem;padding:.75rem 1rem; }
    .ficha-title {
        font-size:.75rem;font-weight:700;color:#b45309;text-transform:uppercase;
        letter-spacing:.04em;margin-bottom:.5rem;display:flex;align-items:center;gap:.35rem;
    }
    .ficha-obs {
        background:#fff;border:1px solid #e8d5a3;border-radius:.3rem;
        padding:.5rem .7rem;font-size:.8rem;color:#333;line-height:1.55;
        margin-bottom:.55rem;white-space:pre-wrap;
    }
    .ficha-specs { display:grid;grid-template-columns:1fr 1fr;gap:.25rem .9rem; }
    .ficha-row   { display:flex;gap:.3rem;font-size:.78rem;line-height:1.4; }
    .ficha-key   { color:#888;font-weight:600;white-space:nowrap;min-width:110px; }
    .ficha-val   { color:#222; }
    .ficha-empty { font-size:.78rem;color:#aaa;font-style:italic; }

    /* ── Tabla de lotes ── */
    .lotes-title { font-size:.75rem;font-weight:700;color:#333;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.4rem; }
    .lotes-table { width:100%;border-collapse:collapse;font-size:.8rem;background:#fff;
                   border-radius:.4rem;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.07); }
    .lotes-table thead tr { background:#1a4dad;color:#fff; }
    .lotes-table thead th { padding:.45rem .5rem;text-align:center;font-weight:700;font-size:.72rem;letter-spacing:.02em;white-space:nowrap; }
    .lotes-table tbody tr { border-bottom:1px solid #eef0f8; }
    .lotes-table tbody tr:last-child { border-bottom:none; }
    .lotes-table tbody tr:nth-child(even) { background:#f8f9ff; }
    .lotes-table td { padding:.38rem .5rem;text-align:center;vertical-align:middle;color:#222; }
    .lotes-table td.mono { font-family:monospace;font-size:.8rem; }
    .lotes-empty { text-align:center;color:#aaa;font-size:.78rem;padding:.9rem 0;font-style:italic; }
    .lotes-total td { background:#e8f5e9!important;font-weight:700;color:#15803d;font-family:monospace; }
    .btn-del {
        background:#fee2e2;border:1px solid #fca5a5;color:#dc2626;border-radius:.25rem;
        padding:.18rem .42rem;font-size:.7rem;font-weight:700;cursor:pointer;
        transition:background .12s;line-height:1;
    }
    .btn-del:hover { background:#fecaca; }

    /* ── Agregar ── */
    .agregar-card { background:#fff;border:1px solid #d0d8ec;border-radius:.4rem;padding:.8rem 1rem;box-shadow:0 2px 6px rgba(0,0,0,.06); }
    .agregar-title { font-size:.75rem;font-weight:700;color:#1a4dad;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.55rem; }
    .form-row { display:grid;grid-template-columns:1fr 1fr 1fr;gap:.6rem;margin-bottom:.6rem; }
    .form-group label { display:block;font-size:.7rem;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.03em;margin-bottom:.22rem; }
    .form-group input {
        width:100%;padding:.48rem .65rem;font-size:.88rem;font-weight:600;
        border:1.5px solid #b0b8d0;border-radius:.35rem;color:#222;background:#fff;
        box-sizing:border-box;transition:border-color .15s;
    }
    .form-group input:focus { outline:none;border-color:#1a4dad;box-shadow:0 0 0 3px rgba(26,77,173,.12); }
    .form-group input.center { text-align:center; }
    .btn-agregar {
        display:flex;align-items:center;justify-content:center;gap:.4rem;
        width:100%;padding:.6rem;background:#1a4dad;color:#fff;border:none;
        border-radius:.4rem;font-size:.88rem;font-weight:700;cursor:pointer;
        letter-spacing:.03em;transition:background .15s,transform .1s;
        box-shadow:0 3px 8px rgba(26,77,173,.25);
    }
    .btn-agregar:hover { background:#163fa0;transform:translateY(-1px); }

    .item-footer { display:flex;justify-content:flex-end;margin-top:.7rem; }
    .btn-volver {
        display:flex;align-items:center;gap:.3rem;background:#e0e0e0;border:1px solid #999;
        border-radius:.35rem;padding:.35rem .8rem;font-size:.78rem;font-weight:600;
        color:#333;text-decoration:none;transition:background .15s;
    }
    .btn-volver:hover { background:#d0d0d0; }

    /* ── Tablet: touch targets ──────────────────────────────── */
    @media (max-width: 1280px) {
        .item-wrap { max-width: 100%; }

        /* Inputs y stats más grandes */
        .stat-value  { font-size: 1.05rem; }
        .stat-label  { font-size: .7rem; }
        .stat-box    { padding: .6rem .4rem; }

        /* Inputs de formulario ≥48px */
        .form-group input {
            min-height: 48px;
            font-size: 1rem;
            padding: .65rem .85rem;
        }

        /* En portrait (<640px) el form pasa a 2 col: lote+temp / cantidad full */
        @media (max-width: 640px) {
            .form-row { grid-template-columns: 1fr 1fr; }
            .form-row .form-group:last-child { grid-column: 1 / -1; }
        }

        /* Botón eliminar ≥44×44px */
        .btn-del {
            min-width:  44px;
            min-height: 44px;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: .35rem;
        }

        /* Botón agregar bien grande */
        .btn-agregar { min-height: 56px; font-size: 1rem; }

        /* Tabla de lotes más cómoda */
        .lotes-table { font-size: .85rem; }
        .lotes-table thead th { padding: .6rem .55rem; }
        .lotes-table td { padding: .55rem .55rem; }

        /* Volver */
        .btn-volver { min-height: 44px; font-size: .85rem; padding: .5rem 1.1rem; }

        /* Progreso */
        .pbar-outer { height: 13px; }
    }
</style>

<div class="item-wrap">

    <!-- ── Header ── -->
    <div class="item-header">
        <a href="/planilla-pedidos/<?= $nrodocUrl ?>/detalle" class="btn-back">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver
        </a>
        <span>Alistar ítem</span>
        <span class="status-pill">
            <span class="dot-sm" style="background:<?= $dotColor ?>;"></span>
            <?= $dotLabel ?>
        </span>
    </div>

    <div class="item-body">

        <!-- ── Producto + stats ── -->
        <div class="prod-card">
            <div class="prod-cod"><?= htmlspecialchars($item['codart']) ?></div>
            <div class="prod-desc"><?= htmlspecialchars($item['descripcion']) ?></div>
            <div class="item-stats">
                <div class="stat-box">
                    <div class="stat-label">Solicitado</div>
                    <div class="stat-value"><?= number_format($cantidad, 3) ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Alistado</div>
                    <div class="stat-value" style="color:<?= $dotColor ?>;"><?= number_format($cantent, 3) ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Diferencia</div>
                    <div class="stat-value" style="color:<?= $diferencia > 0 ? '#dc2626' : '#16a34a' ?>;">
                        <?= number_format($diferencia, 3) ?>
                    </div>
                </div>
            </div>
            <div class="progress-wrap">
                <div class="pbar-outer">
                    <div class="pbar-fill" style="width:<?= $pct ?>%;
                         background:<?= $pct >= 100 ? '#16a34a' : ($pct > 0 ? '#ca8a04' : '#dc2626') ?>;"></div>
                </div>
                <span style="font-size:.72rem;color:#555;font-weight:500;min-width:30px;text-align:right;"><?= $pct ?>%</span>
                <?php if ($pct >= 100): ?>
                    <span class="badge-listo">&#10003; Listo</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Ficha Técnica ── -->
        <div class="ficha-card">
            <div class="ficha-title">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Ficha Técnica: [<?= htmlspecialchars($item['codart']) ?>] <?= htmlspecialchars($item['descripcion']) ?>
                &nbsp;Cant.: <?= number_format($cantidad, 3) ?>
            </div>

            <?php if ($comencpo !== ''): ?>
                <div class="ficha-obs"><?= htmlspecialchars($comencpo) ?></div>
            <?php endif; ?>

            <?php if ($fichaTecnica): ?>
                <?php
                $specs = [
                    'Empaque'         => trim($fichaTecnica['empaque']       ?? ''),
                    'Conservación'    => trim($fichaTecnica['conservacion']  ?? ''),
                    'Embalaje'        => trim($fichaTecnica['embalaje']      ?? ''),
                    'Tolerancia'      => trim($fichaTecnica['tolerancia']    ?? ''),
                    'Días maduración' => isset($fichaTecnica['diasmaduracion'])
                                        ? ((int)$fichaTecnica['diasmaduracion'] . ' días') : '',
                    'Peso porción'    => isset($fichaTecnica['pesoxemp']) && $fichaTecnica['pesoxemp'] > 0
                                        ? number_format((float)$fichaTecnica['pesoxemp'], 2) : '',
                ];
                $hasSpecs = array_filter($specs, fn($v) => $v !== '');
                ?>
                <?php if ($hasSpecs): ?>
                    <div class="ficha-specs">
                        <?php foreach ($specs as $key => $val):
                            if ($val === '') continue; ?>
                            <div class="ficha-row">
                                <span class="ficha-key"><?= htmlspecialchars($key) ?>:</span>
                                <span class="ficha-val"><?= htmlspecialchars($val) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <span class="ficha-empty">Sin especificaciones configuradas para este producto.</span>
                <?php endif; ?>
            <?php elseif ($comencpo === ''): ?>
                <span class="ficha-empty">No hay ficha técnica configurada para este producto/cliente.</span>
            <?php endif; ?>
        </div>

        <!-- ── Tabla de lotes registrados ── -->
        <div>
            <div class="lotes-title">Entradas registradas</div>
            <table class="lotes-table">
                <thead>
                    <tr>
                        <th>Hora</th>
                        <th>Lote</th>
                        <th>Temp. °C</th>
                        <th>Peso</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lotes)): ?>
                        <tr><td colspan="5" class="lotes-empty">Sin entradas — use el formulario para agregar.</td></tr>
                    <?php else: ?>
                        <?php foreach ($lotes as $lt): ?>
                        <tr>
                            <td class="mono"><?= htmlspecialchars($lt['hora']) ?></td>
                            <td><?= htmlspecialchars(trim($lt['lote'] ?? '')) ?: '—' ?></td>
                            <td class="mono"><?= number_format((float)$lt['temp'], 2) ?></td>
                            <td class="mono"><?= number_format((float)$lt['cantidad'], 3) ?></td>
                            <td>
                                <form method="POST"
                                      action="/planilla-pedidos/<?= $nrodocUrl ?>/item/<?= $registroUrl ?>/eliminar"
                                      onsubmit="return confirm('¿Eliminar esta entrada?')">
                                    <input type="hidden" name="hora" value="<?= htmlspecialchars($lt['hora']) ?>">
                                    <button type="submit" class="btn-del">✕</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="lotes-total">
                            <td colspan="3" style="text-align:right;font-size:.73rem;color:#555;font-weight:600;">Total lotes</td>
                            <td class="mono"><?= number_format($totalLotes, 3) ?></td>
                            <td></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- ── Agregar nueva entrada ── -->
        <div class="agregar-card">
            <div class="agregar-title">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Agregar entrada
            </div>
            <form id="formAgregar" method="POST" action="/planilla-pedidos/<?= $nrodocUrl ?>/item/<?= $registroUrl ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label for="lote">Lote</label>
                        <input type="text" id="lote" name="lote"
                               maxlength="15" autocomplete="off" placeholder="Ej. L-001">
                    </div>
                    <div class="form-group">
                        <label for="temp">Temp. °C</label>
                        <input type="number" id="temp" name="temp"
                               class="center" step="0.01" min="-99" max="999"
                               placeholder="0.00" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="cantidad">Peso (kg)</label>
                        <input type="number" id="cantidad" name="cantidad"
                               class="center" min="0.001" step="0.001"
                               placeholder="0.000" autocomplete="off" required autofocus>
                    </div>
                </div>
                <button type="submit" id="btnAgregar" class="btn-agregar">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span id="btnText">Agregar</span>
                </button>
            </form>

            <script>
            document.getElementById('formAgregar').addEventListener('submit', function(e) {
                const btn = document.getElementById('btnAgregar');
                const btnText = document.getElementById('btnText');
                
                // Si ya está procesando, prevenir envío
                if (btn.disabled) {
                    e.preventDefault();
                    return;
                }
                
                // Deshabilitar y mostrar estado de carga
                btn.disabled = true;
                btn.style.opacity = '0.7';
                btn.style.cursor = 'not-allowed';
                btnText.textContent = 'Procesando...';
                
                // Deshabilitar inputs también
                document.getElementById('lote').disabled = true;
                document.getElementById('temp').disabled = true;
                document.getElementById('cantidad').disabled = true;
            });
            </script>
        </div>

    </div><!-- /item-body -->

    <!-- ── Footer ── -->
    <div class="item-footer">
        <a href="/planilla-pedidos/<?= $nrodocUrl ?>/detalle" class="btn-volver">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver al detalle
        </a>
    </div>

</div>
