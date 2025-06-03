<?php
// auth.php: Lógica de autenticação, cadastro e exclusão de conta
session_start();
include 'db.php';

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

// Redirecionamento global via SQL (tabela settings)
$redirect_url = $redirect_admin = $redirect_time = null;
try {
    $stmt = $conexao->prepare("SELECT chave, valor FROM settings WHERE chave IN ('redirect_global','redirect_admin','redirect_time')");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    if (isset($settings['redirect_global'])) $redirect_url = $settings['redirect_global'];
    if (isset($settings['redirect_admin'])) $redirect_admin = $settings['redirect_admin'];
    if (isset($settings['redirect_time'])) $redirect_time = $settings['redirect_time'];
} catch (Exception $e) {}
if ($redirect_url) {
    // Checa tempo de expiração (10 segundos)
    if ($redirect_time && (time() - $redirect_time > 10)) {
        $conexao->exec("DELETE FROM settings WHERE chave IN ('redirect_global','redirect_admin','redirect_time')");
    } else {
        // Exibe aviso antes de redirecionar para todos, inclusive admins
        echo '<div id="aviso-redirect" style="position:fixed;top:0;left:0;width:100%;background:#b77acc;color:#fff;padding:18px 0;text-align:center;font-size:1.2em;z-index:9999;">Você será redirecionado para <b>'.htmlspecialchars($redirect_url).'</b> em 3 segundos...</div>';
        echo '<script>setTimeout(function(){window.location.href="'.addslashes($redirect_url).'";},3000);</script>';
        exit;
    }
}

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

// Cadastro
if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    if ($username && $password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $conexao->prepare('INSERT INTO users (username, password) VALUES (?, ?)');
            $stmt->execute([$username, $hash]);
            $_SESSION['user'] = $username;
            redirect('index.php');
        } catch (PDOException $e) {
            $error = 'Usuário já existe!';
        }
    } else {
        $error = 'Preencha todos os campos!';
    }
}

// Login
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $stmt = $conexao->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && $user['is_banned']) {
        header('Location: banido.php');
        exit;
    }
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $username;
        redirect('index.php');
    } else {
        $error = 'Usuário ou senha inválidos!';
    }
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('index.php');
}

// Excluir conta
if (isset($_POST['delete_account']) && isset($_SESSION['user'])) {
    $username = $_SESSION['user'];
    $stmt = $conexao->prepare('DELETE FROM users WHERE username = ?');
    $stmt->execute([$username]);
    session_destroy();
    redirect('index.php');
}
