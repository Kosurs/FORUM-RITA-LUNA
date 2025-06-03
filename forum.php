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

// AJAX para criar comentário sem recarregar
if (isset($_POST['ajax_create_comment']) && $user_id) {
    $comment_content = trim($_POST['comment_content']);
    $post_id = intval($_POST['post_id']);
    if ($comment_content && $post_id) {
        $stmtComment = $conexao->prepare('INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)');
        $stmtComment->execute([$post_id, $user_id, $comment_content]);
        $comment_id = $conexao->lastInsertId();
        $stmt = $conexao->prepare('SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.id = ?');
        $stmt->execute([$comment_id]);
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);
        header('Content-Type: text/html; charset=utf-8');
        // Renderiza o HTML do novo comentário
        echo '<div style="margin:8px 0; padding:8px; background:#eee; border-radius:5px; color:#222;">';
        echo '<span style="font-weight:bold; color:#327f32;">'.htmlspecialchars($comment['username']).'</span>: ';
        echo nl2br(htmlspecialchars($comment['content']));
        echo '<span style="float:right; font-size:0.85em; color:#888;">em '.date('d/m/Y H:i', strtotime($comment['created_at'])).'</span>';
        echo '<div style="clear:both;"></div></div>';
        exit;
    }
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
    <title><?= htmlspecialchars($forum['name']) ?> - Fórum</title>
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
    <div class="caixa">
        <div style="display:flex;justify-content:flex-start;gap:18px;align-items:center;margin-bottom:2em;">
            <a href="forums.php" title="Voltar para Fóruns" style="display:inline-flex;align-items:center;gap:8px;padding:10px 22px;background:#327f32;color:#fff;font-weight:bold;border-radius:8px;font-size:1.1em;text-decoration:none;box-shadow:0 2px 8px rgba(50,127,50,0.10);transition:background 0.2s;">
                <img src="https://i.ibb.co/ZRKbDVzf/image-removebg-preview.png" alt="Voltar" style="height:1.5em;vertical-align:middle;">
                Voltar para Fóruns
            </a>
        </div>
        <h1><?= htmlspecialchars($forum['name']) ?></h1>
        <p><?= htmlspecialchars($forum['description']) ?></p>
        <hr>
        <?php if (isset($_GET['success'])) echo '<div style="color:green">'.htmlspecialchars($_GET['success']).'</div>'; ?>
        <?php if (isset($error)) echo '<div style="color:red">'.htmlspecialchars($error).'</div>'; ?>
        <?php if ($user_id): ?>
        <?php if ($forum['is_principal'] && !$is_admin): ?>
            <div class="auth-forms" style="background:#ffe0e0;color:#b32d1a;">Você não é um administrador :(</div>
        <?php else: ?>
        <div class="auth-forms">
            <h3>Criar novo post</h3>
            <form method="post">
                <input type="text" name="title" placeholder="Título do post" required><br>
                <textarea name="content" placeholder="Conteúdo" required style="width:100%;min-height:80px;"></textarea><br>
                <button type="submit" name="create_post">Publicar</button>
            </form>
        </div>
        <?php endif; ?>
        <?php else: ?>
        <p><a href="index.php">Faça login para criar um post</a></p>
        <?php endif; ?>
        <hr>
        <h2>Posts</h2>
        <?php if (count($posts) === 0): ?>
            <p>Nenhum post ainda.</p>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <div class="subforum" style="margin-bottom:20px;">
                    <div class="sf-titulo" style="display:flex;align-items:center;gap:10px;">
                        <img src="https://i.ibb.co/gFwDDny3/image-removebg-preview-1.png" alt="Foto do post" style="height:32px;width:32px;object-fit:contain;">
                        <b><?= htmlspecialchars($post['title']) ?></b>
                        <span style="flex:1 1 auto;"></span>
                        <span style="font-size:0.9em;">por <?= htmlspecialchars($post['username']) ?> em <?= date('d/m/Y H:i', strtotime($post['created_at'])) ?><?php if ($post['updated_at'] && $post['updated_at'] != $post['created_at']) echo ' (editado)'; ?></span>
                    </div>
                    <div class="sf-descricao" style="padding:15px; background:#bbc75f; color:#222; border-radius:0 0 10px 10px;">
                        <?php if ($user_id && $post['user_id'] == $user_id && isset($_GET['edit_post']) && $_GET['edit_post'] == $post['id']): ?>
                            <form method="post">
                                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                <input type="text" name="title" value="<?= htmlspecialchars($post['title']) ?>" required><br>
                                <textarea name="content" required style="width:100%;min-height:80px;"><?= htmlspecialchars($post['content']) ?></textarea><br>
                                <button type="submit" name="edit_post">Salvar</button>
                                <a href="forum.php?id=<?= $forum_id ?>">Cancelar</a>
                            </form>
                        <?php else: ?>
                            <?= nl2br(htmlspecialchars($post['content'])) ?>
                            <?php if ($user_id && ($post['user_id'] == $user_id || $is_admin)): ?>
                                <form method="post" style="display:inline; float:right; margin-left:10px;">
                                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                    <button type="submit" name="delete_post" onclick="return confirm('Excluir este post?');">Excluir</button>
                                </form>
                                <a href="forum.php?id=<?= $forum_id ?>&edit_post=<?= $post['id'] ?>" style="float:right;">Editar</a>
                            <?php endif; ?>
                        <?php endif; ?>
                        <div style="clear:both;"></div>
                    </div>
                    <div style="padding:10px 20px; background:#f9f9f9; border-radius:0 0 10px 10px;">
                        <b>Comentários:</b>
                        <?php if (!empty($comments[$post['id']])): ?>
                            <?php foreach ($comments[$post['id']] as $comment): ?>
                                <div style="margin:8px 0; padding:8px; background:#eee; border-radius:5px; color:#222;">
                                    <?php if ($user_id && $comment['user_id'] == $user_id && isset($_GET['edit_comment']) && $_GET['edit_comment'] == $comment['id']): ?>
                                        <form method="post">
                                            <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                            <textarea name="content" required style="width:100%;min-height:40px;"><?= htmlspecialchars($comment['content']) ?></textarea><br>
                                            <button type="submit" name="edit_comment">Salvar</button>
                                            <a href="forum.php?id=<?= $forum_id ?>">Cancelar</a>
                                        </form>
                                    <?php else: ?>
                                        <span style="font-weight:bold; color:#327f32;"><?= htmlspecialchars($comment['username']) ?></span>:
                                        <?= nl2br(htmlspecialchars($comment['content'])) ?>
                                        <span style="float:right; font-size:0.85em; color:#888;">em <?= date('d/m/Y H:i', strtotime($comment['created_at'])) ?></span>
                                        <?php if ($user_id && $comment['user_id'] == $user_id): ?>
                                            <form method="post" style="display:inline; float:right; margin-left:10px;">
                                                <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                                <button type="submit" name="delete_comment" onclick="return confirm('Excluir este comentário?');">Excluir</button>
                                            </form>
                                            <a href="forum.php?id=<?= $forum_id ?>&edit_comment=<?= $comment['id'] ?>" style="float:right;">Editar</a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <div style="clear:both;"></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="color:#888;">Nenhum comentário, seja o primeiro!</div>
                        <?php endif; ?>
                        <?php if ($user_id): ?>
                        <form method="post" class="ajax-comment-form" data-post-id="<?= $post['id'] ?>" id="form-comentario-<?= $post['id'] ?>" style="margin-top:10px;">
                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                            <textarea name="comment_content" placeholder="Comente aqui..." required style="width:100%;min-height:40px;"></textarea><br>
                            <button type="submit" name="create_comment">Comentar</button>
                        </form>
                        <div id="comentarios-<?= $post['id'] ?>">
                            <?php if (!empty($comments[$post['id']])): ?>
                                <?php foreach ($comments[$post['id']] as $comment): ?>
                                    <div style="margin:8px 0; padding:8px; background:#eee; border-radius:5px; color:#222;">
                                        <?php if ($user_id && $comment['user_id'] == $user_id && isset($_GET['edit_comment']) && $_GET['edit_comment'] == $comment['id']): ?>
                                            <form method="post">
                                                <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                                <textarea name="content" required style="width:100%;min-height:40px;"><?= htmlspecialchars($comment['content']) ?></textarea><br>
                                                <button type="submit" name="edit_comment">Salvar</button>
                                                <a href="forum.php?id=<?= $forum_id ?>">Cancelar</a>
                                            </form>
                                        <?php else: ?>
                                            <span style="font-weight:bold; color:#327f32;"><?= htmlspecialchars($comment['username']) ?></span>:
                                            <?= nl2br(htmlspecialchars($comment['content'])) ?>
                                            <span style="float:right; font-size:0.85em; color:#888;">em <?= date('d/m/Y H:i', strtotime($comment['created_at'])) ?></span>
                                            <?php if ($user_id && $comment['user_id'] == $user_id): ?>
                                                <form method="post" style="display:inline; float:right; margin-left:10px;">
                                                    <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                                    <button type="submit" name="delete_comment" onclick="return confirm('Excluir este comentário?');">Excluir</button>
                                                </form>
                                                <a href="forum.php?id=<?= $forum_id ?>&edit_comment=<?= $comment['id'] ?>" style="float:right;">Editar</a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <div style="clear:both;"></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div style="color:#888;">Nenhum comentário, seja o primeiro!</div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <div style="text-align:center; margin-top:2em;">
            <a href="forums.php" title="Voltar para Fóruns" style="display:inline-flex;align-items:center;gap:8px;padding:10px 22px;background:#327f32;color:#fff;font-weight:bold;border-radius:8px;font-size:1.1em;text-decoration:none;box-shadow:0 2px 8px rgba(50,127,50,0.10);transition:background 0.2s;">
                <img src="https://i.ibb.co/ZRKbDVzf/image-removebg-preview.png" alt="Voltar" style="height:1.5em;vertical-align:middle;">
                Voltar para Fóruns
            </a>
        </div>
    </div>
</body>
</html>
<script>
document.querySelectorAll('.ajax-comment-form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var postId = form.getAttribute('data-post-id');
        var formData = new FormData(form);
        formData.append('ajax_create_comment', '1');
        fetch('forum.php?id=' + <?= $forum_id ?>, {
            method: 'POST',
            body: formData
        })
        .then(r => r.text())
        .then(function(html) {
            if (html.trim()) {
                document.getElementById('comentarios-' + postId).insertAdjacentHTML('beforeend', html);
                form.reset();
            }
        });
    });
});
</script>
