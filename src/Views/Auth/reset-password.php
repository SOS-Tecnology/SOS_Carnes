<?php $errors = $_SESSION['errors'] ?? []; unset($_SESSION['errors']); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SOS-Carnes | Nueva contraseña</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center
             bg-gradient-to-br from-green-900 to-green-600">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8">
        <div class="text-center mb-6">
            <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg class="w-7 h-7 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h1 class="text-xl font-bold text-gray-800">Nueva contraseña</h1>
        </div>
        <?php if (!empty($errors)): ?>
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
                <?php foreach ($errors as $e): ?><p>⚠ <?= htmlspecialchars($e) ?></p><?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="/reset-password/<?= htmlspecialchars($token) ?>" id="frm">
            <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Nueva contraseña</label>
                <input type="password" name="password" id="p1" required
                       class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div class="mb-5">
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Confirmar</label>
                <input type="password" name="password_confirm" id="p2" required
                       class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                <p id="mm" class="text-xs text-red-500 mt-1 hidden">Las contraseñas no coinciden.</p>
            </div>
            <button type="submit"
                    class="w-full bg-green-700 hover:bg-green-800 text-white font-semibold py-2.5 rounded-lg transition text-sm">
                Guardar
            </button>
        </form>
        <p class="text-center text-sm text-gray-400 mt-5">
            <a href="/login" class="text-green-700 hover:underline">← Volver</a>
        </p>
    </div>
    <script>
    document.getElementById('frm').addEventListener('submit', function(e) {
        if (document.getElementById('p1').value !== document.getElementById('p2').value) {
            document.getElementById('mm').classList.remove('hidden'); e.preventDefault();
        }
    });
    </script>
</body>
</html>
