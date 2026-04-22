<?php
if (!isset($_SESSION['user'])) { header('Location: /login'); exit; }
?>

<div>
    <p class="text-gray-500 text-sm mb-6">
        Bienvenido, <strong><?= htmlspecialchars($_SESSION['user']['name'] ?? '') ?></strong>.
        Selecciona una opción para comenzar.
    </p>

    <div class="dashboard-grid">

        <!-- Preparación de Pedido -->
        <a href="/preparacion-pedido"
            class="bg-blue-700 hover:bg-blue-800 text-white rounded-2xl shadow-lg p-8
                   flex flex-col items-center justify-center text-center
                   transition transform hover:-translate-y-1 min-h-48">

            <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14 mb-4 opacity-90"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2
                       M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2
                       m-6 9l2 2 4-4"/>
            </svg>

            <h3 class="text-xl font-bold">Preparación de Pedido</h3>
            <p class="text-sm opacity-80 mt-2">Consolidar y generar documento AP.</p>

        </a>

        <!-- Planilla de Pedidos -->
        <a href="/planilla-pedidos"
            class="bg-green-700 hover:bg-green-800 text-white rounded-2xl shadow-lg p-8
                   flex flex-col items-center justify-center text-center
                   transition transform hover:-translate-y-1 min-h-48">

            <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14 mb-4 opacity-90"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2
                       M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2
                       m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>

            <h3 class="text-xl font-bold">Planilla de Pedidos</h3>
            <p class="text-sm opacity-80 mt-2">Gestión y registro de pedidos.</p>

        </a>

    </div>
</div>
