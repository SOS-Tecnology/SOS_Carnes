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
            <h1 class="text-xl font-semibold text-gray-800">Editar usuario</h1>
            <p class="text-sm text-gray-500 mt-0.5"><?= htmlspecialchars($usuario['email'] ?? '') ?></p>
        </div>
        <a href="/usuarios"
           class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver
        </a>
    </div>

    <form method="POST" action="/usuarios/<?= $usuario['id'] ?>/update" id="form-edit"
          class="bg-white rounded-2xl shadow-sm border border-gray-100 divide-y divide-gray-50">

        <!-- Nombre -->
        <div class="px-6 py-5">
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                Nombre completo
            </label>
            <input type="text" name="nombre" required
                   value="<?= htmlspecialchars($usuario['name'] ?? '') ?>"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm
                          focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition">
        </div>

        <!-- Email -->
        <div class="px-6 py-5">
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                Correo electrónico
            </label>
            <input type="email" name="email" required
                   value="<?= htmlspecialchars($usuario['email'] ?? '') ?>"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm
                          focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition">
        </div>

        <!-- Rol -->
        <div class="px-6 py-5">
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                Rol
            </label>
            <select name="rol" required
                    class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm bg-white
                           focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition">
                <?php foreach (['Administrador','Produccion','Ventas','Consulta'] as $r): ?>
                    <option value="<?= $r ?>" <?= ($usuario['rol'] ?? '') === $r ? 'selected' : '' ?>>
                        <?= $r ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Tipo de cliente -->
        <div class="px-6 py-5">
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                Tipo de cliente
            </label>
            <input type="text" name="tipocliente"
                   value="<?= htmlspecialchars($usuario['tipocliente'] ?? '') ?>"
                   placeholder="Ej. 01"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm
                          focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent
                          placeholder-gray-300 transition">
            <p class="text-xs text-gray-400 mt-1">Código del tipo de cliente en <code>geclientes.codtipocli</code>.</p>
        </div>

        <!-- Nueva contraseña (opcional) -->
        <div class="px-6 py-5">
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                Nueva contraseña
                <span class="normal-case font-normal text-gray-400 ml-1">(dejar en blanco para no cambiar)</span>
            </label>
            <div class="relative">
                <input type="password" name="password" id="inp-password"
                       placeholder="Nueva contraseña…"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm
                              focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent
                              placeholder-gray-300 pr-10 transition">
                <button type="button" onclick="togglePass()"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943
                               9.542 7-1.274 4.057-5.064 7-9.542 7-4.477
                               0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Botones -->
        <div class="px-6 py-4 flex justify-end gap-3 bg-gray-50 rounded-b-2xl">
            <a href="/usuarios"
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

<script>
function togglePass() {
    const inp = document.getElementById('inp-password');
    inp.type  = inp.type === 'password' ? 'text' : 'password';
}
</script>
