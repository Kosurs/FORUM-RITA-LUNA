<?php
// auth_bancheck.php: Checa se usuário está banido
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
if (isset($_SESSION['user'])) {
    $stmt = $conexao->prepare('SELECT is_banned FROM users WHERE username = ?');
    $stmt->execute([$_SESSION['user']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && $user['is_banned']) {
        session_destroy();
        header('Location: banido.php');
        exit;
    }
}
