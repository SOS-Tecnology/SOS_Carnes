<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'SOS-Carnes' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="/css/app.css" rel="stylesheet">
    <link href="/css/dashboard.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        /* ── SIDEBAR ─────────────────────────────────────────── */
        #sidebar {
            width: 240px;
            min-height: calc(100vh - 56px);
            background: #1e2a3a;
            transition: width 0.28s ease;
            overflow: hidden;
            flex-shrink: 0;
        }
        #sidebar.collapsed { width: 64px; }

        .sb-label {
            white-space: nowrap;
            overflow: hidden;
            transition: opacity 0.2s, max-width 0.25s;
            max-width: 160px;
            opacity: 1;
        }
        #sidebar.collapsed .sb-label { opacity: 0; max-width: 0; }

        .sb-chevron { transition: transform 0.25s; flex-shrink: 0; }
        #sidebar.collapsed .sb-chevron { display: none; }

        .sb-sub { overflow: hidden; max-height: 0; transition: max-height 0.3s ease; }
        .sb-sub.open { max-height: 300px; }
        #sidebar.collapsed .sb-sub { max-height: 0 !important; }

        .sb-item.active, .sb-item:hover { background: rgba(255,255,255,0.08); }
        .sb-subitem:hover { background: rgba(255,255,255,0.06); }

        .sb-tooltip {
            display: none;
            position: absolute;
            left: 64px;
            top: 50%;
            transform: translateY(-50%);
            background: #1e2a3a;
            color: #fff;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            z-index: 9999;
            pointer-events: none;
            box-shadow: 2px 2px 8px rgba(0,0,0,0.4);
        }
        #sidebar.collapsed .sb-item { position: relative; }
        #sidebar.collapsed .sb-item:hover .sb-tooltip { display: block; }
    </style>
</head>

<body class="min-h-screen flex flex-col bg-gray-100 text-gray-800">

    <!-- ══ HEADER ══════════════════════════════════════════════ -->
    <header class="bg-gray-700 text-white shadow-sm" style="height:56px;">
        <div class="px-4 h-full flex items-center justify-between">

            <div class="flex items-center gap-3">
                <button id="sidebarToggle"
                    class="text-gray-300 hover:text-white focus:outline-none p-1 rounded">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                <a href="/dashboard_home" class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-blue-600 rounded flex items-center justify-center
                                font-bold text-sm hover:bg-blue-700 transition text-white">
                        SC
                    </div>
                    <div class="leading-tight">
                        <div class="font-semibold text-sm">SOS-Carnes</div>
                        <div class="text-xs text-gray-400">Sistema de Alistamiento</div>
                    </div>
                </a>
            </div>

            <?php if (isset($_SESSION['user'])): ?>
            <div class="flex items-center gap-3 relative">

                <a href="/dashboard_home" title="Inicio"
                    class="text-gray-400 hover:text-white transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M3 9.75L12 4l9 5.75V20a1 1 0 01-1 1h-5v-6H9v6H4a1 1 0 01-1-1V9.75z"/>
                    </svg>
                </a>

                <button onclick="toggleUserMenu()"
                    class="flex items-center gap-2 text-sm hover:text-gray-300 focus:outline-none">
                    <span><?= htmlspecialchars($_SESSION['user']['name'] ?? 'Usuario') ?></span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div id="userMenu"
                    class="hidden absolute right-0 top-10 w-48 bg-white border rounded-lg shadow-lg z-50">
                    <a href="/usuarios/create"
                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        Crear usuario
                    </a>
                    <div class="border-t"></div>
                    <a href="/logout"
                        class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                        Cerrar sesión
                    </a>
                </div>

            </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- ══ BODY: sidebar + contenido ════════════════════════════ -->
    <div class="flex flex-1">

        <!-- ── SIDEBAR ─────────────────────────────────────────── -->
        <aside id="sidebar">
            <nav class="py-3">

                <?php
                $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

                function sbItem(string $href, string $label, string $svgPath, string $current): void {
                    $active = ($current === $href) ? 'active' : '';
                    echo <<<HTML
                    <a href="{$href}" class="sb-item {$active} flex items-center gap-3 px-4 py-2.5 text-gray-300 hover:text-white cursor-pointer text-sm">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            {$svgPath}
                        </svg>
                        <span class="sb-label">{$label}</span>
                        <span class="sb-tooltip">{$label}</span>
                    </a>
                    HTML;
                }
                ?>

                <!-- Panel -->
                <?php sbItem(
                    '/dashboard_home', 'Panel',
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M3 3h7v7H3zm11 0h7v7h-7zM3 14h7v7H3zm11 0h7v7h-7z"/>',
                    $currentPath
                ); ?>

                <!-- Planilla de Pedidos -->
                <?php sbItem(
                    '/planilla-pedidos', 'Planilla de Pedidos',
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0
                           00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2
                           2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>',
                    $currentPath
                ); ?>

                <div class="border-t border-gray-700 mx-4 my-2"></div>

                <!-- Usuarios -->
                <?php sbItem(
                    '/usuarios', 'Usuarios',
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                        d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87
                           M12 12a4 4 0 100-8 4 4 0 000 8z"/>',
                    $currentPath
                ); ?>

            </nav>
        </aside>

        <!-- ── ÁREA DE TRABAJO ──────────────────────────────────── -->
        <main class="flex-1 overflow-auto p-6">
            <?= $content ?>
        </main>

    </div>

    <footer class="bg-white border-t text-center py-3 text-xs text-gray-400">
        &copy; <?= date('Y') ?> SOS Technology | SOS-Carnes
    </footer>

    <script>
        const sidebar = document.getElementById('sidebar');
        if (localStorage.getItem('sidebarCollapsed') === '1') {
            sidebar.classList.add('collapsed');
        }
        document.getElementById('sidebarToggle').addEventListener('click', function () {
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed') ? '1' : '0');
        });

        function toggleUserMenu() {
            document.getElementById('userMenu').classList.toggle('hidden');
        }
        document.addEventListener('click', function (e) {
            const menu = document.getElementById('userMenu');
            if (menu && !menu.contains(e.target) && !e.target.closest('button[onclick="toggleUserMenu()"]')) {
                menu.classList.add('hidden');
            }
        });
    </script>

</body>
</html>
