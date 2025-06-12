<?php
require_once 'auth_bancheck.php';
require_once 'auth_login.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <header style="margin-top:20px">
        <div class="center">
            <div><img class="logo-site" src="https://i.ibb.co/d0F66Kw6/Whats-App-Image-2025-05-29-at-18-37-15-removebg-preview.png" alt="Logo"></div>
            <div class="titulo-site">Fórum Rita Matos Luna</div>
            <div><img class="logo-site" src="https://i.ibb.co/SD2TKbT4/image-rita.png" alt="Logo"></div>
        </div>
    </header>
    <div class="caixa">
        <p class="titulo-pagina center">Login</p>
        <div class="caixa-form center-column">
            <?php if (isset($error)) echo '<div class="mensagem-erro">'.htmlspecialchars($error).'</div>'; ?>
            <form class="login-conteudo center-column" method="post">
                <input class="login-texto" type="text" name="username" placeholder="Usuário. . ." required><br>
                <input class="login-texto" type="password" name="password" placeholder="Senha. . ." required><br>
                <input class="login-texto" type="password" name="admin_code" placeholder="Código administrativo. . . (opcional)"><br>
                <button class="botao-verde" type="submit" name="login">Entrar</button>
            </form>
            <p>Não tem uma conta? <a href="register.php">Cadastre-se</a></p>
            <a href="index.php">Voltar</a>
        </div>
    </div>
    <footer>
        <span>&copy; Saulo, Samuel Oliveira, Samuel Cavalcante | All Rights Reserved</span>
    </footer>
</body>
</html>
