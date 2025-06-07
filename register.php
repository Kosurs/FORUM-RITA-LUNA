<?php
session_start();
require 'db.php';
// Proteção global: se usuário logado estiver banido, redireciona para banido.php
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
    <header style="margin-top:20px">
        <div class="center">
            <div><img class="logo-site" src="https://i.ibb.co/d0F66Kw6/Whats-App-Image-2025-05-29-at-18-37-15-removebg-preview.png" alt="Logo"></div>
            <div class="titulo-site">Fórum Rita Matos Luna</div>
            <div><img class="logo-site" src="https://i.ibb.co/d0F66Kw6/Whats-App-Image-2025-05-29-at-18-37-15-removebg-preview.png" alt="Logo"></div>
        </div>
    </header>
    <div class="caixa">
        <p class="titulo-pagina center">Cadastro</p>
        <div class="caixa-form center-column">
            <?php if (isset($error)) echo '<div class="mensagem-erro">'.htmlspecialchars($error).'</div>'; ?>
            <form class="login-conteudo center-column" method="post">
                <input class="login-texto" type="text" name="username" placeholder="Usuário. . ." required><br>
                <input class="login-texto" type="password" name="password" placeholder="Senha. . ." required><br>
                <button class="botao-verde" type="submit" name="register">Cadastrar</button>
            </form>
            <p>Já tem uma conta? <a href="login.php">Entrar</a></p>
            <a href="index.php">Voltar</a>
        </div>
    </div>
    <footer>
        <span>&copy; Saulo, Samuel Oliveira, Samuel Cavalcante | All Rights Reserved</span>
    </footer>
</body>
</html>
