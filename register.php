<?php
session_start();
include 'db.php';

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
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Fórum</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <div style="text-align:center;margin:20px auto;">
        <img src="https://i.ibb.co/d0F66Kw6/Whats-App-Image-2025-05-29-at-18-37-15-removebg-preview.png" alt="Logo" class="logo-img" style="height:80px;">
    </div>
    <div class="auth-forms" style="max-width:400px;margin:20px auto;">
        <h2>Cadastro</h2>
        <?php if (isset($error)) echo '<div style="color:red">'.htmlspecialchars($error).'</div>'; ?>
        <form method="post">
            <input type="text" name="username" placeholder="Usuário" required><br>
            <input type="password" name="password" placeholder="Senha" required><br>
            <button type="submit" name="register">Cadastrar</button>
        </form>
        <p>Já tem conta? <a href="login.php">Entrar</a></p>
        <a href="index.php">&larr; Voltar</a>
    </div>
</body>
</html>
