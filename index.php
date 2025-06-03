<?php
include 'auth.php';
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
        echo '<div id="aviso-redirect" style="position:fixed;top:0;left:0;width:100%;background:#b77acc;color:#fff;padding:18px 0;text-align:center;font-size:1.2em;z-index:9999;">Você será redirecionado para <b>'.htmlspecialchars($redirect_url).'</b> em 3 segundos...</div>';
        echo '<script>setTimeout(function(){window.location.href="'.addslashes($redirect_url).'";},3000);</script>';
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rita Luna</title>
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
    <div class="caixa" style="max-width:900px;margin:32px auto 0 auto;padding:32px 18px 28px 18px;">
        <h1 style="color:#327f32;text-align:center;margin-bottom:0.5em;font-size:2.1em;">Bem-vindo ao Fórum Rita Matos Luna!</h1>
        <p style="text-align:center;font-size:1.15em;color:#555;margin-bottom:2.2em;">Participe das discussões, compartilhe ideias e fique por dentro das novidades da nossa escola.</p>
        <h2 style="color:#b77acc;text-align:left;margin-bottom:1.2em;font-size:1.3em;">Posts mais recentes</h2>
        <?php
        // Buscar os 5 posts mais recentes com info de usuário e fórum
        $stmt = $conexao->query("SELECT p.*, u.username, f.name as forum_name FROM posts p JOIN users u ON p.user_id = u.id JOIN forums f ON p.forum_id = f.id ORDER BY p.created_at DESC LIMIT 5");
        $recent_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($recent_posts) === 0): ?>
            <div style="color:#888;font-size:1.1em;margin:2em 0;text-align:center;">Nenhum post publicado ainda.</div>
        <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:22px;margin-top:0.5em;">
            <?php foreach ($recent_posts as $post): ?>
                <div class="subforum" style="background:#f7f7f7;box-shadow:0 1px 4px rgba(50,127,50,0.05);border-radius:10px;">
                    <div class="sf-titulo" style="display:flex;align-items:center;gap:10px;background:#327f32;color:#f1ee15;padding:10px 20px;border-radius:10px 10px 0 0;font-size:1.1em;">
                        <img src="https://i.ibb.co/gFwDDny3/image-removebg-preview-1.png" alt="Foto do fórum" style="height:32px;width:32px;object-fit:contain;">
                        <b><?= htmlspecialchars($post['title']) ?></b>
                        <span style="flex:1 1 auto;"></span>
                        <span style="font-size:0.9em; color:#fff;">por <?= htmlspecialchars($post['username']) ?> em <?= date('d/m/Y H:i', strtotime($post['created_at'])) ?></span>
                    </div>
                    <div class="sf-descricao" style="padding:15px 20px;background:#e6e1f7;color:#222;border-radius:0 0 10px 10px;">
                        <?= nl2br(htmlspecialchars(mb_strimwidth($post['content'], 0, 350, '...'))) ?>
                        <div style="margin-top:1em;font-size:0.98em;color:#888;">
                            Fórum: <a href="forum.php?id=<?= $post['forum_id'] ?>" style="color:#327f32;font-weight:bold;">#<?= htmlspecialchars($post['forum_name']) ?></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div style="text-align:center;margin-top:2.5em;">
            <a href="forums.php" class="a2" style="font-size:1.2em;">Ver todos os Fóruns</a>
        </div>
    </div>
    <footer style="position:fixed;left:0;bottom:0;width:100%;z-index:100;background:#327f32;color:#fff;text-align:center;padding:16px 0;border-radius:0 0 10px 10px;box-shadow:0 -2px 8px rgba(50,127,50,0.08);font-size:1em;">
        <span>&copy; Saulo, Samuel Oliveira, Samuel Cavalcante | All Rights Reserved</span>
    </footer>
</body>
</html>
