<?php
session_start();
include 'db.php';

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
        if ($admin_code === '1212' && !$user['is_admin']) {
            $stmt2 = $conexao->prepare('UPDATE users SET is_admin = 1 WHERE id = ?');
            $stmt2->execute([$user['id']]);
            $user['is_admin'] = 1;
        }
        $_SESSION['user'] = $username;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Usuário ou senha inválidos!';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Fórum</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <div style="text-align:center;margin:20px auto;">
        <img src="https://i.ibb.co/d0F66Kw6/Whats-App-Image-2025-05-29-at-18-37-15-removebg-preview.png" alt="Logo" class="logo-img" style="height:80px;">
    </div>
    <div class="auth-forms" style="max-width:400px;margin:20px auto;">
        <h2>Login</h2>
        <?php if (isset($error)) echo '<div style="color:red">'.htmlspecialchars($error).'</div>'; ?>
        <form method="post">
            <input type="text" name="username" placeholder="Usuário" required><br>
            <input type="password" name="password" placeholder="Senha" required><br>
            <input type="password" name="admin_code" placeholder="Código administrativo (opcional)"><br>
            <button type="submit" name="login">Entrar</button>
        </form>
        <p>Não tem conta? <a href="register.php">Cadastre-se</a></p>
        <a href="index.php">&larr; Voltar</a>
    </div>
</body>
</html>
