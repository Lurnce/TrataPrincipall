<?php
require_once __DIR__ . '/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirecionar('index.php');
csrf_validar();

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

// Nova sessão só para exibir a mensagem de despedida
session_start();
flash('Você saiu da sua conta.');
redirecionar('login.php');
