<?php
session_start();
include 'db.php';

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

// Garante que os três subfóruns principais existam
$principais = [
    ['Anúncios', 'Comunicados e novidades da escola'],
    ['Provas', 'Discussão sobre provas, simulados e avaliações'],
    ['Perguntas', 'Tire dúvidas gerais sobre a escola']
];
foreach ($principais as $p) {
    $stmt = $conexao->prepare('SELECT id FROM forums WHERE name = ? AND is_principal = 1');
    $stmt->execute([$p[0]]);
    if (!$stmt->fetch()) {
        $stmtIns = $conexao->prepare('INSERT INTO forums (name, description, is_principal) VALUES (?, ?, 1)');
        $stmtIns->execute([$p[0], $p[1]]);
    }
}

// Criação de subfórum público
if (isset($_POST['create_forum']) && isset($_SESSION['user'])) {
    $name = trim($_POST['forum_name']);
    $description = trim($_POST['forum_description']);
    if ($name && $description) {
        // Impede nomes duplicados com principais
        $stmt = $conexao->prepare('SELECT id FROM forums WHERE name = ?');
        $stmt->execute([$name]);
        if ($stmt->fetch()) {
            $error = 'Já existe um fórum com esse nome!';
        } else {
            $stmt = $conexao->prepare('INSERT INTO forums (name, description, is_principal) VALUES (?, ?, 0)');
            $stmt->execute([$name, $description]);
            header('Location: forums.php');
            exit;
        }
    } else {
        $error = 'Preencha todos os campos!';
    }
}

// Busca principais e públicos separadamente
$stmt = $conexao->query('SELECT f.*, 
    (SELECT COUNT(*) FROM posts p WHERE p.forum_id = f.id) AS post_count,
    (SELECT COUNT(*) FROM comments c JOIN posts p ON c.post_id = p.id WHERE p.forum_id = f.id) AS comment_count
    FROM forums f WHERE is_principal = 1 ORDER BY name');
$principais_foruns = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conexao->query('SELECT f.*, 
    (SELECT COUNT(*) FROM posts p WHERE p.forum_id = f.id) AS post_count,
    (SELECT COUNT(*) FROM comments c JOIN posts p ON c.post_id = p.id WHERE p.forum_id = f.id) AS comment_count
    FROM forums f WHERE is_principal = 0 ORDER BY name');
$publicos_foruns = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Fóruns</title>
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
    <div class="caixa">
        <div style="display:flex;justify-content:flex-start;gap:18px;align-items:center;margin-bottom:2em;">
            <a href="index.php" title="Voltar para Início" style="display:inline-flex;align-items:center;gap:8px;padding:10px 22px;background:#327f32;color:#fff;font-weight:bold;border-radius:8px;font-size:1.1em;text-decoration:none;box-shadow:0 2px 8px rgba(50,127,50,0.10);transition:background 0.2s;">
                <img src="https://i.ibb.co/ZRKbDVzf/image-removebg-preview.png" alt="Voltar" style="height:1.5em;vertical-align:middle;">
                Voltar para Início
            </a>
        </div>
        <h1>Fóruns</h1>
        <?php if (isset($_SESSION['user'])): ?>
        <div class="auth-forms" style="max-width:400px;margin:20px auto;">
            <h3>Criar novo Fórum Público</h3>
            <?php if (isset($error)) echo '<div style="color:red">'.htmlspecialchars($error).'</div>'; ?>
            <form method="post">
                <input type="text" name="forum_name" placeholder="Nome do fórum público" required><br>
                <textarea name="forum_description" placeholder="Descrição" required style="width:100%;min-height:60px;"></textarea><br>
                <button type="submit" name="create_forum">Criar Fórum Público</button>
            </form>
        </div>
        <?php endif; ?>
        <h2 style="margin-top:2em;color:#327f32;">Fóruns Principais</h2>
        <?php if (count($principais_foruns) === 0): ?>
            <p>Nenhum Fórum Principal cadastrado.</p>
        <?php else: ?>
            <?php foreach ($principais_foruns as $forum): ?>
                <div class="subforum">
                    <div class="sf-titulo" style="display:flex;align-items:center;gap:10px;background:#b77acc;color:#fff;">
                        <img src="https://i.ibb.co/gFwDDny3/image-removebg-preview-1.png" alt="Foto do fórum" style="height:32px;width:32px;object-fit:contain;">
                        <a href="forum.php?id=<?= $forum['id'] ?>"><b><?= htmlspecialchars($forum['name']) ?></b></a>
                        <span style="flex:1 1 auto;"></span>
                        <span style="font-size:0.9em; color:#fff;">Posts: <?= $forum['post_count'] ?> | Comentários: <?= $forum['comment_count'] ?></span>
                    </div>
                    <div class="sf-descricao" style="padding:10px; background:#e6e1f7; color:#222; border-radius:0 0 10px 10px;">
                        <?= nl2br(htmlspecialchars($forum['description'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <h2 style="margin-top:2em;color:#327f32;">Fóruns Públicos</h2>
        <?php if (count($publicos_foruns) === 0): ?>
            <p>Nenhum Fórum Público cadastrado.</p>
        <?php else: ?>
            <?php foreach ($publicos_foruns as $forum): ?>
                <div class="subforum">
                    <div class="sf-titulo" style="display:flex;align-items:center;gap:10px;">
                        <img src="https://i.ibb.co/gFwDDny3/image-removebg-preview-1.png" alt="Foto do fórum" style="height:32px;width:32px;object-fit:contain;">
                        <a href="forum.php?id=<?= $forum['id'] ?>"><b><?= htmlspecialchars($forum['name']) ?></b></a>
                        <span style="flex:1 1 auto;"></span>
                        <span style="font-size:0.9em; color:#fff;">Posts: <?= $forum['post_count'] ?> | Comentários: <?= $forum['comment_count'] ?></span>
                    </div>
                    <div class="sf-descricao" style="padding:10px; background:#bbc75f; color:#222; border-radius:0 0 10px 10px;">
                        <?= nl2br(htmlspecialchars($forum['description'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <div style="text-align:center; margin-top:2em;">
            <a href="index.php" title="Voltar para Início" style="display:inline-flex;align-items:center;gap:8px;padding:10px 22px;background:#327f32;color:#fff;font-weight:bold;border-radius:8px;font-size:1.1em;text-decoration:none;box-shadow:0 2px 8px rgba(50,127,50,0.10);transition:background 0.2s;">
                <img src="https://i.ibb.co/ZRKbDVzf/image-removebg-preview.png" alt="Voltar" style="height:1.5em;vertical-align:middle;">
                Voltar para Início
            </a>
        </div>
    </div>
    <?php
    // Exibe aviso global se existir
    if (isset($_SESSION['aviso_global']) && $_SESSION['aviso_global']) {
        echo '<div id="aviso-global" style="position:fixed;top:0;left:0;width:100%;background:#b77acc;color:#fff;padding:18px 0;text-align:center;font-size:1.2em;z-index:9999;">'.htmlspecialchars($_SESSION['aviso_global']).'</div>';
        echo '<script>setTimeout(function(){var aviso=document.getElementById(\'aviso-global\');if(aviso)aviso.style.display=\'none\';},10000);</script>';
        unset($_SESSION['aviso_global']);
    }
    ?>
</body>
</html>
