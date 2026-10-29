<?php
require_once __DIR__ . '/../helpers/db.php';

// Выход только через POST с CSRF-токеном (GET-ссылка «выйти» небезопасна)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

redirect('index.php');
