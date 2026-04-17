<?php if (!empty($_SESSION['success'])): ?>
    <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-4 py-3 text-sm">
        ✔ <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>

<div class="flex items-center justify-between mb-6">

    <a href="/dashboard_home"
       class="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Volver
    </a>

    <div>
        <h1 class="text-xl font-semibold text-gray-800 text-right">Usuarios del sistema</h1>
        <p class="text-sm text-gray-500 mt-0.5 text-right">Gestión de cuentas de acceso.</p>
    </div>

    <a href="/usuarios/create"
       class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700
              text-white text-sm font-semibold rounded-lg shadow-sm transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nuevo usuario
    </a>

</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

    <table class="w-full text-sm">
        <thead>
            <tr class="bg-gradient-to-r from-indigo-700 to-blue-600 text-white text-xs uppercase tracking-wide">
                <th class="px-6 py-3.5 text-left font-semibold">Nombre</th>
                <th class="px-6 py-3.5 text-left font-semibold">Correo</th>
                <th class="px-6 py-3.5 text-left font-semibold">Rol</th>
                <th class="px-6 py-3.5 text-center font-semibold">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <?php if (!empty($usuarios)): ?>
                <?php foreach ($usuarios as $u): ?>
                <tr class="hover:bg-indigo-50 transition">

                    <td class="px-6 py-3 font-medium text-gray-800">
                        <?= htmlspecialchars($u['name'] ?? '—') ?>
                    </td>

                    <td class="px-6 py-3 text-gray-600">
                        <?= htmlspecialchars($u['email'] ?? '—') ?>
                    </td>

                    <td class="px-6 py-3">
                        <?php
                        $rol   = $u['rol'] ?? '';
                        $badge = match($rol) {
                            'Administrador' => 'bg-indigo-100 text-indigo-700',
                            'Produccion'    => 'bg-blue-100 text-blue-700',
                            'Ventas'        => 'bg-green-100 text-green-700',
                            default         => 'bg-gray-100 text-gray-600',
                        };
                        ?>
                        <span class="<?= $badge ?> text-xs font-semibold px-2.5 py-0.5 rounded-full">
                            <?= htmlspecialchars($rol ?: 'Sin rol') ?>
                        </span>
                    </td>

                    <td class="px-6 py-3 text-center">
                        <div class="flex items-center justify-center gap-3">

                            <a href="/usuarios/<?= $u['id'] ?>/edit"
                               class="text-indigo-600 hover:text-indigo-800 font-medium text-xs hover:underline">
                                Editar
                            </a>

                            <form method="POST" action="/usuarios/<?= $u['id'] ?>/delete"
                                  onsubmit="return confirm('¿Eliminar usuario <?= htmlspecialchars(addslashes($u['name'] ?? '')) ?>?')">
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
                    <td colspan="4" class="px-6 py-10 text-center text-gray-400 text-sm">
                        No hay usuarios registrados.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</div>
