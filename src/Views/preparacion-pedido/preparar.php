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
        grid-template-columns: 1fr 1fr 1.6fr;
        gap: .6rem;
        align-items: end;
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
    }
    @media (max-width: 640px) {
        .item-row { grid-template-columns: 1fr 1fr; }
        .item-row .peso-group { grid-column: 1 / -1; }
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

// Auto-foco al primer input
document.addEventListener('DOMContentLoaded', () => {
    const first = document.querySelector('.peso-input');
    if (first) first.focus();
});
</script>
