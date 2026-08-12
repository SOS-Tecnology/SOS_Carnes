<?php
$nrodocFmt = fn(string $n): string => str_pad($n, 8, '0', STR_PAD_LEFT);
?>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="pa-alert pa-alert-ok">✔ <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
<?php endif; ?>
<?php if (!empty($_SESSION['errors'])): ?>
    <div class="pa-alert pa-alert-err">
        <?php foreach ($_SESSION['errors'] as $e): ?>
            <div>✖ <?= htmlspecialchars($e) ?></div>
        <?php endforeach; unset($_SESSION['errors']); ?>
    </div>
<?php endif; ?>

<style>
    .pa-alert { border-radius:.5rem; padding:.6rem 1rem; margin-bottom:.8rem; font-size:.85rem; font-weight:600; }
    .pa-alert-ok  { background:#dcfce7; color:#15803d; border:1px solid #86efac; }
    .pa-alert-err { background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5; }

    .pa-header {
        background:#1a4dad; color:#fff; padding:.6rem 1.2rem;
        border-radius:.5rem .5rem 0 0; display:flex; align-items:center;
        justify-content:space-between; font-weight:600; font-size:.95rem; flex-wrap:wrap; gap:.6rem;
    }
    .pa-header small { font-weight:400; font-size:.75rem; opacity:.85; }
    .pa-nueva {
        background:#f59e0b; color:#1a1305; border:none; border-radius:.4rem;
        padding:.4rem .9rem; font-size:.8rem; font-weight:700; text-decoration:none;
    }
    .pa-nueva:hover { background:#d97706; color:#fff; }
    .pa-body {
        background:#e8eaf0; border:1px solid #b0b8d0; border-top:none;
        border-radius:0 0 .5rem .5rem; padding:1rem; min-height:200px;
    }
    .pa-filtros {
        background:#fff; border:1px solid #d0d8ec; border-radius:.5rem;
        padding:.6rem .9rem; margin-bottom:.9rem; display:flex;
        align-items:center; gap:.5rem; flex-wrap:wrap;
        box-shadow:0 2px 8px rgba(0,0,0,.06);
    }
    .pa-filtros .lbl {
        font-size:.7rem; font-weight:700; color:#1a4dad;
        letter-spacing:.02em; text-transform:uppercase; margin-right:.2rem;
    }
    .pa-tab {
        display:inline-block; padding:.32rem .75rem; border-radius:.35rem;
        border:1px solid #c3cde6; background:#fff; color:#1a4dad;
        font-size:.76rem; font-weight:700; text-decoration:none;
    }
    .pa-tab:hover { background:#f0f4ff; }
    .pa-tab.activo { background:#1a4dad; color:#fff; border-color:#1a4dad; }

    .pa-tabla-wrap {
        background:#fff; border:1px solid #d0d8ec; border-radius:.5rem;
        box-shadow:0 2px 8px rgba(0,0,0,.06); overflow:auto;
    }
    .pa-tabla { width:100%; border-collapse:collapse; font-size:.8rem; }
    .pa-tabla thead th {
        background:#f0f4ff; color:#1a4dad; font-size:.7rem; font-weight:700;
        text-transform:uppercase; letter-spacing:.02em; text-align:left;
        padding:.5rem .7rem; border-bottom:1px solid #d0d8ec; white-space:nowrap;
    }
    .pa-tabla tbody td { padding:.5rem .7rem; border-bottom:1px solid #eef1f8; color:#222; vertical-align:top; }
    .pa-tabla tbody tr:hover { background:#f7f9fe; }
    .pa-doc { font-family:monospace; font-weight:700; color:#1a2e6a; white-space:nowrap; }
    .pa-coment { max-width:340px; white-space:pre-wrap; font-size:.78rem; color:#333; }
    .pa-badge {
        display:inline-block; padding:.18rem .6rem; border-radius:999px;
        font-size:.7rem; font-weight:700; white-space:nowrap;
    }
    .pa-meta { font-size:.7rem; color:#888; white-space:nowrap; }
    .pa-btn-cancel {
        background:#dc2626; color:#fff; border:none; border-radius:.35rem;
        padding:.3rem .65rem; font-size:.72rem; font-weight:700; cursor:pointer;
    }
    .pa-btn-cancel:hover { background:#b91c1c; }
    .pa-empty { text-align:center; color:#888; font-size:.85rem; padding:2rem 0; }

    .pa-modal-bg {
        display:none; position:fixed; inset:0; background:rgba(15,23,42,.5);
        z-index:60; align-items:center; justify-content:center;
    }
    .pa-modal-bg.open { display:flex; }
    .pa-modal {
        background:#fff; border-radius:.6rem; padding:1.2rem; width:100%; max-width:420px;
        box-shadow:0 20px 45px rgba(0,0,0,.25);
    }
    .pa-modal h4 { margin:0 0 .5rem 0; font-size:.95rem; color:#1a2e6a; }
    .pa-modal p { margin:0 0 .7rem 0; font-size:.78rem; color:#555; }
    .pa-modal textarea {
        width:100%; border:1px solid #c3cde6; border-radius:.4rem; padding:.5rem .6rem;
        font-size:.82rem; min-height:80px; outline:none; resize:vertical;
    }
    .pa-modal textarea:focus { border-color:#1a4dad; box-shadow:0 0 0 2px rgba(26,77,173,.12); }
    .pa-modal-actions { display:flex; justify-content:flex-end; gap:.6rem; margin-top:.9rem; }
    .pa-modal-cancel {
        background:#e5e7eb; border:none; border-radius:.4rem; padding:.45rem .9rem;
        font-size:.8rem; font-weight:700; color:#374151; cursor:pointer;
    }
    .pa-modal-confirm {
        background:#dc2626; color:#fff; border:none; border-radius:.4rem;
        padding:.45rem .9rem; font-size:.8rem; font-weight:700; cursor:pointer;
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

<div class="pa-header">
    <span>Prioridad de Alistamiento</span>
    <a href="/prioridad-alistamiento/crear" class="pa-nueva">+ Nueva solicitud</a>
</div>

<div class="pa-body">

    <div class="pa-filtros">
        <span class="lbl">Ver:</span>
        <a href="/prioridad-alistamiento?estado=A" class="pa-tab <?= $estadoFiltro === 'A' ? 'activo' : '' ?>">Activas</a>
        <a href="/prioridad-alistamiento?estado=C" class="pa-tab <?= $estadoFiltro === 'C' ? 'activo' : '' ?>">Cerradas</a>
        <a href="/prioridad-alistamiento?estado="  class="pa-tab <?= $estadoFiltro === ''  ? 'activo' : '' ?>">Todas</a>
    </div>

    <?php if (empty($solicitudes)): ?>
        <div class="pa-empty">No hay solicitudes para mostrar.</div>
    <?php else: ?>
    <div class="pa-tabla-wrap">
        <table class="pa-tabla">
            <thead>
                <tr>
                    <th>Documento</th>
                    <th>Cliente</th>
                    <th>Canal</th>
                    <th>Comentario</th>
                    <th>Estado</th>
                    <th>Creada</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($solicitudes as $s): ?>
                <tr>
                    <td class="pa-doc">PV <?= htmlspecialchars($nrodocFmt($s['documento'])) ?></td>
                    <td><?= htmlspecialchars($s['nomcli'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($s['nombrecanal'] ?? $s['canal']) ?></td>
                    <td class="pa-coment"><?= nl2br(htmlspecialchars($s['comentario'])) ?></td>
                    <td>
                        <?php if ($s['estado'] === 'A'): ?>
                            <span class="pa-badge" style="background:#fee2e2;color:#b91c1c;">Activa</span>
                        <?php else: ?>
                            <span class="pa-badge" style="background:#e5e7eb;color:#374151;">Cerrada</span>
                        <?php endif; ?>
                    </td>
                    <td class="pa-meta">
                        <?= htmlspecialchars($s['fecha_fmt']) ?> <?= htmlspecialchars($s['horacrea']) ?><br>
                        <?= htmlspecialchars($s['usuariocrea']) ?>
                    </td>
                    <td>
                        <?php if ($s['estado'] === 'A'): ?>
                            <button type="button" class="pa-btn-cancel"
                                    onclick="paAbrirCancelar(<?= (int)$s['id'] ?>, '<?= htmlspecialchars($nrodocFmt($s['documento']), ENT_QUOTES) ?>')">
                                Cancelar
                            </button>
                        <?php else: ?>
                            <span style="font-size:.72rem;color:#aaa;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Modal cancelar -->
<div class="pa-modal-bg" id="pa-modal-cancelar">
    <div class="pa-modal">
        <h4>Cancelar alerta — PV <span id="pa-modal-doc"></span></h4>
        <p>Escriba el motivo de cierre. Se agregará al comentario de la solicitud.</p>
        <form method="POST" id="pa-form-cancelar" action="">
            <textarea name="motivo" required placeholder="Ej. Alistamiento completado / Emergencia resuelta..."></textarea>
            <div class="pa-modal-actions">
                <button type="button" class="pa-modal-cancel" onclick="paCerrarModal()">Volver</button>
                <button type="submit" class="pa-modal-confirm">Confirmar cierre</button>
            </div>
        </form>
    </div>
</div>

<script>
function paAbrirCancelar(id, doc) {
    document.getElementById('pa-modal-doc').textContent = doc;
    document.getElementById('pa-form-cancelar').action = '/prioridad-alistamiento/' + id + '/cancelar';
    document.getElementById('pa-modal-cancelar').classList.add('open');
}
function paCerrarModal() {
    document.getElementById('pa-modal-cancelar').classList.remove('open');
}
document.getElementById('pa-modal-cancelar').addEventListener('click', function (e) {
    if (e.target === this) paCerrarModal();
});
</script>
