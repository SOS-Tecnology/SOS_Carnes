<?php
$nrodocFmt = fn(string $n): string => str_pad($n, 8, '0', STR_PAD_LEFT);

$etapas = [
    'en_alistamiento' => ['label' => 'En alistamiento',     'bg' => '#fef9c3', 'fg' => '#854d0e'],
    'listo_preparar'  => ['label' => 'Listo para preparar', 'bg' => '#dbeafe', 'fg' => '#1d4ed8'],
    'en_preparacion'  => ['label' => 'En preparación',      'bg' => '#fef3c7', 'fg' => '#b45309'],
    'preparado'       => ['label' => 'Preparado',           'bg' => '#dcfce7', 'fg' => '#15803d'],
    'facturado'       => ['label' => 'Facturado',           'bg' => '#e0e7ff', 'fg' => '#3730a3'],
    'otro'            => ['label' => 'Otro',                'bg' => '#e5e7eb', 'fg' => '#374151'],
];
$et = $etapas[$etapa] ?? $etapas['otro'];

$facturado       = ($etapa === 'facturado');
$puedeAbrirPrep  = ($etapa === 'preparado');
$puedeAbrirAlist = in_array($etapa, ['listo_preparar', 'en_preparacion'], true);

$estadoApLabel = function (string $e): array {
    return match ($e) {
        ''  => ['En proceso', '#fef3c7', '#b45309'],
        'C' => ['Cerrada',    '#dcfce7', '#15803d'],
        'O' => ['Facturada',  '#e0e7ff', '#3730a3'],
        default => ["Estado '{$e}'", '#e5e7eb', '#374151'],
    };
};
?>

