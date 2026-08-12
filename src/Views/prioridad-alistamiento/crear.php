<?php
$old = $_SESSION['old'] ?? [];
unset($_SESSION['old']);
?>

<?php if (!empty($_SESSION['errors'])): ?>
    <div class="pc-alert pc-alert-err">
        <?php foreach ($_SESSION['errors'] as $e): ?>
            <div>✖ <?= htmlspecialchars($e) ?></div>
        <?php endforeach; unset($_SESSION['errors']); ?>
    </div>
<?php endif; ?>

<style>
    .pc-alert { border-radius:.5rem; padding:.6rem 1rem; margin-bottom:.8rem; font-size:.85rem; font-weight:600; }
    .pc-alert-err { background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5; }

    .pc-header {
        background:#1a4dad; color:#fff; padding:.6rem 1.2rem;
        border-radius:.5rem .5rem 0 0; display:flex; align-items:center;
        gap:.9rem; font-weight:600; font-size:.95rem;
    }
    .pc-volver {
        background:#3b62b8; color:#fff; border:1px solid #5d7fc9;
        border-radius:.35rem; padding:.25rem .7rem; font-size:.76rem;
        font-weight:700; text-decoration:none; white-space:nowrap;
    }
    .pc-volver:hover { background:#2d539f; }
    .pc-body {
        background:#e8eaf0; border:1px solid #b0b8d0; border-top:none;
        border-radius:0 0 .5rem .5rem; padding:1rem;
    }
    .pc-card {
        background:#fff; border:1px solid #d0d8ec; border-radius:.5rem;
        box-shadow:0 2px 8px rgba(0,0,0,.06); padding:1.2rem; max-width:640px; margin:0 auto;
    }
    .pc-campo { margin-bottom:1rem; }
    .pc-campo label {
        display:block; font-size:.72rem; font-weight:700; color:#1a4dad;
        letter-spacing:.02em; text-transform:uppercase; margin-bottom:.3rem;
    }
    .pc-campo select,
    .pc-campo textarea {
        width:100%; border:1px solid #c3cde6; border-radius:.4rem;
        padding:.55rem .7rem; font-size:.85rem; color:#222; outline:none;
        font-family:inherit;
    }
    .pc-campo select:focus,
    .pc-campo textarea:focus { border-color:#1a4dad; box-shadow:0 0 0 2px rgba(26,77,173,.12); }
    .pc-campo textarea { min-height:100px; resize:vertical; }
    .pc-hint { font-size:.72rem; color:#888; margin-top:.25rem; }
    .pc-pedido-info {
        display:none; background:#f0f4ff; border:1px solid #c3cde6; border-radius:.4rem;
        padding:.6rem .8rem; font-size:.8rem; color:#1a2e6a; margin-top:.5rem;
    }
    .pc-pedido-info.show { display:block; }
    .pc-pedido-warn {
        display:none; background:#fee2e2; border:1px solid #fca5a5; border-radius:.4rem;
        padding:.5rem .8rem; font-size:.76rem; color:#b91c1c; margin-top:.5rem; font-weight:600;
    }
    .pc-pedido-warn.show { display:block; }
    .pc-actions { display:flex; justify-content:flex-end; gap:.7rem; margin-top:1.2rem; }
    .pc-btn-cancel {
        background:#e5e7eb; border:none; border-radius:.4rem; padding:.55rem 1.1rem;
        font-size:.82rem; font-weight:700; color:#374151; cursor:pointer; text-decoration:none;
        display:inline-flex; align-items:center;
    }
    .pc-btn-cancel:hover { background:#d7dae0; }
    .pc-btn-enviar {
        background:#f59e0b; color:#1a1305; border:none; border-radius:.4rem;
        padding:.55rem 1.3rem; font-size:.82rem; font-weight:700; cursor:pointer;
    }
    .pc-btn-enviar:hover { background:#d97706; color:#fff; }
    .pc-btn-enviar:disabled { background:#e5c98a; color:#8a7239; cursor:not-allowed; }
    .pc-spinner {
        display:inline-block; width:11px; height:11px; border-radius:50%;
        border:2px solid #c3cde6; border-top-color:#1a4dad; margin-left:.4rem;
        animation:pc-spin 1s linear infinite; vertical-align:middle;
    }
    @keyframes pc-spin { to { transform:rotate(360deg); } }
</style>

<div class="pc-header">
    <a href="/prioridad-alistamiento" class="pc-volver">&lt; Volver</a>
    <span>Nueva Solicitud de Prioridad</span>
</div>

<div class="pc-body">
    <div class="pc-card">
        <form method="POST" action="/prioridad-alistamiento/guardar" id="pc-form">
            <div class="pc-campo">
                <label for="pc-canal">Canal</label>
                <select id="pc-canal" name="canal" required onchange="pcCargarPedidos()">
                    <option value="" disabled <?= empty($old['canal']) ? 'selected' : '' ?>>Seleccionar canal…</option>
                    <?php foreach ($canales as $c): ?>
                        <option value="<?= htmlspecialchars($c['codigotc']) ?>"
                            <?= (($old['canal'] ?? '') === $c['codigotc']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nombretc']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="pc-campo">
                <label for="pc-documento">
                    Pedido (PV) en alistamiento
                    <span class="pc-spinner" id="pc-spinner" style="display:none;"></span>
                </label>
                <select id="pc-documento" name="documento" required disabled>
                    <option value="" selected>Seleccione primero un canal…</option>
                </select>
                <input type="hidden" name="prefijo" id="pc-prefijo" value="">
                <div class="pc-hint">Solo se listan pedidos activos en Planilla de Pedidos (en alistamiento).</div>
                <div class="pc-pedido-info" id="pc-pedido-info"></div>
                <div class="pc-pedido-warn" id="pc-pedido-warn">
                    ⚠ Este pedido ya tiene una alerta de prioridad activa.
                </div>
            </div>

            <div class="pc-campo">
                <label for="pc-comentario">Comentario de la urgencia</label>
                <textarea id="pc-comentario" name="comentario" required maxlength="500"
                          placeholder="Describa la emergencia y la prioridad requerida..."><?= htmlspecialchars($old['comentario'] ?? '') ?></textarea>
            </div>

            <div class="pc-actions">
                <a href="/prioridad-alistamiento" class="pc-btn-cancel">Cancelar</a>
                <button type="submit" class="pc-btn-enviar" id="pc-btn-enviar">Enviar solicitud</button>
            </div>
        </form>
    </div>
</div>

<script>
const pcPedidosCache = {};

function pcCargarPedidos() {
    const canal   = document.getElementById('pc-canal').value;
    const selDoc  = document.getElementById('pc-documento');
    const spinner = document.getElementById('pc-spinner');
    const info    = document.getElementById('pc-pedido-info');
    const warn    = document.getElementById('pc-pedido-warn');

    info.classList.remove('show');
    warn.classList.remove('show');
    document.getElementById('pc-prefijo').value = '';

    if (!canal) {
        selDoc.disabled = true;
        selDoc.innerHTML = '<option value="" selected>Seleccione primero un canal…</option>';
        return;
    }

    selDoc.disabled = true;
    selDoc.innerHTML = '<option value="" selected>Cargando pedidos…</option>';
    spinner.style.display = 'inline-block';

    fetch('/api/prioridad-alistamiento/pedidos?canal=' + encodeURIComponent(canal))
        .then(r => r.json())
        .then(data => {
            pcPedidosCache[canal] = data;
            if (!data.length) {
                selDoc.innerHTML = '<option value="" selected>No hay pedidos en alistamiento para este canal</option>';
                selDoc.disabled = true;
                return;
            }
            let html = '<option value="" disabled selected>Seleccionar pedido…</option>';
            data.forEach(p => {
                const doc = String(p.documento).padStart(8, '0');
                html += `<option value="${p.documento}" data-prefijo="${p.prefijo}" data-tiene="${p.ya_tiene_prioridad}">
                            PV ${doc} — ${p.nomcli} (Entrega: ${p.fecentrega_fmt})
                         </option>`;
            });
            selDoc.innerHTML = html;
            selDoc.disabled = false;
        })
        .catch(() => {
            selDoc.innerHTML = '<option value="" selected>Error al cargar pedidos</option>';
        })
        .finally(() => { spinner.style.display = 'none'; });
}

document.getElementById('pc-documento').addEventListener('change', function () {
    const opt  = this.options[this.selectedIndex];
    const info = document.getElementById('pc-pedido-info');
    const warn = document.getElementById('pc-pedido-warn');
    const btn  = document.getElementById('pc-btn-enviar');

    document.getElementById('pc-prefijo').value = opt.dataset.prefijo || '';

    if (!opt.value) {
        info.classList.remove('show');
        warn.classList.remove('show');
        return;
    }

    info.textContent = opt.textContent.trim();
    info.classList.add('show');

    const yaTiene = opt.dataset.tiene === '1';
    warn.classList.toggle('show', yaTiene);
    btn.disabled = yaTiene;
});

<?php if (!empty($old['canal'])): ?>
    // Re-seleccionar canal tras un error de validación
    document.addEventListener('DOMContentLoaded', function () {
        pcCargarPedidos();
        const wantedDoc = <?= json_encode($old['documento'] ?? '') ?>;
        if (wantedDoc) {
            const check = setInterval(function () {
                const sel = document.getElementById('pc-documento');
                if (!sel.disabled) {
                    sel.value = wantedDoc;
                    sel.dispatchEvent(new Event('change'));
                    clearInterval(check);
                }
            }, 150);
        }
    });
<?php endif; ?>
</script>
