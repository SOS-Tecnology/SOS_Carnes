<?php
namespace App\Middleware;

class AuthMiddleware
{
    public function __invoke($request, $handler)
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }

        $timeout = 1800;
        if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout) {
            session_unset();
            session_destroy();
            header('Location: /login');
            exit;
        }

        $_SESSION['LAST_ACTIVITY'] = time();
        return $handler->handle($request);
    }
}