<style>
    .em-alert { border-radius:.5rem; padding:.6rem 1rem; margin-bottom:.8rem; font-size:.85rem; font-weight:600; }
    .em-alert-ok  { background:#dcfce7; color:#15803d; border:1px solid #86efac; }
    .em-alert-err { background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5; }

    .em-header {
        background:#1a4dad; color:#fff; padding:.6rem 1.2rem;
        border-radius:.5rem .5rem 0 0; display:flex; align-items:center;
        gap:.9rem; font-weight:600; font-size:.95rem;
    }
    .em-volver {
        background:#3b62b8; color:#fff; border:1px solid #5d7fc9;
        border-radius:.35rem; padding:.25rem .7rem; font-size:.76rem;
        font-weight:700; text-decoration:none; white-space:nowrap;
    }
    .em-volver:hover { background:#2d539f; }
    .em-body {
        background:#e8eaf0; border:1px solid #b0b8d0; border-top:none;
        border-radius:0 0 .5rem .5rem; padding:1rem;
    }
    .em-card {
        background:#fff; border:1px solid #d0d8ec; border-radius:.5rem;
        box-shadow:0 2px 8px rgba(0,0,0,.06); padding:1rem 1.2rem;
        margin-bottom:.9rem;
    }
    .em-card h3 {
        font-size:.78rem; font-weight:700; color:#1a4dad;
        text-transform:uppercase; letter-spacing:.02em; margin:0 0 .7rem 0;
    }
    .em-grid {
        display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr));
        gap:.7rem;
    }
    .em-dato .lbl { font-size:.66rem; color:#888; text-transform:uppercase; letter-spacing:.02em; }
    .em-dato .val { font-size:.9rem; font-weight:700; color:#1a2e6a; }
    .em-dato .val.mono { font-family:monospace; }
    .em-badge {
        display:inline-block; padding:.2rem .65rem; border-radius:999px;
        font-size:.74rem; font-weight:700;
    }
    .em-estados { font-size:.72rem; color:#666; margin-top:.35rem; font-family:monospace; }

    .em-tabla { width:100%; border-collapse:collapse; font-size:.8rem; }
    .em-tabla th {
        text-align:left; font-size:.68rem; color:#1a4dad; text-transform:uppercase;
        padding:.35rem .6rem; border-bottom:1px solid #d0d8ec;
    }
    .em-tabla td { padding:.4rem .6rem; border-bottom:1px solid #eef1f8; }

    .em-acciones { display:flex; gap:.9rem; flex-wrap:wrap; }
    .em-accion {
        flex:1; min-width:260px; background:#f7f9fe; border:1px solid #d0d8ec;
        border-radius:.5rem; padding:.9rem 1rem;
    }
    .em-accion h4 { margin:0 0 .3rem 0; font-size:.85rem; color:#1a2e6a; }
    .em-accion p  { margin:0 0 .7rem 0; font-size:.74rem; color:#555; line-height:1.35; }
    .em-btn {
        border:none; border-radius:.4rem; padding:.5rem 1rem;
        font-size:.8rem; font-weight:700; cursor:pointer; color:#fff;
    }
    .em-btn-prep  { background:#b45309; }
    .em-btn-prep:hover  { background:#92400e; }
    .em-btn-alist { background:#1d4ed8; }
    .em-btn-alist:hover { background:#1a3fae; }
    .em-btn:disabled { background:#c3c9d6; cursor:not-allowed; }
    .em-hint { font-size:.7rem; color:#b45309; font-weight:600; margin-top:.4rem; }
    .em-fact {
        background:#e0e7ff; border:1px solid #a5b4fc; color:#3730a3;
        border-radius:.5rem; padding:.8rem 1rem; font-size:.85rem; font-weight:700;
    }
</style>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="em-alert em-alert-ok">✔ <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
<?php endif; ?>
<?php if (!empty($_SESSION['errors'])): ?>
    <div class="em-alert em-alert-err">
        <?php foreach ($_SESSION['errors'] as $e): ?>
            <div>✖ <?= htmlspecialchars($e) ?></div>
        <?php endforeach; unset($_SESSION['errors']); ?>
    </div>
<?php endif; ?>

<div class="em-header">
    <a href="/estado-pedidos" class="em-volver">&lt; Volver a la lista</a>
    <span>Modificar Estado — Pedido PV <?= htmlspecialchars($nrodocFmt($pedido['nrodoc'])) ?></span>
</div>

<div class="em-body">

    <!-- Información del pedido -->
    <div class="em-card">
        <h3>Información del pedido</h3>
        <div class="em-grid">
            <div class="em-dato">
                <div class="lbl">Documento</div>
                <div class="val mono"><?= htmlspecialchars($nrodocFmt($pedido['nrodoc'])) ?></div>
            </div>
            <div class="em-dato">
                <div class="lbl">C&oacute;d. Cliente</div>
                <div class="val mono"><?= htmlspecialchars($pedido['codcli']) ?></div>
            </div>
            <div class="em-dato">
                <div class="lbl">Cliente</div>
                <div class="val"><?= htmlspecialchars($pedido['nomcli']) ?></div>
            </div>
            <div class="em-dato">
                <div class="lbl">Fecha</div>
                <div class="val"><?= htmlspecialchars($pedido['fecha_fmt']) ?></div>
            </div>
            <div class="em-dato">
                <div class="lbl">F. Entrega</div>
                <div class="val"><?= htmlspecialchars($pedido['fecentrega_fmt']) ?></div>
            </div>
            <div class="em-dato">
                <div class="lbl">Etapa actual</div>
                <div>
                    <span class="em-badge" style="background:<?= $et['bg'] ?>;color:<?= $et['fg'] ?>;">
                        <?= htmlspecialchars($et['label']) ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="em-estados">
            estado='<?= htmlspecialchars($pedido['estado']) ?>' &nbsp;|&nbsp; estadorm='<?= htmlspecialchars($pedido['estadorm']) ?>'
        </div>
    </div>

    <!-- APs asociadas -->
    <div class="em-card">
        <h3>Documentos AP asociados</h3>
        <?php if (empty($aps)): ?>
            <div style="font-size:.8rem;color:#888;">El pedido no tiene documentos AP generados.</div>
        <?php else: ?>
            <table class="em-tabla">
                <thead>
                    <tr><th>Documento AP</th><th>Fecha</th><th>Estado</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($aps as $ap):
                        [$lbl, $bg, $fg] = $estadoApLabel($ap['estado_ap']);
                    ?>
                    <tr>
                        <td class="mono" style="font-family:monospace;font-weight:700;">
                            <?= htmlspecialchars($nrodocFmt($ap['doc_ap'])) ?>
                        </td>
                        <td><?= htmlspecialchars($ap['fecha_fmt']) ?></td>
                        <td>
                            <span class="em-badge" style="background:<?= $bg ?>;color:<?= $fg ?>;">
                                <?= htmlspecialchars($lbl) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Acciones -->
    <?php if ($facturado): ?>
        <div class="em-fact">
            🔒 Pedido facturado — no permite modificaciones.
        </div>
    <?php else: ?>
        <div class="em-card">
            <h3>Acciones de reapertura</h3>
            <div class="em-acciones">

                <div class="em-accion">
                    <h4>Abrir preparación</h4>
                    <p>
                        El PV vuelve a estadorm='A' y la AP cerrada queda en blanco
                        ("En proceso"). El pedido reaparece en Preparación de Pedido.
                    </p>
                    <form method="POST"
                          action="/estado-pedidos/<?= urlencode($pedido['nrodoc']) ?>/abrir-preparacion"
                          onsubmit="return confirm('¿Abrir la preparación del PV <?= htmlspecialchars($nrodocFmt($pedido['nrodoc'])) ?>?\n\nPV: estadorm R → A\nAP: estado C → (en blanco)');">
                        <input type="hidden" name="prefijo" value="<?= htmlspecialchars($pedido['prefijo']) ?>">
                        <button type="submit" class="em-btn em-btn-prep" <?= $puedeAbrirPrep ? '' : 'disabled' ?>>
                            Abrir preparación
                        </button>
                    </form>
                    <?php if (!$puedeAbrirPrep): ?>
                        <div class="em-hint">Disponible solo cuando el pedido está en etapa Preparado.</div>
                    <?php endif; ?>
                </div>

                <div class="em-accion">
                    <h4>Abrir alistamiento</h4>
                    <p>
                        El PV vuelve a estado='C' y estadorm en blanco. El pedido
                        reaparece en la Planilla de Pedidos.
                    </p>
                    <form method="POST"
                          action="/estado-pedidos/<?= urlencode($pedido['nrodoc']) ?>/abrir-alistamiento"
                          onsubmit="return confirm('¿Abrir el alistamiento del PV <?= htmlspecialchars($nrodocFmt($pedido['nrodoc'])) ?>?\n\nPV: estado O → C, estadorm A → (en blanco)');">
                        <input type="hidden" name="prefijo" value="<?= htmlspecialchars($pedido['prefijo']) ?>">
                        <button type="submit" class="em-btn em-btn-alist" <?= $puedeAbrirAlist ? '' : 'disabled' ?>>
                            Abrir alistamiento
                        </button>
                    </form>
                    <?php if ($etapa === 'preparado'): ?>
                        <div class="em-hint">Primero abra la preparación y luego el alistamiento.</div>
                    <?php elseif (!$puedeAbrirAlist): ?>
                        <div class="em-hint">Disponible solo cuando el pedido está alistado (Listo para preparar o En preparación).</div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    <?php endif; ?>

</div>
