<?php
// auth_delete_account.php: Lógica para exclusão de conta
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
if (isset($_POST['delete_account']) && isset($_SESSION['user'])) {
    $username = $_SESSION['user'];
    $stmt = $conexao->prepare('DELETE FROM users WHERE username = ?');
    $stmt->execute([$username]);
    session_destroy();
    header('Location: index.php');
    exit;
}
