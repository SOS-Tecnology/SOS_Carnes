<?php
$nrodocUrl   = urlencode($pedido['nrodoc']);
$nrodocFmt   = str_pad($pedido['nrodoc'], 8, '0', STR_PAD_LEFT);
$totalItems  = count($items);

// Normaliza comencpo por ítem
function normalizarComencpo(string $raw): string {
    $raw   = str_replace(['\r\n', '\r', '\n', '\\n'], "\n", $raw);
    $lines = array_filter(
        array_map(fn($l) => preg_replace('/[ \t]{2,}/', ' ', trim($l)), explode("\n", $raw)),
        fn($l) => $l !== ''
    );
    return implode("\n", $lines);
}
?>

<style>
    .prep-wrap { max-width: 860px; margin: 0 auto; }

    /* ── Header ── */
    .prep-header {
        background: #1a4dad; color: #fff;
        padding: .65rem 1.2rem;
        border-radius: .5rem .5rem 0 0;
        display: flex; align-items: center; gap: .7rem;
        font-weight: 700; font-size: .9rem; flex-wrap: wrap;
    }
    .prep-header .btn-back {
        display: flex; align-items: center; gap: .25rem; color: #fff;
        text-decoration: none; background: rgba(255,255,255,.15);
        border: 1px solid rgba(255,255,255,.3);
        border-radius: .3rem; padding: .25rem .55rem;
        font-size: .75rem; flex-shrink: 0;
    }
    .prep-header-info {
        display: flex; flex-wrap: wrap; gap: .15rem 1.2rem;
        font-size: .78rem; font-weight: 500; opacity: .92;
        margin-left: auto;
    }

    /* ── Body ── */
    .prep-body {
        background: #f0f2f8; border: 1px solid #b0b8d0;
        border-top: none; border-radius: 0 0 .5rem .5rem;
        padding: 1rem 1.2rem;
    }

    /* ── Item card ── */
    .item-card {
        background: #fff; border: 1px solid #d0d8ec;
        border-radius: .45rem; padding: .8rem 1rem;
        margin-bottom: .75rem;
        box-shadow: 0 2px 6px rgba(0,0,0,.05);
    }
    .item-card:last-child { margin-bottom: 0; }

    .item-card-top {
        display: flex; align-items: flex-start; gap: .8rem;
        margin-bottom: .5rem;
    }
    .item-num {
        background: #1a4dad; color: #fff;
        min-width: 26px; height: 26px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: .72rem; font-weight: 700; flex-shrink: 0;
    }
    .item-info { flex: 1; min-width: 0; }
    .item-cod  { font-size: .68rem; color: #888; font-weight: 600; letter-spacing: .04em; }
    .item-desc { font-size: .92rem; font-weight: 700; color: #1a2e1a; line-height: 1.25; }
    .item-obs  {
        background: #fefce8; border: 1px solid #fde68a;
        border-radius: .3rem; padding: .4rem .65rem;
        font-size: .78rem; color: #555; line-height: 1.5;
        white-space: pre-wrap; margin-bottom: .5rem;
    }

    /* ── Comparador + input ── */
    .item-row {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr auto;
        gap: .6rem;
        align-items: flex-end;
    }
    .cmp-box { background: #f5f7ff; border: 1px solid #dce4f8; border-radius: .35rem; padding: .45rem .6rem; text-align: center; }
    .cmp-label { font-size: .62rem; color: #888; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; margin-bottom: .1rem; }
    .cmp-val   { font-size: .92rem; font-weight: 700; color: #222; font-family: monospace; }
    .cmp-val.green { color: #16a34a; }
    .cmp-val.amber { color: #ca8a04; }
    .cmp-val.red   { color: #dc2626; }

    .peso-group label {
        display: block; font-size: .7rem; font-weight: 700; color: #1a4dad;
        text-transform: uppercase; letter-spacing: .03em; margin-bottom: .22rem;
    }
    .peso-input {
        width: 100%; padding: .55rem .75rem;
        font-size: 1rem; font-weight: 700;
        border: 2px solid #1a4dad; border-radius: .4rem;
        color: #1a2e1a; background: #f0f4ff;
        box-sizing: border-box; text-align: right;
        transition: border-color .15s, box-shadow .15s;
    }
    .peso-input:focus {
        outline: none; border-color: #163fa0;
        box-shadow: 0 0 0 3px rgba(26,77,173,.18);
        background: #fff;
    }

    .btn-sticker {
        display: flex; align-items: center; gap: .35rem;
        background: #f59e0b; color: #fff; border: none;
        border-radius: .4rem; padding: .55rem .9rem;
        font-size: .8rem; font-weight: 600; cursor: pointer;
        letter-spacing: .02em; height: fit-content;
        box-shadow: 0 2px 6px rgba(245,158,11,.3);
        transition: background .15s, transform .1s;
    }
    .btn-sticker:hover { background: #d97706; transform: translateY(-1px); }
    .btn-sticker:active { transform: translateY(0); }

    /* ── Footer form ── */
    .prep-footer {
        margin-top: 1rem; display: flex;
        align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: .5rem;
    }
    .btn-generar {
        display: flex; align-items: center; gap: .5rem;
        background: #15803d; color: #fff; border: none;
        border-radius: .45rem; padding: .7rem 1.6rem;
        font-size: .95rem; font-weight: 700; cursor: pointer;
        letter-spacing: .02em;
        box-shadow: 0 3px 10px rgba(21,128,61,.3);
        transition: background .15s, transform .1s;
    }
    .btn-generar:hover { background: #166534; transform: translateY(-1px); }
    .btn-volver {
        display: flex; align-items: center; gap: .3rem;
        background: #e0e0e0; border: 1px solid #999;
        border-radius: .35rem; padding: .35rem .8rem;
        font-size: .78rem; font-weight: 600; color: #333;
        text-decoration: none; transition: background .15s;
    }
    .btn-volver:hover { background: #d0d0d0; }

    /* ── Tablet ── */
    @media (max-width: 1280px) {
        .prep-wrap    { max-width: 100%; }
        .prep-body    { padding: .85rem; }
        .item-card    { padding: 1rem; }
        .item-desc    { font-size: 1rem; }
        .cmp-val      { font-size: 1rem; }
        .cmp-box      { padding: .55rem .6rem; }
        .peso-input   { min-height: 52px; font-size: 1.1rem; padding: .6rem .85rem; }
        .btn-generar  { min-height: 52px; font-size: 1rem; padding: .75rem 1.8rem; }
        .btn-volver   { min-height: 44px; font-size: .88rem; padding: .5rem 1.1rem; }
        .btn-back     { min-height: 36px; }
        .btn-sticker  { min-height: 52px; padding: .65rem 1.1rem; }
    }
    @media (max-width: 640px) {
        .item-row { grid-template-columns: 1fr 1fr; }
        .item-row .peso-group { grid-column: 1 / -1; }
        .item-row .btn-sticker { grid-column: 1 / -1; }
        .prep-header-info { margin-left: 0; }
    }
</style>

<div class="prep-wrap">

    <!-- Header -->
    <div class="prep-header">
        <a href="/preparacion-pedido" class="btn-back">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver
        </a>
        <span><?= htmlspecialchars($pedido['nomcli']) ?></span>
        <div class="prep-header-info">
            <span>Doc PV: <?= htmlspecialchars($nrodocFmt) ?></span>
            <span>|</span>
            <span>Entrega: <?= htmlspecialchars($pedido['fecentrega_fmt']) ?></span>
            <span>|</span>
            <span>Canal: <?= htmlspecialchars($pedido['codtipocli']) ?></span>
            <span>|</span>
            <span><?= $totalItems ?> ítem<?= $totalItems !== 1 ? 's' : '' ?></span>
        </div>
    </div>

    <!-- Form -->
    <form method="POST" action="/preparacion-pedido/<?= $nrodocUrl ?>/preparar"
          id="form-prep" onsubmit="return validarForm()">

        <div class="prep-body">

            <?php foreach ($items as $idx => $it):
                $obs          = normalizarComencpo($it['comencpo'] ?? '');
                $solicitado   = (float)$it['cantidad'];
                $alistado     = (float)$it['total_alistado'];
                $diff         = $solicitado - $alistado;
                $diffClass    = $diff > 0.001 ? 'amber' : ($diff < -0.001 ? 'red' : 'green');
                $propuesta    = number_format($alistado, 3, '.', '');
            ?>
            <div class="item-card">
                <!-- Hidden fields para identificar el ítem -->
                <input type="hidden" name="registros[]" value="<?= htmlspecialchars($it['registro']) ?>">

                <div class="item-card-top">
                    <div class="item-num"><?= $idx + 1 ?></div>
                    <div class="item-info">
                        <div class="item-cod"><?= htmlspecialchars($it['codart']) ?></div>
                        <div class="item-desc"><?= htmlspecialchars($it['descripcion']) ?></div>
                    </div>
                </div>

                <?php if ($obs !== ''): ?>
                    <div class="item-obs"><?= htmlspecialchars($obs) ?></div>
                <?php endif; ?>

                <div class="item-row">
                    <div class="cmp-box">
                        <div class="cmp-label">Solicitado</div>
                        <div class="cmp-val"><?= number_format($solicitado, 3) ?></div>
                    </div>
                    <div class="cmp-box">
                        <div class="cmp-label">Alistado</div>
                        <div class="cmp-val <?= $diffClass ?>"><?= number_format($alistado, 3) ?></div>
                    </div>
                    <div class="peso-group">
                        <label for="peso_<?= $idx ?>">Peso definitivo (kg)</label>
                        <input type="number"
                               id="peso_<?= $idx ?>"
                               name="pesos[]"
                               class="peso-input"
                               step="0.001"
                               min="0"
                               value="<?= $propuesta ?>"
                               autocomplete="off"
                               onfocus="this.select()"
                               required>
                    </div>
                    <button type="button" 
                            class="btn-sticker"
                            onclick="imprimirSticker(event, '<?= htmlspecialchars(addslashes($it['codart'])) ?>', '<?= htmlspecialchars(addslashes($it['descripcion'])) ?>', '<?= $idx ?>')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4H9a2 2 0 00-2 2v2a2 2 0 002 2h6a2 2 0 002-2v-2a2 2 0 00-2-2zm0 0h6a2 2 0 002-2v-4a2 2 0 00-2-2h-.5"/>
                        </svg>
                        Sticker
                    </button>
                </div>
            </div>
            <?php endforeach; ?>

        </div><!-- /prep-body -->

        <div class="prep-footer">
            <a href="/preparacion-pedido" class="btn-volver">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                Cancelar
            </a>
            <button type="submit" class="btn-generar">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Confirmar y generar AP
            </button>
        </div>

    </form>

</div>

<script>
function validarForm() {
    const inputs = document.querySelectorAll('.peso-input');
    for (const inp of inputs) {
        if (inp.value === '' || isNaN(parseFloat(inp.value)) || parseFloat(inp.value) < 0) {
            inp.focus();
            inp.style.borderColor = '#dc2626';
            alert('Ingrese el peso definitivo para todos los ítems.');
            return false;
        }
        inp.style.borderColor = '';
    }
    return confirm(
        'Va a generar el documento AP para el pedido <?= htmlspecialchars(addslashes($nrodocFmt)) ?>.\n' +
        '¿Confirma los pesos ingresados?'
    );
}

/**
 * Genera e imprime el sticker para un item
 * @param {Event} event - Evento del botón
 * @param {string} codart - Código del artículo
 * @param {string} descripcion - Descripción del producto
 * @param {number} idx - Índice del item (para obtener el peso del input)
 */
function imprimirSticker(event, codart, descripcion, idx) {
    event.preventDefault();
    
    // Obtener el peso del input
    const pesoInput = document.getElementById(`peso_${idx}`);
    if (!pesoInput) {
        mostrarAlertaAmable('Error', '⚠ No se encontró el campo de peso.', 'error');
        return;
    }
    
    const peso = pesoInput.value.trim();
    
    // Validar que el peso sea válido y mayor a 0
    if (peso === '' || isNaN(parseFloat(peso))) {
        mostrarAlertaAmable(
            'Campo incompleto',
            '⚠ Por favor verifica el campo <strong>Peso</strong>: debe ser un número válido mayor a 0',
            'warning'
        );
        pesoInput.focus();
        return;
    }
    
    const pesoNum = parseFloat(peso);
    if (pesoNum <= 0) {
        mostrarAlertaAmable(
            'No se puede generar el sticker',
            '⚠ No es posible generar un sticker con peso de <strong>0 kg</strong>. Por favor ingresa el peso definitivo del producto.',
            'warning'
        );
        pesoInput.focus();
        return;
    }
    
    if (pesoNum > 9999) {
        mostrarAlertaAmable(
            'Peso inválido',
            '⚠ El peso parece demasiado alto (máximo 9999 kg). Verifica el valor ingresado.',
            'warning'
        );
        pesoInput.focus();
        return;
    }
    
    // Construir URL para generar el sticker
    const url = new URL('/sticker/generar', window.location.origin);
    url.searchParams.append('codart', codart);
    url.searchParams.append('desc', descripcion);
    url.searchParams.append('peso', peso);
    
    // Abrir en nueva ventana/pestaña para impresión
    const ventana = window.open(url.toString(), '_blank', 'width=400,height=600,menubar=no,toolbar=no');
    if (ventana) {
        ventana.focus();
    }
}

/**
 * Muestra una alerta con formato amable
 */
function mostrarAlertaAmable(titulo, mensaje, tipo = 'info') {
    // Crear contenedor si no existe
    let container = document.getElementById('alertas-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'alertas-container';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
        `;
        document.body.appendChild(container);
    }
    
    // Crear elemento de alerta
    const alertDiv = document.createElement('div');
    const colores = {
        'success': { bg: '#d4edda', border: '#c3e6cb', text: '#155724' },
        'warning': { bg: '#fff3cd', border: '#ffeaa7', text: '#856404' },
        'error': { bg: '#f8d7da', border: '#f5c6cb', text: '#721c24' },
        'info': { bg: '#d1ecf1', border: '#bee5eb', text: '#0c5460' }
    };
    const color = colores[tipo] || colores['info'];
    
    alertDiv.innerHTML = `
        <div style="
            background: ${color.bg};
            border: 1px solid ${color.border};
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 10px;
            color: ${color.text};
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        ">
            <strong style="display: block; margin-bottom: 5px;">${titulo}</strong>
            <div>${mensaje}</div>
        </div>
    `;
    
    container.appendChild(alertDiv);
    
    // Auto-remover después de 5 segundos
    setTimeout(() => {
        alertDiv.style.opacity = '0';
        alertDiv.style.transition = 'opacity 0.3s';
        setTimeout(() => alertDiv.remove(), 300);
    }, 5000);
}

// Auto-foco al primer input
document.addEventListener('DOMContentLoaded', () => {
    const first = document.querySelector('.peso-input');
    if (first) first.focus();
});
</script>
