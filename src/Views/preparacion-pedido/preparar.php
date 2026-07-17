<?php
// Soporte para pedido único o múltiples pedidos integrados
$esIntegrado   = !empty($pedidosIntegrados);   // bool: AP de varios PV
$nrodocUrl     = urlencode($pedido['nrodoc']);
$nrodocFmt     = str_pad($pedido['nrodoc'], 8, '0', STR_PAD_LEFT);
$totalItems    = count($items);

// Normaliza comencpo
function normalizarComencpo(string $raw): string
{
    $raw   = str_replace(['\r\n', '\r', '\n', '\\n'], "\n", $raw);
    $lines = array_filter(
        array_map(fn($l) => preg_replace('/[ \t]{2,}/', ' ', trim($l)), explode("\n", $raw)),
        fn($l) => $l !== ''
    );
    return implode("\n", $lines);
}

// Mostrar mensajes de sesión
if (isset($_SESSION['success'])) {
    echo "<script>document.addEventListener('DOMContentLoaded', () => mostrarAlertaAmable('Éxito', '" . addslashes($_SESSION['success']) . "', 'success'));</script>";
    unset($_SESSION['success']);
}
if (isset($_SESSION['errors'])) {
    foreach ($_SESSION['errors'] as $error) {
        echo "<script>document.addEventListener('DOMContentLoaded', () => mostrarAlertaAmable('Error', '" . addslashes($error) . "', 'error'));</script>";
    }
    unset($_SESSION['errors']);
}
?>

