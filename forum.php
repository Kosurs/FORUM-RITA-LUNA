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

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$forum_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($forum_id <= 0) {
    echo '<p>Fórum não encontrado.</p>';
    exit;
}

// Busca informações do fórum
$stmt = $conexao->prepare('SELECT * FROM forums WHERE id = ?');
$stmt->execute([$forum_id]);
$forum = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$forum) {
    echo '<p>Fórum não encontrado.</p>';
    exit;
}

// Busca id do usuário logado e se é admin
$user_id = null;
$is_admin = false;
if (isset($_SESSION['user'])) {
    $stmtUser = $conexao->prepare('SELECT id, username, is_admin FROM users WHERE username = ?');
    $stmtUser->execute([$_SESSION['user']]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $user_id = $user['id'];
        if ($user['is_admin']) {
            $is_admin = true;
        }
    }
}

// Criação de post
if (isset($_POST['create_post']) && $user_id) {
    // Se fórum principal, só admin pode criar
    if ($forum['is_principal'] && !$is_admin) {
        $error = 'Apenas administradores podem criar posts neste fórum.';
    } else {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        if ($title && $content) {
            $stmtPost = $conexao->prepare('INSERT INTO posts (forum_id, user_id, title, content) VALUES (?, ?, ?, ?)');
            $stmtPost->execute([$forum_id, $user_id, $title, $content]);
            header('Location: forum.php?id=' . $forum_id . '&success=Post criado com sucesso!');
            exit;
        } else {
            $error = 'Preencha todos os campos!';
        }
    }
}

// Edição de post
if (isset($_POST['edit_post']) && $user_id) {
    $post_id = intval($_POST['post_id']);
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    // Admin pode editar qualquer post, usuário só o próprio
    if ($is_admin) {
        $stmt = $conexao->prepare('UPDATE posts SET title=?, content=?, updated_at=NOW() WHERE id=?');
        $stmt->execute([$title, $content, $post_id]);
    } else {
        $stmt = $conexao->prepare('UPDATE posts SET title=?, content=?, updated_at=NOW() WHERE id=? AND user_id=?');
        $stmt->execute([$title, $content, $post_id, $user_id]);
    }
    header('Location: forum.php?id=' . $forum_id . '&success=Post editado com sucesso!');
    exit;
}

// Exclusão de post
if (isset($_POST['delete_post']) && $user_id) {
    $post_id = intval($_POST['post_id']);
    // Admin pode excluir qualquer post, usuário só o próprio
    if ($is_admin) {
        $stmt = $conexao->prepare('DELETE FROM posts WHERE id=?');
        $stmt->execute([$post_id]);
    } else {
        $stmt = $conexao->prepare('DELETE FROM posts WHERE id=? AND user_id=?');
        $stmt->execute([$post_id, $user_id]);
    }
    header('Location: forum.php?id=' . $forum_id . '&success=Post excluído!');
    exit;
}

// Criação de comentário
if (isset($_POST['create_comment']) && $user_id) {
    $comment_content = trim($_POST['comment_content']);
    $post_id = intval($_POST['post_id']);
    if ($comment_content && $post_id) {
        $stmtComment = $conexao->prepare('INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)');
        $stmtComment->execute([$post_id, $user_id, $comment_content]);
        header('Location: forum.php?id=' . $forum_id . '&success=Comentário adicionado!');
        exit;
    } else {
        $error = 'Preencha o comentário!';
    }
}

// Edição de comentário
if (isset($_POST['edit_comment']) && $user_id) {
    $comment_id = intval($_POST['comment_id']);
    $content = trim($_POST['content']);
    $stmt = $conexao->prepare('UPDATE comments SET content=?, created_at=NOW() WHERE id=? AND user_id=?');
    $stmt->execute([$content, $comment_id, $user_id]);
    header('Location: forum.php?id=' . $forum_id . '&success=Comentário editado!');
    exit;
}

// Exclusão de comentário
if (isset($_POST['delete_comment']) && $user_id) {
    $comment_id = intval($_POST['comment_id']);
    $stmt = $conexao->prepare('DELETE FROM comments WHERE id=? AND user_id=?');
    $stmt->execute([$comment_id, $user_id]);
    header('Location: forum.php?id=' . $forum_id . '&success=Comentário excluído!');
    exit;
}

