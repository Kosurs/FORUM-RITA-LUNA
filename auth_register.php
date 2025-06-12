<?php
// auth_register.php: Lógica de cadastro
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    if ($username && $password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $conexao->prepare('INSERT INTO users (username, password) VALUES (?, ?)');
            $stmt->execute([$username, $hash]);
            $_SESSION['user'] = $username;
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Usuário já existe!';
        }
    } else {
        $error = 'Preencha todos os campos!';
    }
}