<style>
    .prep-wrap { max-width: 900px; margin: 0 auto; }

    /* ── Header ── */
    .prep-wrap .prep-header {
        background: #1a4dad;
        color: #fff;
        padding: .65rem 1.2rem;
        border-radius: .5rem .5rem 0 0;
        display: flex;
        align-items: center;
        gap: .7rem;
        font-weight: 700;
        font-size: .9rem;
        flex-wrap: wrap;
    }
    .prep-wrap .prep-header .btn-back {
        display: flex; align-items: center; gap: .25rem;
        color: #fff; text-decoration: none;
        background: rgba(255,255,255,.15);
        border: 1px solid rgba(255,255,255,.3);
        border-radius: .3rem; padding: .25rem .55rem;
        font-size: .75rem; flex-shrink: 0;
    }
    .prep-wrap .prep-header-info {
        display: flex; flex-wrap: wrap; gap: .15rem 1.2rem;
        font-size: .78rem; font-weight: 500; opacity: .92; margin-left: auto;
    }
    .badge-integrado {
        background: #15803d; color: #fff; border-radius: .25rem;
        padding: .15rem .5rem; font-size: .7rem; font-weight: 700;
    }

    /* ── Body ── */
    .prep-wrap .prep-body {
        background: #f0f2f8;
        border: 1px solid #b0b8d0; border-top: none;
        border-radius: 0 0 .5rem .5rem;
        padding: 1rem 1.2rem;
    }

    /* ── Separador de pedido origen (en modo integrado) ── */
    .origen-separator {
        background: #e8edf8;
        border: 1px solid #c0cce8;
        border-radius: .35rem;
        padding: .35rem .8rem;
        margin-bottom: .5rem;
        display: flex; align-items: center; gap: .5rem;
        font-size: .76rem; font-weight: 700; color: #1a4dad;
    }

    /* ── Item card ── */
    .prep-wrap .item-card {
        background: #fff;
        border: 1px solid #d0d8ec;
        border-radius: .45rem;
        padding: .8rem 1rem;
        margin-bottom: .75rem;
        box-shadow: 0 2px 6px rgba(0,0,0,.05);
    }
    .prep-wrap .item-card:last-child { margin-bottom: 0; }
    .prep-wrap .item-card-top {
        display: flex; align-items: flex-start; gap: .5rem; margin-bottom: .5rem;
        flex-wrap: wrap;
    }
    .prep-wrap .item-num {
        background: #1a4dad; color: #fff;
        min-width: 26px; height: 26px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: .72rem; font-weight: 700; flex-shrink: 0;
    }
    .prep-wrap .item-info { flex: 1; min-width: 0; }
    .prep-wrap .item-cod { font-size: .68rem; color: #888; font-weight: 600; letter-spacing: .04em; }
    .prep-wrap .item-desc { font-size: .92rem; font-weight: 700; color: #1a2e1a; line-height: 1.25; }
    .prep-wrap .item-fechas { display: inline-flex; gap: .75rem; margin-left: .6rem; font-size: .72rem; font-weight: 400; color: #4b5b4b; white-space: nowrap; vertical-align: baseline; }
    .prep-wrap .item-fechas .lbl { color: #8a948a; }
    .lote-fechas { display: flex; gap: .75rem; flex-wrap: wrap; font-size: .7rem; font-weight: 400; color: #4b5b4b; margin-top: .1rem; }
    .lote-fechas .lbl { color: #8a948a; }

    /* badge origen en el card */
    .item-origen-badge {
        font-size: .65rem; background: #dbeafe; color: #1d4ed8;
        border-radius: .2rem; padding: .1rem .4rem; font-weight: 700;
        white-space: nowrap;
    }

    .prep-wrap .item-obs {
        background: #fefce8; border: 1px solid #fde68a;
        border-radius: .3rem; padding: .4rem .65rem;
        font-size: .78rem; color: #555; line-height: 1.5;
        white-space: pre-wrap; margin-bottom: .5rem;
    }

    /* ── Sección de pesajes por lote ── */
    .lotes-section {
        margin-bottom: .6rem;
    }
    .lotes-title {
        font-size: .7rem; font-weight: 700; color: #6b7280;
        text-transform: uppercase; letter-spacing: .04em;
        margin-bottom: .35rem;
    }
    .lote-row {
        display: grid;
        grid-template-columns: auto 1fr auto auto;
        gap: .4rem .6rem;
        align-items: center;
        background: #f8f9ff;
        border: 1px solid #e2e8f0;
        border-radius: .35rem;
        padding: .45rem .7rem;
        margin-bottom: .3rem;
        font-size: .82rem;
    }
    .lote-num {
        background: #64748b; color: #fff; border-radius: 50%;
        width: 20px; height: 20px; display: flex; align-items: center;
        justify-content: center; font-size: .65rem; font-weight: 700; flex-shrink: 0;
    }
    .lote-info { min-width: 0; }
    .lote-lote-id { font-size: .68rem; color: #888; font-weight: 600; }
    .lote-peso-val { font-size: .9rem; font-weight: 700; color: #15803d; font-family: monospace; white-space: nowrap; }
    .lote-peso-label { font-size: .6rem; color: #888; }
    .peso-lote-input {
        width: 92px; text-align: right;
        font-size: .9rem; font-weight: 700; color: #15803d; font-family: monospace;
        border: 1.5px solid #86b98f; border-radius: .3rem;
        padding: .22rem .4rem; outline: none; background: #fff;
    }
    .peso-lote-input:focus { border-color: #15803d; box-shadow: 0 0 0 2px rgba(21,128,61,.15); }
    .prep-wrap .peso-input[readonly] {
        background: #f3f4f6; color: #374151; border-color: #d1d5db; cursor: default;
    }
    .btn-sticker-lote {
        display: inline-flex; align-items: center; gap: .25rem;
        background: #f59e0b; color: #fff; border: none;
        border-radius: .3rem; padding: .3rem .65rem;
        font-size: .72rem; font-weight: 600; cursor: pointer;
        transition: background .15s; white-space: nowrap;
    }
    .btn-sticker-lote:hover { background: #d97706; }

    /* ── Comparador ── */
    .prep-wrap .item-row {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr 1fr auto;
        gap: .6rem;
        align-items: flex-end;
        margin-top: .5rem;
    }
    .prep-wrap .cmp-box {
        background: #f5f7ff; border: 1px solid #dce4f8;
        border-radius: .35rem; padding: .45rem .6rem; text-align: center;
    }
    .prep-wrap .cmp-label {
        font-size: .62rem; color: #888; font-weight: 600;
        text-transform: uppercase; letter-spacing: .04em; margin-bottom: .1rem;
    }
    .prep-wrap .cmp-val { font-size: .92rem; font-weight: 700; color: #222; font-family: monospace; }
    .prep-wrap .cmp-val.green { color: #16a34a; }
    .prep-wrap .cmp-val.amber { color: #ca8a04; }
    .prep-wrap .cmp-val.red   { color: #dc2626; }

    .prep-wrap .peso-group label {
        display: block; font-size: .7rem; font-weight: 700; color: #1a4dad;
        text-transform: uppercase; letter-spacing: .03em; margin-bottom: .22rem;
    }
    .prep-wrap .peso-input {
        width: 100%; padding: .55rem .75rem;
        font-size: 1rem; font-weight: 700;
        border: 2px solid #1a4dad; border-radius: .4rem;
        color: #1a2e1a; background: #f0f4ff;
        box-sizing: border-box; text-align: right;
        transition: border-color .15s, box-shadow .15s;
    }
    .prep-wrap .peso-input:focus {
        outline: none; border-color: #163fa0;
        box-shadow: 0 0 0 3px rgba(26,77,173,.18); background: #fff;
    }
    .prep-wrap .btn-sticker {
        display: flex; align-items: center; gap: .35rem;
        background: #f59e0b; color: #fff; border: none;
        border-radius: .4rem; padding: .55rem .9rem;
        font-size: .8rem; font-weight: 600; cursor: pointer;
        height: fit-content;
        box-shadow: 0 2px 6px rgba(245,158,11,.3);
        transition: background .15s, transform .1s;
    }
    .prep-wrap .btn-sticker:hover { background: #d97706; transform: translateY(-1px); }

    /* ── Footer form ── */
    .prep-wrap .prep-footer {
        margin-top: 1rem; display: flex; align-items: center;
        justify-content: space-between; flex-wrap: wrap; gap: .5rem;
    }
    .prep-wrap .prep-footer-group { display: flex; gap: .5rem; align-items: center; }
    .prep-wrap .btn-generar {
        display: flex; align-items: center; gap: .5rem;
        background: #15803d; color: #fff; border: none;
        border-radius: .45rem; padding: .7rem 1.6rem;
        font-size: .95rem; font-weight: 700; cursor: pointer;
        box-shadow: 0 3px 10px rgba(21,128,61,.3);
        transition: background .15s, transform .1s;
    }
    .prep-wrap .btn-generar:hover { background: #166534; transform: translateY(-1px); }
    .prep-wrap .btn-cierra-planilla {
        display: flex; align-items: center; gap: .5rem;
        background: #7c3aed; color: #fff; border: none;
        border-radius: .45rem; padding: .7rem 1.2rem;
        font-size: .88rem; font-weight: 600; cursor: pointer;
        box-shadow: 0 3px 10px rgba(124,58,237,.3);
        transition: background .15s, transform .1s;
    }
    .prep-wrap .btn-cierra-planilla:hover { background: #6d28d9; transform: translateY(-1px); }
    .prep-wrap .btn-volver {
        display: flex; align-items: center; gap: .3rem;
        background: #e0e0e0; border: 1px solid #999;
        border-radius: .35rem; padding: .35rem .8rem;
        font-size: .78rem; font-weight: 600; color: #333;
        text-decoration: none; transition: background .15s;
    }
    .prep-wrap .btn-volver:hover { background: #d0d0d0; }

    @media (max-width: 1280px) {
        .prep-wrap { max-width: 100%; }
        .prep-wrap .prep-body { padding: .85rem; }
        .prep-wrap .item-card { padding: 1rem; }
        .prep-wrap .item-row {
            grid-template-columns: 1fr 1fr;
        }
        .prep-wrap .item-row .peso-group { grid-column: 1 / -1; }
        .prep-wrap .peso-input { min-height: 52px; font-size: 1.1rem; }
        .prep-wrap .btn-generar { min-height: 52px; font-size: 1rem; padding: .75rem 1.8rem; }
        .prep-wrap .btn-sticker { min-height: 52px; }
        .lote-row { grid-template-columns: auto 1fr auto auto; }
    }
    @media (max-width: 640px) {
        .prep-wrap .item-row { grid-template-columns: 1fr 1fr; }
        .prep-wrap .item-row .peso-group { grid-column: 1 / -1; }
        .prep-wrap .item-row .btn-sticker { grid-column: 1 / -1; }
        .prep-wrap .prep-header-info { margin-left: 0; }
        .lote-row { grid-template-columns: auto 1fr; }
        .lote-row .lote-peso-val,
        .lote-row .btn-sticker-lote { grid-column: 2; }
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
        <?php if ($esIntegrado): ?>
            <span class="badge-integrado">AP Integrada</span>
        <?php endif; ?>
        <div class="prep-header-info">
            <?php if ($esIntegrado): ?>
                <span><?= count($pedidosIntegrados) ?> pedidos integrados</span>
                <span>|</span>
            <?php else: ?>
                <span>Doc PV: <?= htmlspecialchars($nrodocFmt) ?></span>
                <span>|</span>
            <?php endif; ?>
            <span>Entrega: <?= htmlspecialchars($pedido['fecentrega_fmt']) ?></span>
            <span>|</span>
            <span>Canal: <?= htmlspecialchars($pedido['codtipocli']) ?></span>
            <span>|</span>
            <span><?= $totalItems ?> ítem<?= $totalItems !== 1 ? 's' : '' ?></span>
        </div>
    </div>

    <!-- Form -->
    <form method="POST"
          action="/preparacion-pedido/<?= $nrodocUrl ?>/preparar<?= $esIntegrado ? '?integrado=1&docs=' . urlencode(implode(',', array_column($pedidosIntegrados, 'nrodoc'))) . '&prefs=' . urlencode(implode(',', array_column($pedidosIntegrados, 'prefijo'))) : '' ?>"
          id="form-prep" onsubmit="return validarForm()">

        <input type="hidden" name="prefijo"  value="<?= htmlspecialchars($pedido['prefijo']) ?>">
        <input type="hidden" name="accion"   id="input-accion" value="">
        <?php if ($esIntegrado): ?>
            <input type="hidden" name="integrado" value="1">
            <?php foreach ($pedidosIntegrados as $pi): ?>
                <input type="hidden" name="docs_integrados[]"  value="<?= htmlspecialchars($pi['nrodoc']) ?>">
                <input type="hidden" name="prefs_integrados[]" value="<?= htmlspecialchars($pi['prefijo']) ?>">
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="prep-body">

            <?php
            $globalIdx = 0;
            // Agrupar ítems por pedido origen si es integrado
            $itemsPorPedido = [];
            foreach ($items as $it) {
                $origenKey = $it['nrodoc_origen'] ?? $pedido['nrodoc'];
                $itemsPorPedido[$origenKey][] = $it;
            }
            ?>

            <?php foreach ($itemsPorPedido as $docOrigen => $grupoItems):
                // Separador si es integrado y hay más de un pedido
                if ($esIntegrado):
                    $docOrigenFmt = str_pad($docOrigen, 8, '0', STR_PAD_LEFT);
                    // Buscar fecha del pedido origen
                    $fechaOrigen = '';
                    foreach ($pedidosIntegrados as $pi) {
                        if (trim($pi['nrodoc']) === trim($docOrigen)) {
                            $fechaOrigen = $pi['fecentrega_fmt'] ?? '';
                            break;
                        }
                    }
            ?>
                <div class="origen-separator">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Pedido PV <?= htmlspecialchars($docOrigenFmt) ?>
                    <?php if ($fechaOrigen): ?>
                        &nbsp;&mdash;&nbsp; Entrega: <?= htmlspecialchars($fechaOrigen) ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php foreach ($grupoItems as $it):
                $obs        = normalizarComencpo($it['comencpo'] ?? '');
                $solicitado = (float)$it['cantidad'];
                $alistado   = (float)$it['total_alistado'];
                $diff       = $solicitado - $alistado;
                $diffClass  = $diff > 0.001 ? 'amber' : ($diff < -0.001 ? 'red' : 'green');

                // Lotes de itemmov para este ítem
                $lotes = $it['lotes'] ?? [];

                // Calcular acumulado de lotes ya pesados
                $acumuladoLotes = array_sum(array_column($lotes, 'cantidad'));
                $pendienteLotes = $solicitado - $acumuladoLotes;

                // Peso propuesto para el campo consolidado
                $registro_key = trim($it['registro']);
                if (!empty($pesosAP) && isset($pesosAP[$registro_key])) {
                    $propuesta = number_format($pesosAP[$registro_key], 3, '.', '');
                } else {
                    $propuesta = number_format($alistado, 3, '.', '');
                }
            ?>
                <div class="item-card">
                    <input type="hidden" name="registros[]"     value="<?= htmlspecialchars($it['registro']) ?>">
                    <input type="hidden" name="nrodoc_origen[]" value="<?= htmlspecialchars($docOrigen) ?>">

                    <div class="item-card-top">
                        <div class="item-num"><?= $globalIdx + 1 ?></div>
                        <div class="item-info">
                            <div class="item-cod"><?= htmlspecialchars($it['codart']) ?></div>
                            <?php
                                // Fechas a nivel de ítem: solo cuando NO hay lotes
                                // (con lotes, cada lote muestra las suyas)
                                $fSac  = empty($lotes) ? ($it['fecha_sacrificio_fmt']  ?? null) : null;
                                $dVto  = empty($lotes) ? ($it['dias_vencimiento']      ?? null) : null;
                                $fVto  = empty($lotes) ? ($it['fecha_vencimiento_fmt'] ?? null) : null;
                            ?>
                            <div class="item-desc"><?= htmlspecialchars($it['descripcion']) ?><?php if ($fSac !== null || $dVto !== null): ?><span class="item-fechas"><?php if ($fSac !== null): ?><span><span class="lbl">Sacrificio:</span> <?= htmlspecialchars($fSac) ?></span><?php endif; ?><?php if ($dVto !== null): ?><span><span class="lbl">Vence:</span> <?= (int)$dVto ?> días &mdash; <?= htmlspecialchars($fVto) ?></span><?php endif; ?></span><?php endif; ?></div>
                        </div>
                        <?php if ($esIntegrado): ?>
                            <span class="item-origen-badge">PV <?= htmlspecialchars(str_pad($docOrigen, 8, '0', STR_PAD_LEFT)) ?></span>
                        <?php endif; ?>
                        <?php
                            $presInt   = (int)($it['presentacion_int'] ?? 1);
                            $presLabel = $it['presentacion_label'] ?? 'Refrigeración';
                            $diasVenc  = $it['dias_vencimiento'] ?? null;
                            $presColor = $presInt === 2
                                ? ['bg' => '#e0f2fe', 'text' => '#0369a1', 'border' => '#bae6fd']
                                : ['bg' => '#dcfce7', 'text' => '#15803d', 'border' => '#bbf7d0'];
                        ?>
                        <span style="font-size:.68rem;font-weight:700;padding:.2rem .5rem;border-radius:.25rem;
                                     background:<?= $presColor['bg'] ?>;color:<?= $presColor['text'] ?>;
                                     border:1px solid <?= $presColor['border'] ?>;white-space:nowrap;">
                            <?= $presInt === 2 ? '❄️' : '🌡️' ?> <?= htmlspecialchars($presLabel) ?>
                        </span>
                        <span style="font-size:.68rem;font-weight:700;padding:.2rem .5rem;border-radius:.25rem;
                                     background:<?= $diasVenc !== null ? '#fef9c3' : '#f3f4f6' ?>;
                                     color:<?= $diasVenc !== null ? '#854d0e' : '#6b7280' ?>;
                                     border:1px solid <?= $diasVenc !== null ? '#fde68a' : '#e5e7eb' ?>;
                                     white-space:nowrap;"
                              title="Días de vencimiento según inv_caducidad">
                            🗓️ <?= $diasVenc !== null ? $diasVenc . ' días' : 'N/A' ?>
                        </span>
                    </div>

                    <?php if ($obs !== ''): ?>
                        <div class="item-obs"><?= htmlspecialchars($obs) ?></div>
                    <?php endif; ?>

                    <!-- ── Pesajes por lote (itemmov) ── -->
                    <?php if (!empty($lotes)): ?>
                    <div class="lotes-section">
                        <div class="lotes-title">
                            Pesajes registrados &mdash; <?= count($lotes) ?> lote<?= count($lotes) !== 1 ? 's' : '' ?>
                            &nbsp;&bull;&nbsp; Acumulado: <strong><?= number_format($acumuladoLotes, 3) ?> kg</strong>
                            <?php if ($pendienteLotes > 0.001): ?>
                                &nbsp;/&nbsp; Pendiente: <span style="color:#ca8a04;font-weight:700;"><?= number_format($pendienteLotes, 3) ?> kg</span>
                            <?php elseif ($pendienteLotes < -0.001): ?>
                                &nbsp;/&nbsp; <span style="color:#dc2626;font-weight:700;">Excedente: <?= number_format(abs($pendienteLotes), 3) ?> kg</span>
                            <?php else: ?>
                                &nbsp;&mdash;&nbsp; <span style="color:#15803d;font-weight:700;">&#10003; Completo</span>
                            <?php endif; ?>
                        </div>

                        <?php foreach ($lotes as $li => $lote):
                            $lotePeso   = (float)$lote['cantidad'];
                            // Si la AP ya tiene peso validado para este lote (por posición), usarlo
                            if (isset($pesosAPLote[$registro_key][$li])) {
                                $lotePeso = (float)$pesosAPLote[$registro_key][$li];
                            }
                            $loteId     = trim($lote['lote'] ?? '');
                            $loteObs    = trim($lote['observacion'] ?? '');
                            $idxLote    = $globalIdx . '_' . $li;
                        ?>
                        <div class="lote-row">
                            <div class="lote-num"><?= $li + 1 ?></div>
                            <div class="lote-info">
                                <?php if ($loteId !== ''): ?>
                                    <div class="lote-lote-id">Lote: <?= htmlspecialchars($loteId) ?></div>
                                <?php endif; ?>
                                <?php
                                    $lSac  = $lote['fecha_sacrificio_fmt']  ?? null;
                                    $lDias = $lote['dias_vencimiento']      ?? null;
                                    $lFvto = $lote['fecha_vencimiento_fmt'] ?? null;
                                    $lPres = $lote['presentacion_label']    ?? '';
                                ?>
                                <?php if ($lSac !== null || $lDias !== null || $lPres !== ''): ?>
                                    <div class="lote-fechas">
                                        <?php if ($lSac !== null): ?>
                                            <span><span class="lbl">Sacrificio:</span> <?= htmlspecialchars($lSac) ?></span>
                                        <?php endif; ?>
                                        <?php if ($lPres !== ''): ?>
                                            <span><span class="lbl">Present.:</span> <?= htmlspecialchars($lPres) ?></span>
                                        <?php endif; ?>
                                        <?php if ($lDias !== null): ?>
                                            <span><span class="lbl">Vence:</span> <?= (int)$lDias ?> días &mdash; <?= htmlspecialchars($lFvto) ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($loteObs !== ''): ?>
                                    <div style="font-size:.7rem;color:#555;"><?= htmlspecialchars($loteObs) ?></div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="lote-peso-label">Peso (kg)</div>
                                <input type="text"
                                       inputmode="decimal"
                                       id="peso_lote_<?= $idxLote ?>"
                                       name="pesos_lote[<?= $globalIdx ?>][<?= $li ?>]"
                                       class="peso-lote-input"
                                       data-item="<?= $globalIdx ?>"
                                       value="<?= number_format($lotePeso, 3, '.', '') ?>"
                                       autocomplete="off"
                                       required>
                            </div>
                            <button type="button"
                                    class="btn-sticker-lote"
                                    onclick="imprimirStickerLote(event,
                                        '<?= htmlspecialchars(addslashes($it['codart'])) ?>',
                                        '<?= htmlspecialchars(addslashes($it['descripcion'])) ?>',
                                        document.getElementById('peso_lote_<?= $idxLote ?>').value,
                                        '<?= htmlspecialchars(addslashes($loteId)) ?>',
                                        '<?= htmlspecialchars(addslashes($docOrigen)) ?>',
                                        <?= (int)($lote['dias_vencimiento'] ?? 0) ?>,
                                        '<?= htmlspecialchars(addslashes($lote['presentacion_label'] ?? '')) ?>')">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4H9a2 2 0 00-2 2v2a2 2 0 002 2h6a2 2 0 002-2v-2a2 2 0 00-2-2zm0 0h6"/>
                                </svg>
                                Sticker lote
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- ── Comparador + campo peso consolidado ── -->
                    <div class="item-row">
                        <div class="cmp-box">
                            <div class="cmp-label">Solicitado</div>
                            <div class="cmp-val"><?= number_format($solicitado, 3) ?></div>
                        </div>
                        <div class="cmp-box">
                            <div class="cmp-label">Alistado</div>
                            <div class="cmp-val <?= $diffClass ?>"><?= number_format($alistado, 3) ?></div>
                        </div>
                        <div class="cmp-box">
                            <div class="cmp-label">Acum. lotes</div>
                            <div class="cmp-val <?= $acumuladoLotes >= $solicitado - 0.001 ? 'green' : 'amber' ?>"><?= number_format($acumuladoLotes, 3) ?></div>
                        </div>
                        <div class="peso-group">
                            <label for="peso_<?= $globalIdx ?>"><?= !empty($lotes) ? 'Total lotes (kg)' : 'Peso definitivo (kg)' ?></label>
                            <input type="text"
                                   inputmode="decimal"
                                   id="peso_<?= $globalIdx ?>"
                                   name="pesos[]"
                                   class="peso-input"
                                   data-solicitado="<?= number_format($solicitado, 3, '.', '') ?>"
                                   value="<?= !empty($lotes) ? number_format($acumuladoLotes, 3, '.', '') : $propuesta ?>"
                                   autocomplete="off"
                                   <?= !empty($lotes) ? 'readonly title="Total automático: se calcula sumando el peso de cada lote"' : 'required' ?>>
                        </div>
                        <button type="button"
                                class="btn-sticker"
                                onclick="imprimirSticker(event,
                                    '<?= htmlspecialchars(addslashes($it['codart'])) ?>',
                                    '<?= htmlspecialchars(addslashes($it['descripcion'])) ?>',
                                    '<?= $globalIdx ?>',
                                    '<?= htmlspecialchars(addslashes($docOrigen)) ?>',
                                    <?= (int)($diasVenc ?? 0) ?>,
                                    '<?= htmlspecialchars(addslashes($it['presentacion_label'] ?? '')) ?>',
                                    '<?= htmlspecialchars(addslashes($it['lote'] ?? '')) ?>')">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4H9a2 2 0 00-2 2v2a2 2 0 002 2h6a2 2 0 002-2v-2a2 2 0 00-2-2zm0 0h6a2 2 0 002-2v-4a2 2 0 00-2-2h-.5"/>
                            </svg>
                            Sticker
                        </button>
                    </div>
                </div>
            <?php $globalIdx++; endforeach; ?>
            <?php endforeach; ?>

        </div><!-- /prep-body -->

        <div class="prep-footer">
            <a href="/preparacion-pedido" class="btn-volver">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                Cancelar
            </a>
            <div class="prep-footer-group">
                <button type="submit" form="form-prep" class="btn-generar">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Confirmar y generar AP
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    /**
     * Imprime sticker del campo consolidado (peso definitivo del item)
     */
    function imprimirSticker(event, codart, descripcion, idx, docOrigen, diasVenc, presentacion, lote) {
        event.preventDefault();
        const pesoInput = document.getElementById('peso_' + idx);
        if (!pesoInput) {
            mostrarAlertaAmable('Error', 'No se encontró el campo de peso.', 'error');
            return;
        }
        const peso    = pesoInput.value.trim();
        const pesoNum = parseFloat(peso);

        if (peso === '' || isNaN(pesoNum)) {
            mostrarAlertaAmable('Campo incompleto', 'Ingrese un peso válido antes de imprimir el sticker.', 'warning');
            pesoInput.focus(); return;
        }
        if (pesoNum <= 0) {
            mostrarAlertaAmable('Peso inválido', 'El peso debe ser mayor a 0 kg.', 'warning');
            pesoInput.focus(); return;
        }
        if (pesoNum > 9999) {
            mostrarAlertaAmable('Peso inválido', 'Verifique el valor ingresado (máx 9999 kg).', 'warning');
            pesoInput.focus(); return;
        }
        const url = new URL('/sticker/generar', window.location.origin);
        url.searchParams.append('codart', codart);
        url.searchParams.append('desc', descripcion);
        url.searchParams.append('peso', peso);
        if (docOrigen)    url.searchParams.append('docpv',        docOrigen);
        if (lote)         url.searchParams.append('lote',         lote);
        if (diasVenc > 0) url.searchParams.append('dias_venc',    diasVenc);
        if (presentacion) url.searchParams.append('presentacion', presentacion);
        const w = window.open(url.toString(), '_blank', 'width=400,height=680,menubar=no,toolbar=no');
        if (w) w.focus();
    }

    /**
     * Imprime sticker de un lote específico de itemmov
     */
    function imprimirStickerLote(event, codart, descripcion, peso, loteId, docOrigen, diasVenc, presentacion) {
        event.preventDefault();
        const pesoNum = parseFloat(peso);
        if (isNaN(pesoNum) || pesoNum <= 0) {
            mostrarAlertaAmable('Peso inválido', 'El lote no tiene un peso válido registrado.', 'warning');
            return;
        }
        const url = new URL('/sticker/generar', window.location.origin);
        url.searchParams.append('codart', codart);
        url.searchParams.append('desc', descripcion);
        url.searchParams.append('peso', peso);
        if (loteId)       url.searchParams.append('lote',         loteId);
        if (docOrigen)    url.searchParams.append('docpv',        docOrigen);
        if (diasVenc > 0) url.searchParams.append('dias_venc',    diasVenc);
        if (presentacion) url.searchParams.append('presentacion', presentacion);
        const w = window.open(url.toString(), '_blank', 'width=400,height=680,menubar=no,toolbar=no');
        if (w) w.focus();
    }

    function validarForm() {
        // Pesos por lote: todos válidos y mayores a 0
        const lotesInp = document.querySelectorAll('.peso-lote-input');
        for (const inp of lotesInp) {
            const v = parseFloat(inp.value);
            if (inp.value === '' || isNaN(v) || v <= 0) {
                inp.focus(); inp.style.borderColor = '#dc2626';
                alert('Ingrese un peso válido (mayor a 0) para todos los lotes.');
                return false;
            }
            inp.style.borderColor = '';
        }
        const inputs = document.querySelectorAll('.peso-input');
        for (const inp of inputs) {
            const v = parseFloat(inp.value);
            if (inp.value === '' || isNaN(v) || v < 0) {
                inp.focus(); inp.style.borderColor = '#dc2626';
                alert('Ingrese el peso definitivo para todos los ítems.');
                return false;
            }
            inp.style.borderColor = '';
        }
        return confirm(
            'Va a generar el documento AP.\n¿Confirma los pesos ingresados?'
        );
    }

    function mostrarModalCierre() {
        const modal = document.createElement('div');
        modal.id = 'modal-cierre-planilla';
        modal.innerHTML = `
            <div style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.6);
                        display:flex;align-items:center;justify-content:center;z-index:10000;
                        font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;"
                 onclick="if(event.target===this)cerrarModalCierre()">
                <div style="background:#fff;border-radius:12px;padding:28px 32px;max-width:420px;
                            width:90%;box-shadow:0 20px 60px rgba(0,0,0,.3);text-align:center;">
                    <div style="width:64px;height:64px;background:#fef3c7;border-radius:50%;
                                display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 style="margin:0 0 12px;font-size:1.35rem;color:#1f2937;">¿Cerrar planilla?</h3>
                    <p style="margin:0 0 24px;color:#4b5563;line-height:1.5;">
                        Se cambiará el estado del/los PV a <strong>RM = R</strong> (completado)<br>
                        y el estado de la AP a <strong>C</strong> (cerrada).
                    </p>
                    <div style="display:flex;gap:12px;justify-content:center;">
                        <button type="button" onclick="cerrarModalCierre()"
                            style="flex:1;padding:12px 20px;background:#e5e7eb;border:none;
                                   border-radius:8px;font-size:.95rem;font-weight:600;color:#374151;cursor:pointer;">
                            Solo guardar
                        </button>
                        <button type="button" onclick="confirmarCierre()"
                            style="flex:1;padding:12px 20px;background:#7c3aed;border:none;
                                   border-radius:8px;font-size:.95rem;font-weight:600;color:#fff;cursor:pointer;">
                            Sí, cerrar
                        </button>
                    </div>
                </div>
            </div>`;
        document.body.appendChild(modal);
    }

    function cerrarModalCierre() {
        const modal = document.getElementById('modal-cierre-planilla');
        if (modal) modal.remove();
        window.location.href = '/preparacion-pedido';
    }

    function confirmarCierre() {
        document.getElementById('input-accion').value = 'cerrar';
        document.getElementById('form-prep').submit();
    }

    function mostrarAlertaAmable(titulo, mensaje, tipo = 'info') {
        let container = document.getElementById('alertas-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'alertas-container';
            container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;max-width:400px;';
            document.body.appendChild(container);
        }
        const colores = {
            success: { bg:'#d4edda', border:'#c3e6cb', text:'#155724' },
            warning: { bg:'#fff3cd', border:'#ffeaa7', text:'#856404' },
            error:   { bg:'#f8d7da', border:'#f5c6cb', text:'#721c24' },
            info:    { bg:'#d1ecf1', border:'#bee5eb', text:'#0c5460' }
        };
        const c = colores[tipo] || colores.info;
        const div = document.createElement('div');
        div.innerHTML = `<div style="background:${c.bg};border:1px solid ${c.border};border-radius:4px;
            padding:15px;margin-bottom:10px;color:${c.text};">
            <strong style="display:block;margin-bottom:5px;">${titulo}</strong>
            <div>${mensaje}</div></div>`;
        container.appendChild(div);
        setTimeout(() => { div.style.opacity='0'; div.style.transition='opacity .3s';
            setTimeout(() => div.remove(), 300); }, 5000);
    }

    document.addEventListener('DOMContentLoaded', () => {

        // ── Inicializar campos de peso ──────────────────────────────
        // Recalcula el total del ítem sumando sus lotes
        function recalcularTotalItem(itemIdx) {
            const lotes = document.querySelectorAll('.peso-lote-input[data-item="' + itemIdx + '"]');
            if (!lotes.length) return;
            let suma = 0;
            lotes.forEach(l => { const n = parseFloat(l.value); if (!isNaN(n)) suma += n; });
            const total = document.getElementById('peso_' + itemIdx);
            if (total) total.value = suma.toFixed(3);
        }

        // Campos de peso por lote
        document.querySelectorAll('.peso-lote-input').forEach(inp => {
            inp.addEventListener('focus', function () {
                setTimeout(() => this.select(), 10);
            });
            inp.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    e.stopPropagation();
                    const all = [...document.querySelectorAll('.peso-lote-input, .peso-input:not([readonly])')];
                    const i   = all.indexOf(this);
                    if (i >= 0 && i < all.length - 1) all[i + 1].focus();
                }
            });
            inp.addEventListener('input', function () {
                let v = this.value.replace(/[^0-9.]/g, '');
                const partes = v.split('.');
                if (partes.length > 2) v = partes[0] + '.' + partes.slice(1).join('');
                this.value = v;
                recalcularTotalItem(this.dataset.item);
            });
            inp.addEventListener('blur', function () {
                let v = this.value.trim();
                if (v === '' || v === '.') { this.value = '0.000'; }
                else {
                    if (v.startsWith('.')) v = '0' + v;
                    const n = parseFloat(v);
                    this.value = isNaN(n) ? '0.000' : n.toFixed(3);
                }
                recalcularTotalItem(this.dataset.item);
            });
        });

        document.querySelectorAll('.peso-input').forEach(inp => {

            // Foco: seleccionar todo el contenido
            inp.addEventListener('focus', function () {
                if (this.readOnly) return;
                setTimeout(() => this.select(), 10);
            });

            // Doble click: cargar el valor solicitado
            inp.addEventListener('dblclick', function () {
                if (this.readOnly) return;
                this.value = this.dataset.solicitado || '0.000';
                setTimeout(() => this.select(), 10);
            });

            // Enter: no enviar el formulario; avanzar al siguiente campo
            inp.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    e.stopPropagation();
                    const all = [...document.querySelectorAll('.peso-lote-input, .peso-input:not([readonly])')];
                    const i   = all.indexOf(this);
                    if (i >= 0 && i < all.length - 1) {
                        all[i + 1].focus();
                    }
                }
            });

            // Input: filtrar solo dígitos y un punto decimal
            inp.addEventListener('input', function () {
                if (this.readOnly) return;
                let v = this.value.replace(/[^0-9.]/g, '');
                // Permitir solo un punto decimal
                const partes = v.split('.');
                if (partes.length > 2) v = partes[0] + '.' + partes.slice(1).join('');
                this.value = v;
            });

            // Blur: normalizar formato (0.xxx)
            inp.addEventListener('blur', function () {
                if (this.readOnly) return;
                let v = this.value.trim();
                if (v === '' || v === '.') { this.value = '0.000'; return; }
                if (v.startsWith('.')) v = '0' + v;  // .78 → 0.78
                const n = parseFloat(v);
                this.value = isNaN(n) ? '0.000' : n.toFixed(3);
            });
        });

        const first = document.querySelector('.peso-lote-input, .peso-input:not([readonly])');
        if (first) first.focus();

        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('modalCierre') === '1') {
            window.history.replaceState({}, document.title, window.location.pathname);
            setTimeout(() => mostrarModalCierre(), 300);
        }
    });
</script>