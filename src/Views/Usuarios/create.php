<?php if (!empty($_SESSION['errors'])): ?>
    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
        <?php foreach ($_SESSION['errors'] as $e): ?>
            <p>⚠ <?= htmlspecialchars($e) ?></p>
        <?php endforeach; ?>
        <?php unset($_SESSION['errors']); ?>
    </div>
<?php endif; ?>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-4 py-3 text-sm">
        ✔ <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>

<div class="max-w-lg mx-auto mt-6">

    <!-- ENCABEZADO -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-800">Nuevo usuario</h1>
            <p class="text-sm text-gray-500 mt-0.5">Completa los datos para crear una cuenta de acceso.</p>
        </div>
        <a href="/usuarios"
            class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver
        </a>
    </div>

    <!-- FORMULARIO -->
    <form method="POST" action="/usuarios/store" id="form-usuario"
          class="bg-white rounded-2xl shadow-sm border border-gray-100 divide-y divide-gray-50">

        <!-- Nombre -->
        <div class="px-6 py-5">
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                Nombre completo
            </label>
            <input type="text" name="nombre" required
                   placeholder="Ej. Juan Pérez"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm
                          focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent
                          placeholder-gray-300 transition">
        </div>

        <!-- Email -->
        <div class="px-6 py-5">
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                Correo electrónico
            </label>
            <input type="email" name="email" required
                   placeholder="correo@empresa.com"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm
                          focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent
                          placeholder-gray-300 transition">
        </div>

        <!-- Contraseña -->
        <div class="px-6 py-5">
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                Contraseña
            </label>
            <div class="relative">
                <input type="password" name="password" id="inp-password" required
                       placeholder="Mínimo 8 caracteres"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm
                              focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent
                              placeholder-gray-300 pr-10 transition">
                <button type="button" onclick="togglePass()"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                    <svg id="eye-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943
                               9.542 7-1.274 4.057-5.064 7-9.542 7-4.477
                               0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
            </div>
            <p class="text-xs text-gray-400 mt-1">Mínimo 8 caracteres.</p>
        </div>

        <!-- Confirmar contraseña -->
        <div class="px-6 py-5">
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                Confirmar contraseña
            </label>
            <input type="password" name="password_confirm" id="inp-confirm" required
                   placeholder="Repite la contraseña"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm
                          focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent
                          placeholder-gray-300 transition">
            <p id="pass-mismatch" class="text-xs text-red-500 mt-1 hidden">Las contraseñas no coinciden.</p>
        </div>

        <!-- Rol -->
        <div class="px-6 py-5">
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                Rol
            </label>
            <select name="rol" required
                    class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm bg-white
                           focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition">
                <option value="" disabled selected>Seleccionar rol…</option>
                <option value="Administrador">Administrador</option>
                <option value="Produccion">Producción</option>
                <option value="Ventas">Ventas</option>
                <option value="Consulta">Solo consulta</option>
            </select>
        </div>

        <!-- Tipo de cliente -->
        <div class="px-6 py-5">
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                Tipo de cliente
            </label>
            <input type="text" name="tipocliente" placeholder="Ej. 01"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm
                          focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent
                          placeholder-gray-300 transition">
            <p class="text-xs text-gray-400 mt-1">Código del tipo de cliente en <code>geclientes.codtipocli</code>.</p>
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
                Crear usuario
            </button>
        </div>

    </form>
</div>

<script>
function togglePass() {
    const inp  = document.getElementById('inp-password');
    inp.type = inp.type === 'password' ? 'text' : 'password';
}

document.getElementById('form-usuario').addEventListener('submit', function (e) {
    const p1 = document.getElementById('inp-password').value;
    const p2 = document.getElementById('inp-confirm').value;
    const msg = document.getElementById('pass-mismatch');

    if (p1 !== p2) {
        msg.classList.remove('hidden');
        e.preventDefault();
        return;
    }
    msg.classList.add('hidden');

    if (p1.length < 8) {
        alert('La contraseña debe tener al menos 8 caracteres.');
        e.preventDefault();
    }
});

document.getElementById('inp-confirm').addEventListener('input', function () {
    const match = this.value === document.getElementById('inp-password').value;
    document.getElementById('pass-mismatch').classList.toggle('hidden', match || this.value === '');
});
</script>
