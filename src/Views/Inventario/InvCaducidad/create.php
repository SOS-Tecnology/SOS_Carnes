<?php if (!empty($_SESSION['errors'])): ?>
    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
        <?php foreach ($_SESSION['errors'] as $e): ?>
            <p>⚠ <?= htmlspecialchars($e) ?></p>
        <?php endforeach; ?>
        <?php unset($_SESSION['errors']); ?>
    </div>
<?php endif; ?>

<div class="max-w-lg mx-auto mt-4 px-4 sm:px-0">

    <!-- ENCABEZADO -->
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-800">Nueva caducidad</h1>
            <p class="text-sm text-gray-500 mt-0.5">Canal, grupo, subgrupo, cliente (opcional), presentación y días.</p>
        </div>
        <a href="/inv-caducidad"
           class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1 shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver
        </a>
    </div>

    <!-- FORMULARIO -->
    <form method="POST" action="/inv-caducidad/store" id="form-caducidad"
          class="bg-white rounded-2xl shadow-sm border border-gray-100 divide-y divide-gray-100">

        <!-- Canal -->
        <div class="px-5 py-3">
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Canal</label>
            <select name="canal" id="sel-canal" required
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white
                           focus:outline-none focus:ring-2 focus:ring-blue-400 transition">
                <option value="" disabled selected>Seleccionar canal…</option>
                <?php foreach ($canales as $c): ?>
                    <option value="<?= htmlspecialchars($c['codigotc']) ?>">
                        <?= htmlspecialchars($c['codigotc']) ?> — <?= htmlspecialchars($c['descripcion'] ?? '') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Grupo -->
        <div class="px-5 py-3">
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Grupo</label>
            <select name="codgrupo" id="sel-grupo" required
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white
                           focus:outline-none focus:ring-2 focus:ring-blue-400 transition">
                <option value="" disabled selected>Seleccionar grupo…</option>
                <?php foreach ($grupos as $g): ?>
                    <option value="<?= htmlspecialchars($g['codgrupo']) ?>">
                        <?= htmlspecialchars($g['codgrupo']) ?> — <?= htmlspecialchars($g['descripcion'] ?? '') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Subgrupo -->
        <div class="px-5 py-3">
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Subgrupo</label>
            <select name="codsubg" id="sel-subgrupo" required
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white
                           focus:outline-none focus:ring-2 focus:ring-blue-400 transition">
                <option value="" disabled selected>Seleccione primero un grupo…</option>
                <?php foreach ($subgrupos as $s): ?>
                    <option value="<?= htmlspecialchars($s['codsubg']) ?>"
                            data-grupo="<?= htmlspecialchars($s['codgrupo']) ?>">
                        <?= htmlspecialchars($s['codsubg']) ?> — <?= htmlspecialchars($s['descripcion'] ?? '') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Cliente (opcional) -->
        <div class="px-5 py-3">
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">
                Cliente <span class="font-normal text-gray-400 normal-case">(opcional — vacío = aplica a todos)</span>
            </label>
            <select name="codc"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white
                           focus:outline-none focus:ring-2 focus:ring-blue-400 transition">
                <option value="">— Genérico (todos los clientes) —</option>
                <?php foreach ($clientes as $cl): ?>
                    <option value="<?= htmlspecialchars($cl['codcli']) ?>">
                        <?= htmlspecialchars($cl['codcli']) ?> — <?= htmlspecialchars($cl['razonsoc'] ?? '') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Presentación -->
        <div class="px-5 py-3">
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Presentación</label>
            <select name="presentacion" required
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white
                           focus:outline-none focus:ring-2 focus:ring-blue-400 transition">
                <option value="" disabled selected>Seleccionar…</option>
                <option value="Refrigeración">Refrigeración</option>
                <option value="Congelación">Congelación</option>
            </select>
        </div>

        <!-- Días -->
        <div class="px-5 py-3">
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Días de caducidad</label>
            <input type="number" name="cantidad" required min="0" max="99999"
                   placeholder="Ej. 30"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm
                          focus:outline-none focus:ring-2 focus:ring-blue-400
                          placeholder-gray-300 transition">
            <p class="text-xs text-gray-400 mt-1">Número de días antes del vencimiento.</p>
        </div>

        <!-- Botones -->
        <div class="px-5 py-3 flex justify-end gap-3 bg-gray-50 rounded-b-2xl">
            <a href="/inv-caducidad"
               class="px-4 py-2 text-sm text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 transition">
                Cancelar
            </a>
            <button type="submit"
                    class="px-5 py-2 text-sm font-semibold text-white bg-blue-600
                           rounded-lg hover:bg-blue-700 transition shadow-sm">
                Crear registro
            </button>
        </div>

    </form>
</div>

<script>
const selGrupo    = document.getElementById('sel-grupo');
const selSubgrupo = document.getElementById('sel-subgrupo');
const allOptions  = Array.from(selSubgrupo.querySelectorAll('option[data-grupo]'));

function filtrarSubgrupos() {
    const grupoVal = selGrupo.value;
    selSubgrupo.innerHTML = '<option value="" disabled selected>Seleccionar subgrupo…</option>';
    allOptions
        .filter(o => o.dataset.grupo === grupoVal)
        .forEach(o => selSubgrupo.appendChild(o.cloneNode(true)));
}
selGrupo.addEventListener('change', filtrarSubgrupos);
</script>