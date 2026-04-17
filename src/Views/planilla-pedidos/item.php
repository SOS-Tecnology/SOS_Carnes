<?php
$cantidad = (float)$item['cantidad'];
$cantent  = (float)$item['cantent'];
$pct      = $cantidad > 0 ? min(round($cantent / $cantidad * 100), 100) : 0;

if ($cantent <= 0) {
    $dotColor = '#dc2626';
    $dotLabel = 'Sin procesar';
} elseif ($cantent >= $cantidad && $cantidad > 0) {
    $dotColor = '#16a34a';
    $dotLabel = 'Completo';
} else {
    $dotColor = '#ca8a04';
    $dotLabel = 'Parcial';
}
?>

<style>
    .item-wrap { max-width: 520px; margin: 0 auto; }
    .item-header {
        background: #1a4dad;
        color: #fff;
        padding: .65rem 1.2rem;
        border-radius: .5rem .5rem 0 0;
        display: flex;
        align-items: center;
        gap: .7rem;
        font-weight: 700;
        font-size: .9rem;
    }
    .item-body {
        background: #f0f2f8;
        border: 1px solid #b0b8d0;
        border-top: none;
        border-radius: 0 0 .5rem .5rem;
        padding: 1.5rem 1.2rem;
    }
    .item-info-card {
        background: #fff;
        border-radius: .4rem;
        border: 1px solid #d0d8ec;
        padding: 1rem 1.2rem;
        margin-bottom: 1.2rem;
        box-shadow: 0 2px 6px rgba(0,0,0,.06);
    }
    .item-cod {
        font-size: .72rem;
        color: #666;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: .15rem;
    }
    .item-desc {
        font-size: 1.05rem;
        font-weight: 700;
        color: #1a2e1a;
        margin-bottom: .7rem;
        line-height: 1.25;
    }
    .item-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: .5rem;
        text-align: center;
    }
    .stat-box {
        background: #f5f7ff;
        border: 1px solid #dce4f8;
        border-radius: .35rem;
        padding: .5rem .4rem;
    }
    .stat-label {
        font-size: .65rem;
        color: #888;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: .15rem;
    }
    .stat-value {
        font-size: .95rem;
        font-weight: 700;
        color: #222;
        font-family: monospace;
    }
    .progress-section { margin-bottom: 1.2rem; }
    .progress-label {
        display: flex;
        justify-content: space-between;
        font-size: .75rem;
        color: #555;
        font-weight: 500;
        margin-bottom: .3rem;
    }
    .progress-bar-wrap {
        width: 100%;
        height: 12px;
        background: #ddd;
        border-radius: 9px;
        overflow: hidden;
    }
    .progress-bar-fill {
        height: 100%;
        border-radius: 9px;
        transition: width .4s;
    }
    .form-section label {
        display: block;
        font-size: .8rem;
        font-weight: 700;
        color: #333;
        margin-bottom: .4rem;
        text-transform: uppercase;
        letter-spacing: .03em;
    }
    .form-section input[type="number"] {
        width: 100%;
        padding: .65rem .9rem;
        font-size: 1.3rem;
        font-weight: 700;
        font-family: monospace;
        border: 2px solid #b0b8d0;
        border-radius: .4rem;
        text-align: center;
        color: #222;
        background: #fff;
        box-sizing: border-box;
        transition: border-color .15s;
    }
    .form-section input[type="number"]:focus {
        outline: none;
        border-color: #1a4dad;
        box-shadow: 0 0 0 3px rgba(26,77,173,.15);
    }
    .hint {
        font-size: .72rem;
        color: #888;
        margin-top: .35rem;
        text-align: center;
    }
    .btn-guardar {
        display: block;
        width: 100%;
        margin-top: 1.2rem;
        padding: .8rem;
        background: #1a4dad;
        color: #fff;
        border: none;
        border-radius: .4rem;
        font-size: 1rem;
        font-weight: 700;
        cursor: pointer;
        letter-spacing: .03em;
        transition: background .15s, transform .1s;
        box-shadow: 0 3px 8px rgba(26,77,173,.3);
    }
    .btn-guardar:hover { background: #163fa0; transform: translateY(-1px); }
    .btn-guardar:active { transform: translateY(0); }
    .det-footer {
        display: flex;
        justify-content: flex-end;
        margin-top: .9rem;
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
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .2rem .6rem;
        border-radius: 9px;
        font-size: .72rem;
        font-weight: 600;
        background: rgba(255,255,255,.18);
    }
    .dot-sm {
        width: 9px; height: 9px;
        border-radius: 50%;
        border: 1.5px solid rgba(255,255,255,.5);
        flex-shrink: 0;
    }
</style>

<div class="item-wrap">

    <!-- Header -->
    <div class="item-header">
        <a href="/planilla-pedidos/<?= urlencode($pedido['nrodoc']) ?>/detalle"
           style="display:flex;align-items:center;gap:.25rem;color:#fff;text-decoration:none;
                  background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);
                  border-radius:.3rem;padding:.25rem .55rem;font-size:.75rem;">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver
        </a>
        <span style="flex:1;">Alistar ítem</span>
        <span class="status-pill">
            <span class="dot-sm" style="background:<?= $dotColor ?>;"></span>
            <?= $dotLabel ?>
        </span>
    </div>

    <!-- Body -->
    <div class="item-body">

        <!-- Info del producto -->
        <div class="item-info-card">
            <div class="item-cod"><?= htmlspecialchars($item['codart']) ?></div>
            <div class="item-desc"><?= htmlspecialchars($item['descripcion']) ?></div>
            <div class="item-stats">
                <div class="stat-box">
                    <div class="stat-label">Solicitado</div>
                    <div class="stat-value"><?= number_format($cantidad, 3) ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Alistado</div>
                    <div class="stat-value" style="color:<?= $dotColor ?>;">
                        <?= number_format($cantent, 3) ?>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Diferencia</div>
                    <div class="stat-value" style="color:<?= $cantent >= $cantidad && $cantidad > 0 ? '#16a34a' : '#dc2626' ?>;">
                        <?= number_format(max($cantidad - $cantent, 0), 3) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Barra de progreso -->
        <div class="progress-section">
            <div class="progress-label">
                <span>Progreso</span>
                <span><?= $pct ?>%</span>
            </div>
            <div class="progress-bar-wrap">
                <div class="progress-bar-fill"
                     style="width:<?= $pct ?>%;
                            background:<?= $pct >= 100 ? '#16a34a' : ($pct > 0 ? '#ca8a04' : '#dc2626') ?>;"></div>
            </div>
        </div>

        <!-- Formulario -->
        <form method="POST"
              action="/planilla-pedidos/<?= urlencode($pedido['nrodoc']) ?>/item/<?= (int)$item['numreg'] ?>">
            <div class="form-section">
                <label for="cantent">Cantidad alistada</label>
                <input type="number"
                       id="cantent"
                       name="cantent"
                       min="0"
                       step="0.001"
                       value="<?= htmlspecialchars(number_format($cantent, 3, '.', '')) ?>"
                       autofocus
                       autocomplete="off">
                <div class="hint">Ingrese la cantidad total que ha sido alistada hasta ahora</div>
            </div>
            <button type="submit" class="btn-guardar">
                Guardar y volver al pedido
            </button>
        </form>

    </div>

    <!-- Footer -->
    <div class="det-footer">
        <a href="/planilla-pedidos/<?= urlencode($pedido['nrodoc']) ?>/detalle" class="btn-volver">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Cancelar
        </a>
    </div>

</div>
