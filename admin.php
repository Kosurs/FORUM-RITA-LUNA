<?php
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
// Envia aviso global
if (isset($_POST['enviar_aviso']) && isset($_POST['aviso_texto'])) {
    $_SESSION['aviso_global'] = trim($_POST['aviso_texto']);
    header('Location: admin.php');
    exit;
}
// Redirecionamento global usando SQL
if (isset($_POST['set_redirect']) && isset($_POST['redirect_url'])) {
    $redirect_url = trim($_POST['redirect_url']);
    $redirect_admin = $_SESSION['user'];
    $redirect_time = time();
    // Cria tabela settings se não existir
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
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel do Administrador, teste</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <header>
        <div style="background:#fff;padding:1.2em 0 0.3em 0;text-align:center;">
            <img src="https://i.ibb.co/d0F66Kw6/Whats-App-Image-2025-05-29-at-18-37-15-removebg-preview.png" alt="Logo" class="logo-img" style="height:48px;">
            <div class="brand" style="color:#b77acc;font-size:1.3em;margin-top:0.2em;">Fórum Rita Matos Luna</div>
            <?php if (!isset($_SESSION['user'])): ?>
            <div style="margin-top:0.3em;">
                <a href="register.php" style="display:inline-block;background:#327f32;color:#fff;font-weight:bold;margin:0 8px;padding:8px 22px;border-radius:6px;font-size:0.98em;box-shadow:0 2px 8px rgba(50,127,50,0.10);transition:background 0.2s;">Criar Conta</a>
                <a href="login.php" style="display:inline-block;background:#b77acc;color:#fff;font-weight:bold;margin:0 8px;padding:8px 22px;border-radius:6px;font-size:0.98em;box-shadow:0 2px 8px rgba(183,122,204,0.10);transition:background 0.2s;">Login</a>
            </div>
            <?php endif; ?>
        </div>
        <nav class="navbar" style="background:#fff;display:flex;align-items:center;justify-content:center;padding:0;margin:0;border-bottom:1px solid #eee;">
            <ul class="nav-list" style="display:flex;align-items:center;gap:2em;margin:0;padding:0;list-style:none;">
                <li class="nav-item"><a href="index.php" style="color:#222;font-weight:bold;letter-spacing:1px;padding:14px 0;display:inline-block;font-size:0.98em;">Início</a></li>
                <li class="nav-item"><a href="forums.php" style="color:#222;font-weight:bold;letter-spacing:1px;padding:14px 0;display:inline-block;font-size:0.98em;">Fóruns</a></li>
                <?php if (isset($_SESSION['user'])): ?>
                    <?php
                    $is_admin = false;
                    if (isset($_SESSION['user'])) {
                        include_once 'db.php';
                        $stmt = $conexao->prepare('SELECT is_admin FROM users WHERE username = ?');
                        $stmt->execute([$_SESSION['user']]);
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($row && $row['is_admin']) $is_admin = true;
                    }
                    ?>
                    <li class="nav-item" style="color:#327f32;font-weight:bold;">
                        <span style="background:#f7f7f7;padding:7px 18px;border-radius:6px;">👤 <?= htmlspecialchars($_SESSION['user']) ?></span>
                    </li>
                    <?php if ($is_admin): ?>
                        <li class="nav-item"><a href="admin.php" style="color:#222;font-weight:bold;letter-spacing:1px;padding:14px 0;display:inline-block;font-size:0.98em;">Painel Admin</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a href="?logout=1" style="color:#b77acc;font-weight:bold;letter-spacing:1px;padding:14px 0;display:inline-block;font-size:0.98em;">Sair</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <div class="caixa" style="background:#e6e1f7; border:2px solid #b77acc; margin:2em auto; max-width:900px;">
        <h2 style="color:#b77acc;">Painel do Administrador</h2>
        <form method="post" style="margin-bottom:1em;">
            <button type="submit" name="banir_todos" style="background:#e04a3f;color:#fff;border:none;padding:8px 18px;border-radius:5px;font-weight:bold;">Banir TODOS os usuários</button>
        </form>
        <form method="post" style="margin-bottom:2em;">
            <label for="aviso_texto"><b>Enviar aviso para todos os usuários:</b></label><br>
            <input type="text" name="aviso_texto" id="aviso_texto" maxlength="200" style="width:80%;padding:8px;" required>
            <button type="submit" name="enviar_aviso" style="padding:8px 18px;background:#327f32;color:#fff;border:none;border-radius:5px;font-weight:bold;">Enviar Aviso</button>
        </form>
        <form method="post" style="margin-bottom:2em;">
            <label for="redirect_url"><b>Redirecionar todos os usuários para um link:</b></label><br>
            <input type="url" name="redirect_url" id="redirect_url" maxlength="300" style="width:80%;padding:8px;" placeholder="https://exemplo.com" required>
            <button type="submit" name="set_redirect" style="padding:8px 18px;background:#b77acc;color:#fff;border:none;border-radius:5px;font-weight:bold;">Ativar Redirecionamento</button>
            <?php if (isset($_SESSION['redirect_global'])): ?>
                <button type="submit" name="remove_redirect" style="padding:8px 18px;background:#e04a3f;color:#fff;border:none;border-radius:5px;font-weight:bold;">Remover Redirecionamento</button>
                <div style="margin-top:8px;color:#b77acc;">Redirecionamento ativo para: <b><?= htmlspecialchars($_SESSION['redirect_global']) ?></b></div>
            <?php endif; ?>
        </form>
        <h3 style="color:#b77acc;">Banir/Desbanir Usuários</h3>
        <table style="width:100%;background:#fff;color:#222;border-radius:8px;overflow:hidden;">
            <tr><th>Usuário</th><th>Admin</th><th>Status</th><th>Ação</th></tr>
            <?php foreach($users as $u): ?>
            <tr>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= $u['is_admin'] ? 'Sim' : 'Não' ?></td>
                <td><?= $u['is_banned'] ? '<span style="color:#e04a3f;">Banido</span>' : '<span style="color:#327f32;">Ativo</span>' ?></td>
                <td>
                    <?php if (!$u['is_banned']): ?>
                        <form method="post" style="display:inline;"><input type="hidden" name="ban_user_id" value="<?= $u['id'] ?>"><button type="submit" name="banir_user" style="background:#e04a3f;color:#fff;border:none;padding:6px 14px;border-radius:5px;">Banir</button></form>
                    <?php else: ?>
                        <form method="post" style="display:inline;"><input type="hidden" name="ban_user_id" value="<?= $u['id'] ?>"><button type="submit" name="desbanir_user" style="background:#327f32;color:#fff;border:none;padding:6px 14px;border-radius:5px;">Desbanir</button></form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <div style="text-align:center;margin-top:2em;">
            <a href="index.php" style="color:#327f32;font-weight:bold;">Voltar para Início</a>
        </div>
    </div>
</body>
</html>
