<?php
function cadUrlEdit(array $r): string {
    $codc = (isset($r['codc']) && trim($r['codc']) !== '') ? urlencode(trim($r['codc'])) : '_';
    return urlencode(trim($r['canal'])) . '/' . urlencode(trim($r['codgrupo'])) . '/' . urlencode(trim($r['codsubg']))
         . '/' . $codc . '/' . urlencode(trim($r['presentacion']));
}
?>
<?php if (!empty($_SESSION['errors'])): ?>
    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
        <?php foreach ($_SESSION['errors'] as $e): ?>
            <p>⚠ <?= htmlspecialchars($e) ?></p>
        <?php endforeach; ?>
        <?php unset($_SESSION['errors']); ?>
    </div>
<?php endif; ?>

<div class="max-w-lg mx-auto mt-6">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-800">Editar caducidad</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                Solo se pueden modificar los días de caducidad.
            </p>
        </div>
        <a href="/inv-caducidad"
           class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver
        </a>
    </div>

    <form method="POST"
          action="/inv-caducidad/<?= cadUrlEdit($registro) ?>/update"
          class="bg-white rounded-2xl shadow-sm border border-gray-100 divide-y divide-gray-50">

        <!-- Canal (solo lectura) -->
        <div class="px-6 py-4">
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Canal</label>
            <input type="text" readonly value="<?= htmlspecialchars(trim($registro['canal'])) ?>"
                   class="w-full border border-gray-100 bg-gray-50 rounded-lg px-3 py-2 text-sm text-gray-500 cursor-not-allowed">
        </div>

        <!-- Grupo (solo lectura) -->
        <div class="px-6 py-4">
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Grupo</label>
            <input type="text" readonly value="<?= htmlspecialchars(trim($registro['codgrupo'])) ?>"
                   class="w-full border border-gray-100 bg-gray-50 rounded-lg px-3 py-2 text-sm text-gray-500 cursor-not-allowed">
        </div>

        <!-- Subgrupo (solo lectura) -->
        <div class="px-6 py-4">
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Subgrupo</label>
            <input type="text" readonly value="<?= htmlspecialchars(trim($registro['codsubg'])) ?>"
                   class="w-full border border-gray-100 bg-gray-50 rounded-lg px-3 py-2 text-sm text-gray-500 cursor-not-allowed">
        </div>

        <!-- Cliente (solo lectura) -->
        <div class="px-6 py-4">
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Cliente</label>
            <?php $codcTrim = trim($registro['codc'] ?? ''); ?>
            <input type="text" readonly
                   value="<?= $codcTrim !== '' ? htmlspecialchars($codcTrim . (!empty($registro['cliente_desc']) ? ' — ' . $registro['cliente_desc'] : '')) : 'Genérico (todos los clientes)' ?>"
                   class="w-full border border-gray-100 bg-gray-50 rounded-lg px-3 py-2 text-sm text-gray-500 cursor-not-allowed">
        </div>

        <!-- Presentación (solo lectura) -->
        <div class="px-6 py-4">
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Presentación</label>
            <input type="text" readonly value="<?= htmlspecialchars($registro['presentacion'] ?? '') ?>"
                   class="w-full border border-gray-100 bg-gray-50 rounded-lg px-3 py-2 text-sm text-gray-500 cursor-not-allowed">
        </div>

        <!-- Días (editable) -->
        <div class="px-6 py-4">
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Días de caducidad</label>
            <input type="number" name="cantidad" required min="0" max="99999"
                   value="<?= (int)($registro['cantidad'] ?? 0) ?>"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm
                          focus:outline-none focus:ring-2 focus:ring-blue-400 transition">
        </div>

        <!-- Botones -->
        <div class="px-6 py-4 flex justify-end gap-3 bg-gray-50 rounded-b-2xl">
            <a href="/inv-caducidad"
               class="px-5 py-2 text-sm text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 transition">
                Cancelar
            </a>
            <button type="submit"
                    class="px-6 py-2 text-sm font-semibold text-white bg-blue-600
                           rounded-lg hover:bg-blue-700 transition shadow-sm">
                Guardar cambios
            </button>
        </div>

    </form>
</div>