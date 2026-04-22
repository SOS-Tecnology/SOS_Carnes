<?php
session_name('SOSCARNES');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'secure'   => false,
    'samesite' => 'Lax'
]);
session_start();

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/config.php';

use Slim\Factory\AppFactory;
use Medoo\Medoo;
use Dotenv\Dotenv;
use App\Middleware\AuthMiddleware;
use App\Controllers\PlanillaPedidosController;
use App\Controllers\PreparacionPedidoController;
use App\Controllers\StickerController;

// ── Entorno ───────────────────────────────────────────────
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// ── Base de datos ─────────────────────────────────────────
$GLOBALS['db'] = new Medoo([
    'database_type' => $_ENV['DB_TYPE'],
    'database_name' => $_ENV['DB_NAME'],
    'server'        => $_ENV['DB_HOST'],
    'username'      => $_ENV['DB_USER'],
    'password'      => $_ENV['DB_PASS'],
    'charset'       => 'utf8mb4',
    'collation'     => 'utf8mb4_unicode_ci',
]);

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

ini_set('display_errors', 1);
error_reporting(E_ALL);

// ── Helper renderView ────────────────────────────────────
function renderView($response, $viewPath, $title, $data = [])
{
    extract($data);
    ob_start();
    include $viewPath;
    $content = ob_get_clean();
    include __DIR__ . '/../src/Views/layouts/dashboard.php';
    return $response;
}

// ─────────────────────────────────────────────────────────
// RUTAS PÚBLICAS
// ─────────────────────────────────────────────────────────

$app->get('/', function ($request, $response) {
    $loc = isset($_SESSION['user']) ? '/dashboard_home' : '/login';
    return $response->withHeader('Location', $loc)->withStatus(302);
});

// ── Login ─────────────────────────────────────────────────
$app->get('/login', function ($request, $response) {
    ob_start();
    include __DIR__ . '/../src/Views/Auth/login.php';
    $response->getBody()->write(ob_get_clean());
    return $response;
});

$app->post('/login', function ($request, $response) {
    $data     = $request->getParsedBody();
    $email    = trim($data['email']    ?? '');
    $password = trim($data['password'] ?? '');

    if ($email === '' || $password === '') {
        $_SESSION['errors'] = ['Todos los campos son obligatorios'];
        return $response->withHeader('Location', '/login')->withStatus(302);
    }

    $user = $GLOBALS['db']->get('users', '*', ['email' => $email]);

    if (!$user || !password_verify($password, $user['password'])) {
        $_SESSION['errors'] = ['Credenciales incorrectas'];
        return $response->withHeader('Location', '/login')->withStatus(302);
    }

    $_SESSION['user'] = [
        'id'         => $user['id'],
        'name'       => $user['name'],
        'email'      => $user['email'],
        'rol'        => $user['rol'],
        'codtipocli' => trim($user['tipocliente'] ?? '')
    ];
    $_SESSION['LAST_ACTIVITY'] = time();

    return $response->withHeader('Location', '/dashboard_home')->withStatus(302);
});

$app->get('/logout', function ($request, $response) {
    session_unset();
    session_destroy();
    return $response->withHeader('Location', '/login')->withStatus(302);
});

// ── Recuperación de contraseña ────────────────────────────
$app->get('/forgot-password', function ($request, $response) {
    ob_start();
    include __DIR__ . '/../src/Views/Auth/forgot-password.php';
    $response->getBody()->write(ob_get_clean());
    return $response;
});

$app->post('/forgot-password', function ($request, $response) {
    $email = trim($request->getParsedBody()['email'] ?? '');
    $user  = $GLOBALS['db']->get("users", ["id","name","email"], ["email" => $email]);

    $_SESSION['success'] = "Si el correo está registrado, recibirás un enlace en breve.";

    if ($user) {
        $GLOBALS['db']->query("
            CREATE TABLE IF NOT EXISTS password_resets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL,
                token VARCHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                INDEX (token), INDEX (email)
            )
        ");

        $token   = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600);

        $GLOBALS['db']->delete("password_resets", ["email" => $email]);
        $GLOBALS['db']->insert("password_resets", [
            "email" => $email, "token" => $token, "expires_at" => $expires
        ]);

        $url  = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/reset-password/' . $token;
        $body = "Hola {$user['name']},\n\nRestablece tu contraseña (válido 1 hora):\n\n{$url}\n\nSOS-Carnes";
        @mail($email, "Restablecer contraseña - SOS Carnes", $body,
              "From: noreply@sos-carnes.local\r\nContent-Type: text/plain; charset=UTF-8");
    }

    return $response->withHeader('Location', '/forgot-password')->withStatus(302);
});

