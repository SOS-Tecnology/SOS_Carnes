<?php if (!empty($_SESSION['success'])): ?>
    <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-4 py-3 text-sm">
        ✔ <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>

<?php if (!empty($_SESSION['errors'])): ?>
    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
        <?php foreach ($_SESSION['errors'] as $e): ?>
            <p>⚠ <?= htmlspecialchars($e) ?></p>
        <?php endforeach; ?>
        <?php unset($_SESSION['errors']); ?>
    </div>
<?php endif; ?>

<!-- ENCABEZADO responsive -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">

    <div class="flex items-center gap-3">
        <a href="/dashboard_home"
           class="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver
        </a>
        <div>
            <h1 class="text-xl font-semibold text-gray-800">Caducidad en Productos</h1>
            <p class="text-sm text-gray-500 mt-0.5 hidden sm:block">Días de caducidad por canal, grupo y subgrupo.</p>
        </div>
    </div>

    <a href="/inv-caducidad/create"
       class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700
              text-white text-sm font-semibold rounded-lg shadow-sm transition self-start sm:self-auto">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nueva caducidad
    </a>

</div>

<!-- TABLA con scroll horizontal en móvil -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[640px]">
            <thead>
                <tr class="bg-gradient-to-r from-indigo-700 to-blue-600 text-white text-xs uppercase tracking-wide">
                    <th class="px-4 py-3 text-left font-semibold">Canal</th>
                    <th class="px-4 py-3 text-left font-semibold">Grupo</th>
                    <th class="px-4 py-3 text-left font-semibold">Subgrupo</th>
                    <th class="px-4 py-3 text-center font-semibold">Presentación</th>
                    <th class="px-4 py-3 text-center font-semibold">Días</th>
                    <th class="px-4 py-3 text-center font-semibold">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (!empty($registros)): ?>
                    <?php foreach ($registros as $r): ?>
                    <tr class="hover:bg-indigo-50 transition">

                        <td class="px-4 py-2.5 text-gray-700">
                            <span class="font-semibold text-indigo-700"><?= htmlspecialchars($r['canal']) ?></span>
                            <?php if (!empty($r['canal_desc'])): ?>
                                <span class="text-gray-400 text-xs ml-1">— <?= htmlspecialchars($r['canal_desc']) ?></span>
                            <?php endif; ?>
                        </td>

                        <td class="px-4 py-2.5 text-gray-700">
                            <span class="font-mono text-xs bg-gray-100 px-1.5 py-0.5 rounded"><?= htmlspecialchars($r['codgrupo']) ?></span>
                            <?php if (!empty($r['grupo_desc'])): ?>
                                <span class="ml-1 text-gray-600"><?= htmlspecialchars($r['grupo_desc']) ?></span>
                            <?php endif; ?>
                        </td>

                        <td class="px-4 py-2.5 text-gray-700">
                            <span class="font-mono text-xs bg-gray-100 px-1.5 py-0.5 rounded"><?= htmlspecialchars($r['codsubg']) ?></span>
                            <?php if (!empty($r['subgrupo_desc'])): ?>
                                <span class="ml-1 text-gray-600"><?= htmlspecialchars($r['subgrupo_desc']) ?></span>
                            <?php endif; ?>
                        </td>

                        <td class="px-4 py-2.5 text-center">
                            <?php $badgeClass = $r['presentacion'] === 'Refrigeración' ? 'bg-blue-100 text-blue-700' : 'bg-cyan-100 text-cyan-700'; ?>
                            <span class="<?= $badgeClass ?> text-xs font-semibold px-2.5 py-0.5 rounded-full whitespace-nowrap">
                                <?= htmlspecialchars($r['presentacion']) ?>
                            </span>
                        </td>

                        <td class="px-4 py-2.5 text-center font-semibold text-gray-800">
                            <?= (int)$r['cantidad'] ?>
                        </td>

                        <td class="px-4 py-2.5 text-center">
                            <div class="flex items-center justify-center gap-3">

                                <a href="/inv-caducidad/<?= urlencode($r['canal']) ?>/<?= urlencode($r['codgrupo']) ?>/<?= urlencode($r['codsubg']) ?>"
                                   class="text-gray-500 hover:text-gray-700 font-medium text-xs hover:underline">
                                    Ver
                                </a>

                                <a href="/inv-caducidad/<?= urlencode($r['canal']) ?>/<?= urlencode($r['codgrupo']) ?>/<?= urlencode($r['codsubg']) ?>/edit"
                                   class="text-indigo-600 hover:text-indigo-800 font-medium text-xs hover:underline">
                                    Editar
                                </a>

                                <form method="POST"
                                      action="/inv-caducidad/<?= urlencode($r['canal']) ?>/<?= urlencode($r['codgrupo']) ?>/<?= urlencode($r['codsubg']) ?>/delete"
                                      onsubmit="return confirm('¿Eliminar este registro de caducidad?')">
                                    <button type="submit"
                                            class="text-red-500 hover:text-red-700 font-medium text-xs hover:underline">
                                        Eliminar
                                    </button>
                                </form>

                            </div>
                        </td>

                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-400 text-sm">
                            No hay registros de caducidad.
                            <a href="/inv-caducidad/create" class="text-indigo-600 hover:underline">Crear el primero</a>.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
