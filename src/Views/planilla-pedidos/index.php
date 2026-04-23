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

$summary = ['total' => count($pedidos), 'sinProcesar' => 0, 'faltanItems' => 0, 'listosCerrar' => 0];
foreach ($pedidos as $p) {
    $est = estadoPedido((int)$p['total_items'], (int)$p['items_completos'], (int)$p['items_iniciados']);
    if ($est['dot'] === '#dc2626') {
        $summary['sinProcesar']++;
    } elseif ($est['dot'] === '#ca8a04') {
        $summary['faltanItems']++;
    } elseif ($est['dot'] === '#16a34a') {
        $summary['listosCerrar']++;
    }
}

?>

<link rel="stylesheet" href="/css/planilla-pedidos.css">

<!-- Header tipo cliente -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem;flex-wrap:wrap;gap:.75rem;">
    <a href="/dashboard_home"
       class="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700"
       style="font-weight:600;color:#1f2937;text-decoration:none;">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Volver
    </a>
    <div class="pp-refresh">
        <span class="pp-spinner" id="spinner"></span>
        <span id="refresh-txt">Actualiza cada 30 s</span>
    </div>
</div>

<div class="pp-summary">
    <div class="pp-summary-top">
        <div>
            <span class="pp-page-title">Pedidos generados</span>
            <?php if (!empty($nomTipoCli)): ?>
                <span class="pp-page-subtitle"><?= htmlspecialchars($nomTipoCli) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div class="pp-stat-grid">
        <div class="pp-stat-card">
            <span class="pp-stat-value"><?= $summary['total'] ?></span>
            <span class="pp-stat-label">Total pedidos</span>
        </div>
        <div class="pp-stat-card">
            <span class="pp-stat-value"><?= $summary['sinProcesar'] ?></span>
            <span class="pp-stat-label">Sin procesar</span>
        </div>
        <div class="pp-stat-card">
            <span class="pp-stat-value"><?= $summary['faltanItems'] ?></span>
            <span class="pp-stat-label">Faltan ítems</span>
        </div>
        <div class="pp-stat-card">
            <span class="pp-stat-value"><?= $summary['listosCerrar'] ?></span>
            <span class="pp-stat-label">Listos p/ cerrar</span>
        </div>
    </div>
    <div class="pp-summary-controls">
        <div class="pp-search">
            <input id="search-input" type="search" placeholder="Buscar cliente o documento..." aria-label="Buscar cliente o documento" />
        </div>
        <div class="pp-filter-group">
            <button type="button" class="pp-filter-btn active" data-filter="all">Todos</button>
            <button type="button" class="pp-filter-btn" data-filter="sinprocesar">Sin procesar</button>
            <button type="button" class="pp-filter-btn" data-filter="incompletos">Incompletos</button>
            <button type="button" class="pp-filter-btn" data-filter="listos">Listos</button>
        </div>
    </div>
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
                <?php
                    $status = 'sinprocesar';
                    if ($est['dot'] === '#ca8a04') {
                        $status = 'incompletos';
                    } elseif ($est['dot'] === '#16a34a') {
                        $status = 'listos';
                    }
                    $searchText = htmlspecialchars(strtolower($p['nomcli'] . ' ' . $nrodoc), ENT_QUOTES, 'UTF-8');
                ?>
                <div class="pp-card"
                     id="card-<?= htmlspecialchars($p['nrodoc']) ?>"
                     data-completo="<?= ($est['dot'] === '#16a34a') ? '1' : '0' ?>"
                     data-status="<?= $status ?>"
                     data-search="<?= $searchText ?>"
                     onclick="abrirPedido('<?= htmlspecialchars($p['nrodoc']) ?>')"
                     title="Ver detalle del pedido">
                    <div class="pp-card-title"><?= htmlspecialchars($p['nomcli']) ?></div>
                    <div class="pp-card-info">
                        <span>Doc: <?= htmlspecialchars($nrodoc) ?></span>
                        <span>Entrega: <?= htmlspecialchars($p['fecentrega_fmt']) ?></span>
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
                        <span class="btn-estado" data-status="<?= $status ?>">
                            <span class="dot" style="background:<?= $est['dot'] ?>;"></span>
                            <?= htmlspecialchars($est['label']) ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Leyenda Integrada -->
        <div class="pp-legend-wrapper">
            <div class="pp-legend">
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
            <a href="/dashboard_home" class="pp-exit-btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Salir de pedidos
            </a>
        </div>
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

    const completo = (completos >= total && total > 0) ? '1' : '0';
    const status = dot === '#dc2626' ? 'sinprocesar' : dot === '#ca8a04' ? 'incompletos' : 'listos';
    const searchText = esc((p.nomcli || '') + ' ' + nrodoc).toLowerCase();
    const label = dot === '#dc2626' ? 'Sin procesar' : dot === '#ca8a04' ? 'Faltan ítems' : 'Procesado sin cerrar';
    return `<div class="pp-card" id="card-${esc(p.nrodoc)}"
                 data-completo="${completo}"
                 data-status="${status}"
                 data-search="${searchText}"
                 onclick="abrirPedido('${esc(p.nrodoc)}')" title="Ver detalle del pedido">
        <div class="pp-card-title">${esc(p.nomcli)}</div>
        <div class="pp-card-info">
            <span>Doc: ${esc(nrodoc)}</span>
            <span>Entrega: ${esc(p.fecentrega_fmt)}</span>
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
            <span class="btn-estado" data-status="${status}">
                <span class="dot" style="background:${dot};"></span>
                ${label}
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

    if (card.dataset.completo !== '1') {
        if (!confirm('Este pedido tiene ítems pendientes por alistar.\n¿Desea cerrarlo de todas formas?')) {
            return;
        }
    }

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
    const visibleCards = [...grid.querySelectorAll('.pp-card:not(.cerrando)')]
        .filter(card => window.getComputedStyle(card).display !== 'none');
    const empties = grid.querySelectorAll('.pp-empty');
    if (visibleCards.length === 0 && empties.length === 0) {
        grid.insertAdjacentHTML('beforeend',
            '<div class="pp-empty" style="grid-column:1/-1">No hay pedidos pendientes por alistar.</div>');
    } else if (visibleCards.length > 0) {
        empties.forEach(empty => empty.remove());
    }
}

function applyFilters() {
    const searchInput = document.getElementById('search-input');
    const query = (searchInput?.value || '').trim().toLowerCase();
    const filterBtn = document.querySelector('.pp-filter-btn.active');
    const activeFilter = filterBtn?.dataset.filter || 'all';
    const grid = document.getElementById('pedidos-grid');
    if (!grid) return;

    [...grid.querySelectorAll('.pp-card')].forEach(card => {
        const status = card.dataset.status || 'all';
        const searchText = (card.dataset.search || '').toLowerCase();
        const matchesStatus = activeFilter === 'all' || status === activeFilter;
        const matchesSearch = query === '' || searchText.includes(query);
        card.style.display = matchesStatus && matchesSearch ? '' : 'none';
    });

    checkEmpty();
}

function updateFilters() {
    applyFilters();
}

const searchInputEl = document.getElementById('search-input');
if (searchInputEl) {
    searchInputEl.addEventListener('input', updateFilters);
}

document.querySelectorAll('.pp-filter-btn').forEach(button => {
    button.addEventListener('click', () => {
        document.querySelectorAll('.pp-filter-btn').forEach(btn => btn.classList.remove('active'));
        button.classList.add('active');
        updateFilters();
    });
});

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
                    card.dataset.completo = (completos >= total && total > 0) ? '1' : '0';
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

            updateFilters();
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
