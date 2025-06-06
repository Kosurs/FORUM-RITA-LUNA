<?php
session_start();
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}
require 'db.php';
// Define $is_admin antes de qualquer uso
$is_admin = false;
if (isset($_SESSION['user'])) {
    $stmt = $conexao->prepare('SELECT is_admin FROM users WHERE username = ?');
    $stmt->execute([$_SESSION['user']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && $row['is_admin']) $is_admin = true;
}
// Exclusão de fórum (apenas admin e não principal)
if (isset($_POST['delete_forum']) && $is_admin) {
    $forum_id = intval($_POST['forum_id']);
    // Verifica se o fórum é principal
    $stmt = $conexao->prepare('SELECT is_principal FROM forums WHERE id = ?');
    $stmt->execute([$forum_id]);
    $forum = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($forum && !$forum['is_principal']) {
        $stmt = $conexao->prepare('DELETE FROM forums WHERE id = ?');
        $stmt->execute([$forum_id]);
        header('Location: index.php?success=Fórum excluído!');
        exit;
    }
}
// Edição de fórum (apenas admin e não principal)
if (isset($_POST['edit_forum']) && $is_admin) {
    $forum_id = intval($_POST['forum_id']);
    $name = trim($_POST['forum_name']);
    $description = trim($_POST['forum_description']);
    // Verifica se o fórum é principal
    $stmt = $conexao->prepare('SELECT is_principal FROM forums WHERE id = ?');
    $stmt->execute([$forum_id]);
    $forum = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($forum && !$forum['is_principal']) {
        if ($name && $description) {
            $stmt = $conexao->prepare('UPDATE forums SET name=?, description=? WHERE id=?');
            $stmt->execute([$name, $description, $forum_id]);
            header('Location: index.php?success=Fórum editado!');
            exit;
        } else {
            $error = 'Preencha todos os campos!';
        }
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
    if ($redirect_time && (time() - $redirect_time > 10)) {
        $conexao->exec("DELETE FROM settings WHERE chave IN ('redirect_global','redirect_admin','redirect_time')");
    } else {
        echo '<div id="aviso-redirect" style="position:fixed;top:0;left:0;width:100%;background:#b77acc;color:#fff;padding:18px 0;text-align:center;font-size:1.2em;z-index:9999;">Você será redirecionado para <b>'.htmlspecialchars($redirect_url).'</b> em 3 segundos...</div>';
        echo '<script>setTimeout(function(){window.location.href="'.addslashes($redirect_url).'";},3000);</script>';
        exit;
    }
}
// Criação de subfórum público
if (isset($_POST['forum_name'], $_POST['forum_description']) && isset($_SESSION['user'])) {
    $name = trim($_POST['forum_name']);
    $description = trim($_POST['forum_description']);
    if ($name && $description) {
        $stmt = $conexao->prepare('SELECT id FROM forums WHERE name = ?');
        $stmt->execute([$name]);
        if ($stmt->fetch()) {
            $error = 'Já existe um fórum com esse nome!';
        } else {
            $stmt = $conexao->prepare('INSERT INTO forums (name, description, is_principal) VALUES (?, ?, 0)');
            $stmt->execute([$name, $description]);
            header('Location: index.php');
            exit;
        }
    } else {
        $error = 'Preencha todos os campos!';
    }
}
// Busca principais e públicos separadamente
$stmt = $conexao->query('SELECT f.*, (SELECT COUNT(*) FROM posts p WHERE p.forum_id = f.id) AS post_count, (SELECT COUNT(*) FROM comments c JOIN posts p ON c.post_id = p.id WHERE p.forum_id = f.id) AS comment_count FROM forums f WHERE is_principal = 1 ORDER BY name');
$principais_foruns = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt = $conexao->query('SELECT f.*, (SELECT COUNT(*) FROM posts p WHERE p.forum_id = f.id) AS post_count, (SELECT COUNT(*) FROM comments c JOIN posts p ON c.post_id = p.id WHERE p.forum_id = f.id) AS comment_count FROM forums f WHERE is_principal = 0 ORDER BY name');
$publicos_foruns = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fóruns</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <header> 
        <nav>                    
            <div class="nav-links">
                <div class="nav-link"><a href="index.php">Início</a></div>
                <div class="nav-link"><a href="index.php#lista-foruns">Fóruns</a></div>
                <?php if ($is_admin): ?><div class="nav-link"><a href="admin.php">Painel Admin</a></div><?php endif; ?>
                <?php if (isset($_SESSION['user'])): ?><div class="nav-link"><a href="?logout=1">Sair</a></div><?php endif; ?>
            </div>
        </nav>
        <div class="nav-user">
            <?php if (isset($_SESSION['user'])): ?>
                <h1><?= htmlspecialchars($_SESSION['user']) ?></h1>
            <?php else: ?>
                <div class="nav-link"><a href="register.php">Cadastrar-se</a></div>
                <div class="nav-link"><a href="login.php">Entrar</a></div>
            <?php endif; ?>
        </div>
        <div class="center">
            <div><img class="logo-site" src="https://i.ibb.co/d0F66Kw6/Whats-App-Image-2025-05-29-at-18-37-15-removebg-preview.png" alt="Logo"></div>
            <div class="titulo-site">Fórum Rita Matos Luna</div>
            <div><img class="logo-site" src="https://i.ibb.co/d0F66Kw6/Whats-App-Image-2025-05-29-at-18-37-15-removebg-preview.png" alt="Logo"></div>
        </div> 
    </header>
    <div class="caixa" id="lista-foruns">
        <p class="titulo-pagina center">Fóruns</p>
        <?php if (isset($_SESSION['user'])): ?>
        <div class="caixa-form foruns-form">
            <h1>Criar um novo Fórum</h1>
            <hr>
            <?php if (isset($error)) echo '<div class="mensagem-erro">'.htmlspecialchars($error).'</div>'; ?>
            <form class="formulario" method="post">
                <input class="formulario-nome" type="text" name="forum_name" placeholder="Nome do subfórum . . ." required><br>
                <textarea class="formulario-descricao" name="forum_description" placeholder="Descrição. . ." required></textarea><br>
                <button class="botao-verde" type="submit">Criar Subfórum</button>
            </form>
        </div>
        <?php endif; ?>
        <h2 style="margin-top:2em;color:#327f32;">Fóruns Principais</h2>
        <?php if (count($principais_foruns) === 0): ?>
            <p>Nenhum Fórum Principal cadastrado.</p>
        <?php else: ?>
            <?php foreach ($principais_foruns as $forum): ?>
                <div class="subforum">
                    <div class="sf-titulo center">
                        <a href="forum.php?id=<?= $forum['id'] ?>"><h1><?= htmlspecialchars($forum['name']) ?></h1></a>
                        <span class="separador"></span>
                        <span class="sf-estatisticas">Posts: <?= $forum['post_count'] ?> | Comentários: <?= $forum['comment_count'] ?></span>
                        <!-- Nenhum botão de editar/excluir para fóruns principais -->
                    </div>
                    <div class="sf-descricao">
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
                    <div class="sf-titulo center">
                        <a href="forum.php?id=<?= $forum['id'] ?>"><h1><?= htmlspecialchars($forum['name']) ?></h1></a>
                        <span class="separador"></span>
                        <span class="sf-estatisticas">Posts: <?= $forum['post_count'] ?> | Comentários: <?= $forum['comment_count'] ?></span>
                        <?php if ($is_admin): ?>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir este fórum?');">
                                <input type="hidden" name="forum_id" value="<?= $forum['id'] ?>">
                                <button type="submit" name="delete_forum" class="botao-vermelho">Excluir</button>
                            </form>
                            <button onclick="toggleEditForumPub(<?= $forum['id'] ?>)" class="botao-verde">Editar</button>
                        <?php endif; ?>
                    </div>
                    <div class="sf-descricao">
                        <?= nl2br(htmlspecialchars($forum['description'])) ?>
                    </div>
                    <?php if ($is_admin): ?>
                    <form method="post" class="edit-forum-form center-column" id="edit-forum-pub-<?= $forum['id'] ?>" style="display:none;">
                        <input type="hidden" name="forum_id" value="<?= $forum['id'] ?>">
                        <input class="formulario-nome" type="text" name="forum_name" value="<?= htmlspecialchars($forum['name']) ?>" required><br>
                        <textarea class="formulario-descricao" name="forum_description" required><?= htmlspecialchars($forum['description']) ?></textarea><br>
                        <button class="botao-verde" type="submit" name="edit_forum">Salvar</button>
                        <button type="button" onclick="toggleEditForumPub(<?= $forum['id'] ?>)">Cancelar</button>
                    </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <footer>
        <span>&copy; Saulo, Samuel Oliveira, Samuel Cavalcante | All Rights Reserved</span>
    </footer>
</body>
<script>
// Rolagem suave para o id se houver hash na URL
if (window.location.hash === '#lista-foruns') {
    document.addEventListener('DOMContentLoaded', function() {
        var el = document.getElementById('lista-foruns');
        if (el) el.scrollIntoView({behavior: 'smooth'});
    });
}
function toggleEditForum(id) {
    var form = document.getElementById('edit-forum-' + id);
    if (form.style.display === 'none') {
        form.style.display = 'flex';
    } else {
        form.style.display = 'none';
    }
}
function toggleEditForumPub(id) {
    var form = document.getElementById('edit-forum-pub-' + id);
    if (form.style.display === 'none') {
        form.style.display = 'flex';
    } else {
        form.style.display = 'none';
    }
}
</script>
</html>
