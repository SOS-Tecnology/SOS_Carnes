<div class="max-w-lg mx-auto mt-6">

    <!-- ENCABEZADO -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-800">Detalle de caducidad</h1>
            <p class="text-sm text-gray-500 mt-0.5">Información completa del registro.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="/inv-caducidad/<?= urlencode($registro['canal']) ?>/<?= urlencode($registro['codgrupo']) ?>/<?= urlencode($registro['codsubg']) ?>/edit"
               class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700
                      text-white text-sm font-semibold rounded-lg shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5
                           m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Editar
            </a>
            <a href="/inv-caducidad"
               class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Volver
            </a>
        </div>
    </div>

    <!-- TARJETA DETALLE -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 divide-y divide-gray-50">

        <!-- Canal -->
        <div class="px-6 py-5 flex items-start justify-between">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Canal</span>
            <div class="text-right">
                <span class="font-semibold text-indigo-700 text-sm">
                    <?= htmlspecialchars($registro['canal']) ?>
                </span>
                <?php if (!empty($registro['canal_desc'])): ?>
                    <p class="text-xs text-gray-500 mt-0.5"><?= htmlspecialchars($registro['canal_desc']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Grupo -->
        <div class="px-6 py-5 flex items-start justify-between">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Grupo</span>
            <div class="text-right">
                <span class="font-mono text-xs bg-gray-100 px-2 py-0.5 rounded text-gray-700">
                    <?= htmlspecialchars($registro['codgrupo']) ?>
                </span>
                <?php if (!empty($registro['grupo_desc'])): ?>
                    <p class="text-xs text-gray-500 mt-0.5"><?= htmlspecialchars($registro['grupo_desc']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Subgrupo -->
        <div class="px-6 py-5 flex items-start justify-between">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Subgrupo</span>
            <div class="text-right">
                <span class="font-mono text-xs bg-gray-100 px-2 py-0.5 rounded text-gray-700">
                    <?= htmlspecialchars($registro['codsubg']) ?>
                </span>
                <?php if (!empty($registro['subgrupo_desc'])): ?>
                    <p class="text-xs text-gray-500 mt-0.5"><?= htmlspecialchars($registro['subgrupo_desc']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Presentación -->
        <div class="px-6 py-5 flex items-center justify-between">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Presentación</span>
            <?php
            $badgeClass = ($registro['presentacion'] ?? '') === 'Refrigeración'
                ? 'bg-blue-100 text-blue-700'
                : 'bg-cyan-100 text-cyan-700';
            ?>
            <span class="<?= $badgeClass ?> text-xs font-semibold px-3 py-1 rounded-full">
                <?= htmlspecialchars($registro['presentacion'] ?? '—') ?>
            </span>
        </div>

        <!-- Cantidad -->
        <div class="px-6 py-5 flex items-center justify-between">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Días de caducidad</span>
            <span class="text-2xl font-bold text-gray-800">
                <?= (int)($registro['cantidad'] ?? 0) ?>
                <span class="text-sm font-normal text-gray-500 ml-1">días</span>
            </span>
        </div>

    </div>

    <!-- Eliminar -->
    <div class="mt-6 flex justify-end">
        <form method="POST"
              action="/inv-caducidad/<?= urlencode($registro['canal']) ?>/<?= urlencode($registro['codgrupo']) ?>/<?= urlencode($registro['codsubg']) ?>/delete"
              onsubmit="return confirm('¿Confirma la eliminación de este registro?')">
            <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm text-red-600 border border-red-200
                           rounded-lg hover:bg-red-50 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0
                           01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0
                           00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Eliminar registro
            </button>
        </form>
    </div>

</div>
