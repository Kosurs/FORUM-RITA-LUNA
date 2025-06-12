<?php
// auth_login.php: Lógica de login
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $admin_code = isset($_POST['admin_code']) ? trim($_POST['admin_code']) : '';
    $stmt = $conexao->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password'])) {
        // Se código admin correto, atualiza permissão
        if ($admin_code !== '' && $admin_code !== '1212') {
            $error = 'Código administrativo incorreto!';
        } else {
            if ($admin_code === '1212' && !$user['is_admin']) {
                $stmt2 = $conexao->prepare('UPDATE users SET is_admin = 1 WHERE id = ?');
                $stmt2->execute([$user['id']]);
                // Atualiza o array $user após promover
                $stmt = $conexao->prepare('SELECT * FROM users WHERE username = ?');
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            $_SESSION['user'] = $username;
            header('Location: index.php');
            exit;
        }
    } else {
        $error = 'Usuário ou senha inválidos!';
    }
}