$app->get('/reset-password/{token}', function ($request, $response, $args) {
    $token = $args['token'];
    $reset = $GLOBALS['db']->get("password_resets", "*",
        ["token" => $token, "expires_at[>]" => date('Y-m-d H:i:s')]);

    if (!$reset) {
        $_SESSION['errors'] = ["El enlace no es válido o ha expirado."];
        return $response->withHeader('Location', '/forgot-password')->withStatus(302);
    }

    ob_start();
    include __DIR__ . '/../src/Views/Auth/reset-password.php';
    $response->getBody()->write(ob_get_clean());
    return $response;
});

$app->post('/reset-password/{token}', function ($request, $response, $args) {
    $token    = $args['token'];
    $password = $request->getParsedBody()['password'] ?? '';
    $reset    = $GLOBALS['db']->get("password_resets", "*",
        ["token" => $token, "expires_at[>]" => date('Y-m-d H:i:s')]);

    if (!$reset) {
        $_SESSION['errors'] = ["El enlace no es válido o ha expirado."];
        return $response->withHeader('Location', '/forgot-password')->withStatus(302);
    }
    if (strlen($password) < 8) {
        $_SESSION['errors'] = ["La contraseña debe tener al menos 8 caracteres."];
        return $response->withHeader('Location', '/reset-password/' . $token)->withStatus(302);
    }

    $GLOBALS['db']->update("users",
        ["password" => password_hash($password, PASSWORD_DEFAULT)],
        ["email"    => $reset['email']]);
    $GLOBALS['db']->delete("password_resets", ["token" => $token]);

    $_SESSION['success'] = "Contraseña actualizada. Ya puedes iniciar sesión.";
    return $response->withHeader('Location', '/login')->withStatus(302);
});

// ─────────────────────────────────────────────────────────
// RUTAS PROTEGIDAS (requieren sesión)
// ─────────────────────────────────────────────────────────
$authMiddleware = new AuthMiddleware();

