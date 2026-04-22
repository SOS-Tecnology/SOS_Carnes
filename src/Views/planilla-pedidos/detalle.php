<?php
function itemEstado(float $cantidad, float $cantent): array
{
    if ($cantent <= 0) {
        return ['bg' => '#dc2626', 'hover' => '#b91c1c', 'label' => 'Actualizar', 'text' => '#fff'];
    }
    if ($cantent >= $cantidad && $cantidad > 0) {
        return ['bg' => '#16a34a', 'hover' => '#15803d', 'label' => 'LISTO!',     'text' => '#fff'];
    }
    return     ['bg' => '#ca8a04', 'hover' => '#a16207', 'label' => 'Actualizar', 'text' => '#fff'];
}

$totalItems    = count($items);
$itemsCompletos = 0;
foreach ($items as $it) {
    if ((float)$it['cantidad'] > 0 && (float)$it['cantent'] >= (float)$it['cantidad']) {
        $itemsCompletos++;
    }
}
$todosListos = $totalItems > 0 && $itemsCompletos >= $totalItems;
?>

<style>
    .det-wrap { max-width: 960px; margin: 0 auto; }
    .det-header {
        background: #1a4dad;
        color: #fff;
        padding: .65rem 1.2rem;
        border-radius: .5rem .5rem 0 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-weight: 700;
        font-size: .9rem;
        gap: .5rem;
        flex-wrap: wrap;
    }
    .det-header-left  { display: flex; align-items: center; gap: .7rem; }
    .det-header-right { font-size: .78rem; font-weight: 500; opacity: .9; }
    .det-body {
        background: #f0f2f8;
        border: 1px solid #b0b8d0;
        border-top: none;
        border-radius: 0 0 .5rem .5rem;
        padding: 1rem;
    }
    .det-table {
        width: 100%;
        border-collapse: collapse;
        font-size: .82rem;
        background: #fff;
        border-radius: .4rem;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,.08);
    }
    .det-table thead tr {
        background: #1a4dad;
        color: #fff;
    }
    .det-table thead th {
        padding: .55rem .7rem;
        text-align: center;
        font-weight: 700;
        font-size: .78rem;
        letter-spacing: .02em;
        white-space: nowrap;
    }
    .det-table thead th:nth-child(2) { text-align: left; }
    .det-table tbody tr { border-bottom: 1px solid #e2e8f0; }
    .det-table tbody tr:last-child { border-bottom: none; }
    .det-table tbody tr:nth-child(even) { background: #f8f9ff; }
    .det-table tbody tr:hover { background: #eef2ff; }
    .det-table td {
        padding: .5rem .7rem;
        text-align: center;
        vertical-align: middle;
        color: #222;
    }
    .det-table td:nth-child(2) { text-align: left; font-weight: 600; }
    .det-table td.num { font-family: monospace; font-size: .83rem; }
    .det-table td.diff-pos { color: #dc2626; font-weight: 700; font-family: monospace; }
    .det-table td.diff-zero { color: #16a34a; font-weight: 700; font-family: monospace; }
    .btn-accion {
        display: inline-block;
        min-width: 90px;
        padding: .45rem .8rem;
        border: none;
        border-radius: .35rem;
        font-size: .78rem;
        font-weight: 700;
        cursor: pointer;
        text-align: center;
        text-decoration: none;
        letter-spacing: .02em;
        transition: background .15s, transform .1s;
        box-shadow: 0 2px 5px rgba(0,0,0,.15);
    }
    .btn-accion:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,.2); }
    .det-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: .9rem;
        flex-wrap: wrap;
        gap: .5rem;
    }
    .det-progress {
        display: flex;
        align-items: center;
        gap: .5rem;
        font-size: .8rem;
        color: #333;
        font-weight: 500;
    }
    .progress-bar-wrap {
        width: 140px;
        height: 10px;
        background: #ddd;
        border-radius: 9px;
        overflow: hidden;
    }
    .progress-bar-fill {
        height: 100%;
        border-radius: 9px;
        background: #16a34a;
        transition: width .4s;
    }
    .badge-listo {
        background: #16a34a;
        color: #fff;
        padding: .25rem .7rem;
        border-radius: 9px;
        font-size: .75rem;
        font-weight: 700;
        letter-spacing: .03em;
    }
    .btn-volver {
        display: flex;
        align-items: center;
        gap: .3rem;
        background: #e0e0e0;
        border: 1px solid #999;
        border-radius: .35rem;
        padding: .35rem .8rem;
        font-size: .78rem;
        font-weight: 600;
        color: #333;
        text-decoration: none;
        transition: background .15s;
    }
    .btn-volver:hover { background: #d0d0d0; }

    /* ── Tabla con scroll horizontal en tablet ───────────── */
    .det-table-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: .4rem;
    }
    /* ── Touch targets para botones de acción ────────────── */
    @media (max-width: 1280px) {
        .det-body { padding: .75rem; }
        .det-table { font-size: .85rem; }
        .det-table thead th { padding: .65rem .6rem; font-size: .8rem; }
        .det-table td { padding: .65rem .6rem; }
        .btn-accion {
            min-height: 48px;
            min-width: 110px;
            font-size: .88rem;
            padding: .65rem 1.1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .det-footer { margin-top: 1.1rem; }
        .btn-volver { min-height: 44px; font-size: .85rem; padding: .5rem 1.1rem; }
    }
</style>

<div class="det-wrap">

    <!-- Header -->
    <div class="det-header">
        <div class="det-header-left">
            <a href="/planilla-pedidos" class="btn-volver" style="background:rgba(255,255,255,.15);border-color:rgba(255,255,255,.3);color:#fff;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                Volver
            </a>
            <span><?= htmlspecialchars($pedido['nomcli']) ?></span>
        </div>
        <div class="det-header-right">
            Doc: <?= htmlspecialchars(str_pad($pedido['nrodoc'], 8, '0', STR_PAD_LEFT)) ?>
            &nbsp;|&nbsp;
            Entrega: <?= htmlspecialchars($pedido['fecentrega_fmt']) ?>
            &nbsp;|&nbsp;
            Canal: <?= htmlspecialchars($pedido['codtipocli']) ?>
        </div>
    </div>

    <!-- Body -->
    <div class="det-body">
        <?php if (empty($items)): ?>
            <div style="text-align:center;color:#888;padding:2rem 0;font-size:.9rem;">
                Este pedido no tiene ítems registrados.
            </div>
        <?php else: ?>
        <div class="det-table-wrap">
        <table class="det-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Descripción</th>
                    <th>Unidad</th>
                    <th>Cantidad</th>
                    <th>Cant. Alistada</th>
                    <th>Diferencia</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $it):
                    $cantidad  = (float)$it['cantidad'];
                    $cantent   = (float)$it['cantent'];
                    $diferencia = (float)$it['diferencia'];
                    $est = itemEstado($cantidad, $cantent);
                ?>
                <tr>
                    <td class="num"><?= htmlspecialchars($it['codart']) ?></td>
                    <td><?= htmlspecialchars($it['descripcion']) ?></td>
                    <td class="num"><?= number_format((float)$it['unidad'], 3) ?></td>
                    <td class="num"><?= number_format($cantidad, 3) ?></td>
                    <td class="num"><?= number_format($cantent, 3) ?></td>
                    <td class="<?= $diferencia > 0 ? 'diff-pos' : 'diff-zero' ?> num">
                        <?= number_format($diferencia, 3) ?>
                    </td>
                    <td>
                        <a href="/planilla-pedidos/<?= urlencode($pedido['nrodoc']) ?>/item/<?= urlencode($it['registro']) ?>"
                           class="btn-accion"
                           style="background:<?= $est['bg'] ?>;color:<?= $est['text'] ?>;"
                           onmouseover="this.style.background='<?= $est['hover'] ?>'"
                           onmouseout="this.style.background='<?= $est['bg'] ?>'">
                            <?= $est['label'] ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div><!-- /det-table-wrap -->
        <?php endif; ?>
    </div>

    <!-- Footer: progreso + volver -->
    <div class="det-footer">
        <div class="det-progress">
            <?php if ($totalItems > 0): ?>
                <?php $pct = round($itemsCompletos / $totalItems * 100); ?>
                <span><?= $itemsCompletos ?> / <?= $totalItems ?> ítems</span>
                <div class="progress-bar-wrap">
                    <div class="progress-bar-fill" style="width:<?= $pct ?>%;
                         background:<?= $pct < 100 ? ($pct > 0 ? '#ca8a04' : '#dc2626') : '#16a34a' ?>;"></div>
                </div>
                <span><?= $pct ?>%</span>
                <?php if ($todosListos): ?>
                    <span class="badge-listo">&#10003; Todo alistado</span>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <a href="/planilla-pedidos" class="btn-volver">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver a planilla
        </a>
    </div>

</div>
