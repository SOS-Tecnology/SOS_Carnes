<?php
function estadoPedido(int $total, int $completos, int $iniciados): array
{
    if ($total === 0 || $iniciados === 0) {
        return ['dot' => '#dc2626', 'label' => 'Sin procesar'];
    }
    if ($completos >= $total) {
        return ['dot' => '#16a34a', 'label' => 'Procesado sin cerrar'];
    }
    return ['dot' => '#ca8a04', 'label' => 'Le faltan ítems'];
}
?>

<style>
    .pp-header {
        background: #1a4dad;
        color: #fff;
        padding: .6rem 1.2rem;
        border-radius: .5rem .5rem 0 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-weight: 600;
        font-size: .95rem;
        letter-spacing: .01em;
    }
    .pp-body {
        background: #e8eaf0;
        border: 1px solid #b0b8d0;
        border-top: none;
        border-radius: 0 0 .5rem .5rem;
        padding: 1rem;
        min-height: 260px;
    }
    .pp-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: .75rem;
    }
    .pp-card {
        background: #fffde4;
        border: 2px solid #d4a800;
        border-radius: .45rem;
        padding: .7rem .8rem .6rem;
        cursor: pointer;
        transition: box-shadow .15s, transform .1s;
        position: relative;
        user-select: none;
    }
    .pp-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,.18); transform: translateY(-1px); }
    .pp-card.cerrando {
        opacity: .45;
        pointer-events: none;
        border-color: #aaa;
        background: #f0f0f0;
    }
    .pp-card-title {
        font-weight: 700;
        font-size: .78rem;
        color: #1a2e1a;
        text-align: center;
        margin-bottom: .35rem;
        line-height: 1.2;
    }
    .pp-card-info {
        font-size: .72rem;
        color: #444;
        text-align: center;
        line-height: 1.5;
    }
    .pp-card-actions {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        margin-top: .55rem;
    }
    .btn-cerrar {
        display: flex;
        align-items: center;
        gap: .25rem;
        background: #c0392b;
        color: #fff;
        border: none;
        border-radius: .3rem;
        font-size: .68rem;
        font-weight: 600;
        padding: .28rem .55rem;
        cursor: pointer;
        transition: background .15s;
    }
    .btn-cerrar:hover { background: #a93226; }
    .btn-estado {
        display: flex;
        align-items: center;
        gap: .25rem;
        background: #f5e642;
        border: 1.5px solid #b8a800;
        border-radius: .3rem;
        font-size: .68rem;
        font-weight: 600;
        padding: .28rem .55rem;
        cursor: default;
        color: #333;
    }
    .dot {
        width: 11px; height: 11px;
        border-radius: 50%;
        display: inline-block;
        flex-shrink: 0;
        border: 1.5px solid rgba(0,0,0,.2);
    }
    .pp-legend {
        border: 2px solid #c0392b;
        border-radius: .4rem;
        padding: .55rem .9rem;
        margin-top: .9rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: .5rem;
    }
    .pp-legend-items {
        display: flex;
        flex-wrap: wrap;
        gap: .4rem .9rem;
    }
    .pp-legend-item {
        display: flex;
        align-items: center;
        gap: .35rem;
        font-size: .75rem;
        color: #222;
        font-weight: 500;
    }
    .pp-legend-title {
        font-weight: 700;
        font-size: .8rem;
        color: #c0392b;
        margin-right: .4rem;
    }
    .pp-refresh {
        font-size: .7rem;
        color: #666;
        display: flex;
        align-items: center;
        gap: .3rem;
    }
    .pp-spinner {
        width: 12px; height: 12px;
        border: 2px solid #aaa;
        border-top-color: #1a4dad;
        border-radius: 50%;
        display: none;
        animation: spin .7s linear infinite;
    }
    .pp-spinner.active { display: inline-block; }
    @keyframes spin { to { transform: rotate(360deg); } }
    .pp-empty {
        text-align: center;
        color: #888;
        font-size: .85rem;
        padding: 2rem 0;
    }
</style>

<!-- Header tipo cliente -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.6rem;">
    <a href="/dashboard_home"
       class="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Volver
    </a>
    <div class="pp-refresh">
        <span class="pp-spinner" id="spinner"></span>
        <span id="refresh-txt">Actualiza cada 30 s</span>
    </div>
</div>

<!-- Panel principal -->
<div>
    <div class="pp-header">
        <span>Pedidos generados</span>
        <span><?= htmlspecialchars($nomTipoCli ?? '') ?></span>
    </div>
    <div class="pp-body">
        <div class="pp-grid" id="pedidos-grid">
            <?php if (empty($pedidos)): ?>
                <div class="pp-empty" style="grid-column:1/-1">
                    No hay pedidos pendientes por alistar.
                </div>
            <?php else: ?>
                <?php foreach ($pedidos as $p):
                    $est = estadoPedido(
                        (int)$p['total_items'],
                        (int)$p['items_completos'],
                        (int)$p['items_iniciados']
                    );
                    $nrodoc = str_pad($p['nrodoc'], 8, '0', STR_PAD_LEFT);
                ?>
                <div class="pp-card"
                     id="card-<?= htmlspecialchars($p['nrodoc']) ?>"
                     onclick="abrirPedido('<?= htmlspecialchars($p['nrodoc']) ?>')"
                     title="Ver detalle del pedido">
                    <div class="pp-card-title"><?= htmlspecialchars($p['nomcli']) ?></div>
                    <div class="pp-card-info">
                        Documento: <?= htmlspecialchars($nrodoc) ?><br>
                        Fecha Entrega: <?= htmlspecialchars($p['fecentrega_fmt']) ?>
                    </div>
                    <div class="pp-card-actions">
                        <button class="btn-cerrar"
                                onclick="cerrarPedido(event, '<?= htmlspecialchars($p['nrodoc']) ?>')"
                                title="Cerrar pedido">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="3" stroke-linecap="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            Cerrar pedido
                        </button>
                        <span class="btn-estado">
                            <span class="dot" style="background:<?= $est['dot'] ?>;"></span>
                            Estado del pedido
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Leyenda + salir -->
<div class="pp-legend">
    <div style="display:flex;align-items:center;flex-wrap:wrap;gap:.4rem .9rem;">
        <span class="pp-legend-title">Convenciones</span>
        <div class="pp-legend-items">
            <span class="pp-legend-item">
                <span class="dot" style="background:#dc2626;"></span> Pedido sin procesar
            </span>
            <span class="pp-legend-item">
                <span class="dot" style="background:#ca8a04;"></span> Pedido le faltan ítems
            </span>
            <span class="pp-legend-item">
                <span class="dot" style="background:#16a34a;"></span> Pedido procesado sin cerrar
            </span>
        </div>
    </div>
    <a href="/dashboard_home"
       style="background:#e0e0e0;border:1px solid #999;border-radius:.35rem;
              padding:.3rem .8rem;font-size:.78rem;font-weight:600;color:#333;
              text-decoration:none;display:flex;align-items:center;gap:.3rem;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
        </svg>
        Salir de pedidos
    </a>
</div>

<script>
// Plantilla para construir una tarjeta desde JSON
function buildCard(p) {
    const nrodoc   = p.nrodoc.padStart(8, '0');
    const total    = parseInt(p.total_items)    || 0;
    const completos= parseInt(p.items_completos)|| 0;
    const iniciados= parseInt(p.items_iniciados)|| 0;

    let dot;
    if (total === 0 || iniciados === 0)   dot = '#dc2626';
    else if (completos >= total)           dot = '#16a34a';
    else                                   dot = '#ca8a04';

    const esc = s => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');

    return `<div class="pp-card" id="card-${esc(p.nrodoc)}"
                 onclick="abrirPedido('${esc(p.nrodoc)}')" title="Ver detalle del pedido">
        <div class="pp-card-title">${esc(p.nomcli)}</div>
        <div class="pp-card-info">
            Documento: ${esc(nrodoc)}<br>
            Fecha Entrega: ${esc(p.fecentrega_fmt)}
        </div>
        <div class="pp-card-actions">
            <button class="btn-cerrar"
                    onclick="cerrarPedido(event,'${esc(p.nrodoc)}')" title="Cerrar pedido">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="3" stroke-linecap="round">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                Cerrar pedido
            </button>
            <span class="btn-estado">
                <span class="dot" style="background:${dot};"></span>
                Estado del pedido
            </span>
        </div>
    </div>`;
}

function abrirPedido(nrodoc) {
    window.location.href = '/planilla-pedidos/' + encodeURIComponent(nrodoc) + '/detalle';
}

function cerrarPedido(e, nrodoc) {
    e.stopPropagation();
    const card = document.getElementById('card-' + nrodoc);
    if (!card) return;
    card.classList.add('cerrando');

    fetch('/planilla-pedidos/cerrar/' + encodeURIComponent(nrodoc), { method: 'POST' })
        .then(r => r.json())
        .then(() => {
            // Desaparece tras 60 s
            setTimeout(() => card.remove(), 60000);
            checkEmpty();
        })
        .catch(() => card.classList.remove('cerrando'));
}

function checkEmpty() {
    const grid = document.getElementById('pedidos-grid');
    if (!grid) return;
    const cards = grid.querySelectorAll('.pp-card:not(.cerrando)');
    const empties = grid.querySelectorAll('.pp-empty');
    if (cards.length === 0 && empties.length === 0) {
        grid.insertAdjacentHTML('beforeend',
            '<div class="pp-empty" style="grid-column:1/-1">No hay pedidos pendientes por alistar.</div>');
    }
}

// Polling: cada 30 s solicita la lista actualizada
let knownIds = new Set([...document.querySelectorAll('.pp-card')].map(c => c.id.replace('card-','')));

function pollPedidos() {
    const spinner = document.getElementById('spinner');
    const txt     = document.getElementById('refresh-txt');
    spinner.classList.add('active');

    fetch('/api/planilla-pedidos')
        .then(r => r.json())
        .then(data => {
            const grid      = document.getElementById('pedidos-grid');
            const serverIds = new Set(data.map(p => String(p.nrodoc)));

            data.forEach(p => {
                const id   = String(p.nrodoc);
                const card = document.getElementById('card-' + id);

                if (!knownIds.has(id)) {
                    // Nueva tarjeta
                    const empDiv = grid.querySelector('.pp-empty');
                    if (empDiv) empDiv.remove();
                    grid.insertAdjacentHTML('beforeend', buildCard(p));
                    knownIds.add(id);
                } else if (card) {
                    // Actualizar dot de estado en tarjeta existente
                    const total     = parseInt(p.total_items)    || 0;
                    const completos = parseInt(p.items_completos) || 0;
                    const iniciados = parseInt(p.items_iniciados) || 0;
                    let dot;
                    if (total === 0 || iniciados === 0) dot = '#dc2626';
                    else if (completos >= total)         dot = '#16a34a';
                    else                                 dot = '#ca8a04';
                    const dotEl = card.querySelector('.btn-estado .dot');
                    if (dotEl) dotEl.style.background = dot;
                }
            });

            // Quitar pedidos que ya no están (cerrados definitivamente)
            knownIds.forEach(id => {
                if (!serverIds.has(id)) {
                    const card = document.getElementById('card-' + id);
                    if (card && !card.classList.contains('cerrando')) card.remove();
                    knownIds.delete(id);
                }
            });

            checkEmpty();
            const now = new Date();
            txt.textContent = 'Actualizado ' + now.getHours().toString().padStart(2,'0')
                            + ':' + now.getMinutes().toString().padStart(2,'0')
                            + ':' + now.getSeconds().toString().padStart(2,'0');
        })
        .catch(() => { txt.textContent = 'Error al actualizar'; })
        .finally(() => spinner.classList.remove('active'));
}

setInterval(pollPedidos, 30000);
</script>
