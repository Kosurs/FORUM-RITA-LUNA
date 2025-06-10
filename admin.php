<?php
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
include 'auth.php';
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
// Checa se usuário é admin
$is_admin = false;
$stmt = $conexao->prepare('SELECT is_admin FROM users WHERE username = ?');
$stmt->execute([$_SESSION['user']]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row && $row['is_admin']) {
    $is_admin = true;
}
if (!$is_admin) {
    header('Location: index.php');
    exit;
}
// Banir/desbanir usuário
if (isset($_POST['banir_user']) && isset($_POST['ban_user_id'])) {
    $stmt = $conexao->prepare('UPDATE users SET is_banned=1 WHERE id=?');
    $stmt->execute([intval($_POST['ban_user_id'])]);
}
if (isset($_POST['desbanir_user']) && isset($_POST['ban_user_id'])) {
    $stmt = $conexao->prepare('UPDATE users SET is_banned=0 WHERE id=?');
    $stmt->execute([intval($_POST['ban_user_id'])]);
}
// Banir todos
if (isset($_POST['banir_todos'])) {
    $conexao->query('UPDATE users SET is_banned=1');
}
// Redirecionamento global usando SQL
if (isset($_POST['set_redirect']) && isset($_POST['redirect_url'])) {
    $redirect_url = trim($_POST['redirect_url']);
    $redirect_admin = $_SESSION['user'];
    $redirect_time = time();
    $conexao->exec("CREATE TABLE IF NOT EXISTS settings (chave VARCHAR(100) PRIMARY KEY, valor TEXT)");
    $stmt = $conexao->prepare("REPLACE INTO settings (chave, valor) VALUES ('redirect_global', ?)");
    $stmt->execute([$redirect_url]);
    $stmt = $conexao->prepare("REPLACE INTO settings (chave, valor) VALUES ('redirect_admin', ?)");
    $stmt->execute([$redirect_admin]);
    $stmt = $conexao->prepare("REPLACE INTO settings (chave, valor) VALUES ('redirect_time', ?)");
    $stmt->execute([$redirect_time]);
    header('Location: admin.php');
    exit;
}
if (isset($_POST['remove_redirect'])) {
    $conexao->exec("DELETE FROM settings WHERE chave IN ('redirect_global','redirect_admin','redirect_time')");
    header('Location: admin.php');
    exit;
}
$users = $conexao->query('SELECT id, username, is_admin, is_banned FROM users')->fetchAll(PDO::FETCH_ASSOC);
// Carrega redirect do banco para exibir no painel
$redirect_global = '';
$conexao->exec("CREATE TABLE IF NOT EXISTS settings (chave VARCHAR(100) PRIMARY KEY, valor TEXT)");
$settings = $conexao->query("SELECT chave, valor FROM settings WHERE chave IN ('redirect_global')")->fetchAll(PDO::FETCH_KEY_PAIR);
if (isset($settings['redirect_global'])) $redirect_global = $settings['redirect_global'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel dos Administradores</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <header>
        <nav>
            <div class="nav-links">
                <div class="nav-link"><a href="index.php">Início</a></div>
                <div class="nav-link"><a href="index.php">Fóruns</a></div>
                <div class="nav-link"><a href="admin.php">Painel Admin</a></div>
                <div class="nav-link"><a href="?logout=1">Sair</a></div>
            </div>
        </nav>
        <div class="nav-user">
            <h1><?= htmlspecialchars($_SESSION['user']) ?></h1>
        </div>
        <div class="center">
            <div><img class="logo-site" src="https://i.ibb.co/d0F66Kw6/Whats-App-Image-2025-05-29-at-18-37-15-removebg-preview.png" alt="Logo"></div>
            <div class="titulo-site">Fórum Rita Matos Luna</div>
            <div><img class="logo-site" src="https://i.ibb.co/SD2TKbT4/image-rita.png" alt="Logo"></div>
        </div>
    </header>
    <div class="caixa">
        <p class="titulo-pagina center">Painel do Administrador</p>
        <div class="caixa-form center-column">
            <h1>Painel do Administrador</h1>
            <hr>
            <form class="admin-redirecionar" method="post">
                <input class="admin-redirecionar-texto" type="url" id="redirect_url" name="redirect_url" maxlength="300" placeholder="Redirecionar todos os usuários para um link. . ." required>
                <?php if ($redirect_global): ?>
                    <i>Redirecionamento ativo para: <b><?= htmlspecialchars($redirect_global) ?></b> </i>
                <?php endif; ?>
                <div class="admin-redirecionar-botoes">
                    <button class="botao-verde" type="submit" name="set_redirect">Ativar Redirecionamento</button>
                    <?php if ($redirect_global): ?>
                        <button class="botao-vermelho" type="submit" name="remove_redirect">Remover Redirecionamento</button>
                    <?php endif; ?>
                </div>
            </form>
            <h1>Banir/Desbanir Usuários</h1>
            <table class="admin-tabela">
                <tr>
                    <th>Usuário</th>
                    <th>Admin</th>
                    <th>Status</th>
                    <th>Ação</th>
                </tr>
                <?php foreach($users as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['username']) ?></td>
                    <td><?= $u['is_admin'] ? 'Sim' : 'Não' ?></td>
                    <td><?= $u['is_banned'] ? '<span class="user-banido">Banido</span>' : '<span class="user-ativo">Ativo</span>' ?></td>
                    <td>
                        <?php if (!$u['is_banned']): ?>
                            <form method="post" style="display:inline;"><input type="hidden" name="ban_user_id" value="<?= $u['id'] ?>"><button type="submit" name="banir_user" class="botao-vermelho tabela-bvm">Banir</button></form>
                        <?php else: ?>
                            <form method="post" style="display:inline;"><input type="hidden" name="ban_user_id" value="<?= $u['id'] ?>"><button type="submit" name="desbanir_user" class="botao-verde tabela-bvd">Desbanir</button></form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <form method="post">
                <button type="submit" name="banir_todos" class="botao-vermelho">Banir Todos os Usuários</button>
            </form>
        </div>
    </div>
    <footer>
        <span>&copy; Saulo, Samuel Oliveira, Samuel Cavalcante | All Rights Reserved</span>
    </footer>
</body>
</html>