$app->group('', function ($group) {

    // ── Dashboard ─────────────────────────────────────────
    $group->get('/dashboard_home', function ($request, $response) {
        return renderView($response, __DIR__ . '/../src/Views/dashboard_home.php', 'Inicio', []);
    });

    // ── Planilla de Pedidos ───────────────────────────────
    $group->get('/planilla-pedidos', function ($request, $response) {
        $ctrl = new PlanillaPedidosController($GLOBALS['db']);
        return $ctrl->index($request, $response);
    });

    $group->post('/planilla-pedidos/cerrar/{nrodoc}', function ($request, $response, $args) {
        $ctrl = new PlanillaPedidosController($GLOBALS['db']);
        return $ctrl->cerrar($request, $response, $args);
    });

    $group->get('/planilla-pedidos/{nrodoc}/detalle', function ($request, $response, $args) {
        $ctrl = new PlanillaPedidosController($GLOBALS['db']);
        return $ctrl->detalle($request, $response, $args);
    });

    $group->get('/planilla-pedidos/{nrodoc}/item/{registro}', function ($request, $response, $args) {
        $ctrl = new PlanillaPedidosController($GLOBALS['db']);
        return $ctrl->verItem($request, $response, $args);
    });

    $group->post('/planilla-pedidos/{nrodoc}/item/{registro}', function ($request, $response, $args) {
        $ctrl = new PlanillaPedidosController($GLOBALS['db']);
        return $ctrl->actualizarItem($request, $response, $args);
    });

    $group->post('/planilla-pedidos/{nrodoc}/item/{registro}/eliminar', function ($request, $response, $args) {
        $ctrl = new PlanillaPedidosController($GLOBALS['db']);
        return $ctrl->eliminarLote($request, $response, $args);
    });

    // ── Preparación de Pedido ─────────────────────────────
    $group->get('/preparacion-pedido', function ($request, $response) {
        $ctrl = new PreparacionPedidoController($GLOBALS['db']);
        return $ctrl->index($request, $response);
    });

    $group->get('/preparacion-pedido/{nrodoc}/preparar', function ($request, $response, $args) {
        $ctrl = new PreparacionPedidoController($GLOBALS['db']);
        return $ctrl->preparar($request, $response, $args);
    });

    $group->post('/preparacion-pedido/{nrodoc}/preparar', function ($request, $response, $args) {
        $ctrl = new PreparacionPedidoController($GLOBALS['db']);
        return $ctrl->guardar($request, $response, $args);
    });

    // ── Sticker para despacho ─────────────────────────────
    $group->get('/sticker/generar', function ($request, $response) {
        $ctrl = new StickerController();
        return $ctrl->generar($request, $response);
    });

    $group->get('/api/planilla-pedidos', function ($request, $response) {
        $ctrl = new PlanillaPedidosController($GLOBALS['db']);
        return $ctrl->apiPedidos($request, $response);
    });

    // ── Usuarios ──────────────────────────────────────────
    $group->get('/usuarios', function ($request, $response) {
        $usuarios = $GLOBALS['db']->select("users", "*");
        return renderView($response, __DIR__ . '/../src/Views/Usuarios/index.php', "Usuarios", [
            'usuarios' => $usuarios
        ]);
    });

    $group->get('/usuarios/create', function ($request, $response) {
        return renderView($response, __DIR__ . '/../src/Views/Usuarios/create.php', "Nuevo Usuario", []);
    });

    $group->post('/usuarios/store', function ($request, $response) {
        $data = $request->getParsedBody();

        if ($GLOBALS['db']->has("users", ["email" => $data['email']])) {
            $_SESSION['errors'] = ["El correo ya está registrado."];
            return $response->withHeader('Location', '/usuarios/create')->withStatus(302);
        }

        $GLOBALS['db']->insert("users", [
            "name"        => $data['nombre'],
            "email"       => $data['email'],
            "password"    => password_hash($data['password'], PASSWORD_DEFAULT),
            "rol"         => $data['rol'],
            "tipocliente" => trim($data['tipocliente'] ?? '')
        ]);

        $_SESSION['success'] = "Usuario {$data['nombre']} creado correctamente.";
        return $response->withHeader('Location', '/usuarios')->withStatus(302);
    });

    $group->get('/usuarios/{id}/edit', function ($request, $response, $args) {
        $usuario = $GLOBALS['db']->get("users", "*", ["id" => (int)$args['id']]);
        if (!$usuario) {
            return $response->withHeader('Location', '/usuarios')->withStatus(302);
        }
        return renderView($response, __DIR__ . '/../src/Views/Usuarios/edit.php', "Editar Usuario", [
            'usuario' => $usuario
        ]);
    });

    $group->post('/usuarios/{id}/update', function ($request, $response, $args) {
        $id   = (int)$args['id'];
        $data = $request->getParsedBody();

        if ($GLOBALS['db']->has("users", ["email" => $data['email'], "id[!]" => $id])) {
            $_SESSION['errors'] = ["El correo ya está en uso por otro usuario."];
            return $response->withHeader('Location', '/usuarios/' . $id . '/edit')->withStatus(302);
        }

        $campos = [
            "name"       => $data['nombre'],
            "email"      => $data['email'],
            "rol"        => $data['rol'],
            "tipocliente" => trim($data['tipocliente'] ?? '')
        ];
        if (!empty($data['password'])) {
            $campos["password"] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $GLOBALS['db']->update("users", $campos, ["id" => $id]);
        $_SESSION['success'] = "Usuario actualizado correctamente.";
        return $response->withHeader('Location', '/usuarios')->withStatus(302);
    });

    $group->post('/usuarios/{id}/delete', function ($request, $response, $args) {
        $GLOBALS['db']->delete("users", ["id" => (int)$args['id']]);
        $_SESSION['success'] = "Usuario eliminado.";
        return $response->withHeader('Location', '/usuarios')->withStatus(302);
    });

})->add($authMiddleware);

$app->run();
