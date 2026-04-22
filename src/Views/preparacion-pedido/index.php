<?php
$nrodocFmt = fn(string $n): string => str_pad($n, 8, '0', STR_PAD_LEFT);
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
    .pp2-table {
        width: 100%;
        border-collapse: collapse;
        font-size: .84rem;
        background: #fff;
        border-radius: .4rem;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,.07);
    }
    .pp2-table thead tr { background: #1a4dad; color: #fff; }
    .pp2-table thead th {
        padding: .55rem .8rem;
        font-weight: 700;
        font-size: .78rem;
        letter-spacing: .02em;
        white-space: nowrap;
    }
    .pp2-table thead th:first-child { text-align: center; }
    .pp2-table tbody tr { border-bottom: 1px solid #e2e8f0; }
    .pp2-table tbody tr:last-child { border-bottom: none; }
    .pp2-table tbody tr:nth-child(even) { background: #f8f9ff; }
    .pp2-table tbody tr:hover { background: #eef2ff; }
    .pp2-table td {
        padding: .5rem .8rem;
        vertical-align: middle;
        color: #222;
        font-size: .83rem;
    }
    .pp2-table td.center { text-align: center; }
    .pp2-table td.mono   { font-family: monospace; }
    .btn-preparar {
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
        box-shadow: 0 2px 5px rgba(0,0,0,.15);
    }
    .btn-preparar:hover { background: #163fa0; transform: translateY(-1px); }
    .pp2-empty {
        text-align: center;
        color: #888;
        font-size: .85rem;
        padding: 2rem 0;
    }
    .pp2-legend {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        margin-top: .8rem;
    }

    @media (max-width: 1280px) {
        .pp2-table { font-size: .88rem; }
        .pp2-table thead th { padding: .65rem .8rem; font-size: .82rem; }
        .pp2-table td { padding: .65rem .8rem; }
        .btn-preparar { min-height: 44px; font-size: .85rem; padding: .55rem 1.1rem; }
    }
    @media (max-width: 640px) {
        .pp2-body { padding: .6rem; }
        .pp2-table { display: block; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    }
</style>

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
        <span style="font-size:.78rem;opacity:.85;">
            Pedidos alistados pendientes de despacho
        </span>
    </div>
    <div class="pp2-body">
        <?php if (empty($pedidos)): ?>
            <div class="pp2-empty">No hay pedidos listos para preparar.</div>
        <?php else: ?>
            <table class="pp2-table">
                <thead>
                    <tr>
                        <th style="text-align:center;">Documento</th>
                        <th>Cliente</th>
                        <th style="text-align:center;">Canal</th>
                        <th style="text-align:center;">Fecha Entrega</th>
                        <th style="text-align:center;">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $p): ?>
                    <tr>
                        <td class="center mono"><?= htmlspecialchars($nrodocFmt($p['nrodoc'])) ?></td>
                        <td style="font-weight:600;"><?= htmlspecialchars($p['nomcli']) ?></td>
                        <td class="center"><?= htmlspecialchars($p['codtipocli']) ?></td>
                        <td class="center"><?= htmlspecialchars($p['fecentrega_fmt']) ?></td>
                        <td class="center">
                            <a href="/preparacion-pedido/<?= urlencode($p['nrodoc']) ?>/preparar"
                               class="btn-preparar">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0
                                             002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2
                                             0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                Preparar
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