// Busca posts do fórum
$stmt = $conexao->prepare('SELECT p.*, u.username FROM posts p JOIN users u ON p.user_id = u.id WHERE p.forum_id = ? ORDER BY p.created_at DESC');
$stmt->execute([$forum_id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Busca comentários agrupados por post_id
$comments = [];
if ($posts) {
    $post_ids = array_column($posts, 'id');
    if ($post_ids) {
        $in = str_repeat('?,', count($post_ids) - 1) . '?';
        $stmt = $conexao->prepare("SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.post_id IN ($in) ORDER BY c.created_at ASC");
        $stmt->execute($post_ids);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $comment) {
            $comments[$comment['post_id']][] = $comment;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($forum['name']) ?> - Fórum</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <header>
        <nav>
            <div class="nav-links">
                <div class="nav-link"><a href="index.php">Início</a></div>
                <div class="nav-link"><a href="index.php">Fóruns</a></div>
                <?php if ($is_admin): ?>
                <div class="nav-link"><a href="admin.php">Painel Admin</a></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['user'])): ?>
                <div class="nav-link"><a href="?logout=1">Sair</a></div>
                <?php endif; ?>
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
            <div><img class="logo-site" src="https://i.ibb.co/SD2TKbT4/image-rita.png" alt="Logo"></div>
        </div>
    </header>
    <div class="caixa">
        <p class="titulo-pagina center"><?= htmlspecialchars($forum['name']) ?></p>
        <p class="descricao-pagina center"><?= htmlspecialchars($forum['description']) ?></p>
        <?php if (isset($_GET['success'])) echo '<div style="color:green">'.htmlspecialchars($_GET['success']).'</div>'; ?>
        <?php if (isset($error)) echo '<div style="color:red">'.htmlspecialchars($error).'</div>'; ?>
        <?php if ($user_id): ?>
        <?php if ($forum['is_principal'] && !$is_admin): ?>
            <div class="caixa-form center-column" style="background:#ffe0e0;color:#b32d1a;">Você não é um administrador :(</div>
        <?php else: ?>
        <div class="caixa-form center-column">
            <h1>Criar novo post</h1>
            <hr>
            <form class="formulario" method="post">
                <input class="formulario-nome" type="text" name="title" placeholder="Título do post. . ." required><br>
                <textarea class="formulario-descricao" name="content" placeholder="Conteúdo. . ." required></textarea><br>
                <button class="botao-verde" type="submit" name="create_post">Publicar</button>
            </form>
        </div>
        <?php endif; ?>
        <?php endif; ?>
        <?php if (count($posts) === 0): ?>
                    <p class="mensagem-erro">Nenhum Post Ainda</p>
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <div class="subforum">
                    <div class="sf-titulo center">
                        <h1><?= htmlspecialchars($post['title']) ?></h1>
                        <span class="separador"></span>
                        <span class="estatisticas"><?= htmlspecialchars($post['username']) ?> em <?= date('d/m/Y H:i', strtotime($post['created_at'])) ?><?php if ($post['updated_at'] && $post['updated_at'] != $post['created_at']) echo ' (editado)'; ?></span>
                        <?php if ($user_id && ($is_admin || $post['user_id'] == $user_id)): ?>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir este post?');">
                                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                <button type="submit" name="delete_post" class="botao-vermelho">Excluir</button>
                            </form>
                            <button onclick="toggleEditPost(<?= $post['id'] ?>)" class="botao-verde">Editar</button>
                        <?php endif; ?>
                    </div>
                    <div class="forum-conteudo">
                        <div class="sf-descricao">
                            <span><?= nl2br(htmlspecialchars($post['content'])) ?></span>
                        </div>
                        <?php if ($user_id && ($is_admin || $post['user_id'] == $user_id)): ?>
                        <form method="post" class="edit-post-form center-column" id="edit-post-<?= $post['id'] ?>" style="display:none;">
                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                            <input class="formulario-nome" type="text" name="title" value="<?= htmlspecialchars($post['title']) ?>" required><br>
                            <textarea class="formulario-descricao" name="content" required><?= htmlspecialchars($post['content']) ?></textarea><br>
                            <button class="botao-verde" type="submit" name="edit_post">Salvar</button>
                            <button type="button" onclick="toggleEditPost(<?= $post['id'] ?>)">Cancelar</button>
                        </form>
                        <?php endif; ?>
                        <div>
                            <h1 class="center">Comentários</h1>
                            <?php if (!empty($comments[$post['id']])): ?>
                                <?php foreach ($comments[$post['id']] as $comment): ?>
                                    <div class="comentario">
                                        <span><?= htmlspecialchars($comment['username']) ?></span>:
                                        <span><?= nl2br(htmlspecialchars($comment['content'])) ?></span>
                                        <br><br>
                                        <div class="center">
                                            <span><?= date('d/m/Y H:i', strtotime($comment['created_at'])) ?> | </span>
                                            <?php if ($user_id && $comment['user_id'] == $user_id): ?>
                                                <a href="forum.php?id=<?= $forum_id ?>&edit_comment=<?= $comment['id'] ?>">Editar</a>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($user_id && $comment['user_id'] == $user_id && isset($_GET['edit_comment']) && $_GET['edit_comment'] == $comment['id']): ?>
                                            <form method="post" class="center-column">
                                                <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                                <textarea class="forum-formulario-descricao" name="content" required><?= htmlspecialchars($comment['content']) ?></textarea>
                                                <button class="botao-verde" type="submit" name="edit_comment">Salvar</button>
                                                <a href="forum.php?id=<?= $forum_id ?>">Cancelar</a>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="comentario">Nenhum comentário, seja o primeiro!</div>
                            <?php endif; ?>
                            <?php if ($user_id): ?>
                            <form class="center-column" method="post">
                                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                <textarea class="forum-formulario-descricao" name="comment_content" placeholder="Comente aqui..." required></textarea>
                                <button class="botao-verde" type="submit" name="create_comment">Comentar</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <footer>
        <span>&copy; Saulo, Samuel Oliveira, Samuel Cavalcante | All Rights Reserved</span>
    </footer>
    <script>
    function toggleEditPost(id) {
        var form = document.getElementById('edit-post-' + id);
        if (form.style.display === 'none') {
            form.style.display = 'flex';
        } else {
            form.style.display = 'none';
        }
    }
    </script>
</body>
</html>
