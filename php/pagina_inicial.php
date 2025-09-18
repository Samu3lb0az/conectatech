<?php
session_start();
include_once '../config/database.php';
// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>Página Inicial - Vibe</title>
</head>

<body class="home-body">


    <main class="main-feed">
        <aside class="sidebar">
            <div class="logo">
                <img src="../assets/img/vibe-logo.png" alt="Vibe Logo" style="height:40px; vertical-align:middle;">
            </div>
            <nav>
                <ul>
                    <li><a href="#">Feeed</a></li>
                    <li><a href="profile.php">Perfil</a></li>
                    <li><a href="chat.php">Chat</a></li>
                    <li><a href="logout.php">Sair</a></li>
                </ul>
            </nav>
        </aside>
        <div class="container-conteudo">
            <div class="bemvindo-efeito">
                <h1 class="titulo-home-efeito">Bem-vindo à Vibe, <?php echo htmlspecialchars($_SESSION['email']); ?>!
                </h1>
            </div>
            <div class="home-container">
                <div class="feed">
                    <?php
                    // Buscar posts do usuário e dos seguidos
                    $user_id = $_SESSION['user_id'];


                    $sql = "SELECT p.*, u.email FROM posts p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute();
                    $posts = $stmt->fetchAll();

                    function formatDate($date)
                    {
                        $timestamp = strtotime($date);
                        $now = time();
                        $diff = $now - $timestamp;
                        if ($diff < 60) {
                            return 'Agora mesmo';
                        } elseif ($diff < 3600) {
                            return floor($diff / 60) . ' min atrás';
                        } elseif ($diff < 86400) {
                            return floor($diff / 3600) . ' h atrás';
                        } else {
                            return date('d/m/Y', $timestamp);
                        }
                    }

                    if ($posts && count($posts) > 0):
                        foreach ($posts as $post):
                            $userName = $post['email'] ?? 'Usuário';
                            $profilePic = '../assets/img/vibe-logo.png';
                            $dataFormatada = formatDate($post['created_at']);
                            ?>
                            <div class="post-card">
                                <div class="post-header">
                                    <img src="<?php echo htmlspecialchars($profilePic); ?>" class="profile-pic-sm"
                                        alt="Usuário">
                                    <div class="post-user-info">
                                        <strong><?php echo htmlspecialchars($userName); ?></strong>
                                        <span><?php echo $dataFormatada; ?></span>
                                    </div>
                                </div>
                                <?php if (!empty($post['imagem'])): ?>
                                    <div class="post-image">
                                        <img src="data:<?php echo htmlspecialchars($post['mime_type']); ?>;base64,<?php echo base64_encode($post['imagem']); ?>"
                                            alt="Post">
                                    </div>
                                <?php endif; ?>
                                <div class="post-caption">
                                    <?php if (!empty($post['conteudo'])): ?>
                                        <p><?php echo nl2br(htmlspecialchars($post['conteudo'])); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($post['descricao'])): ?>
                                        <p class="descricao-img"><em><?php echo htmlspecialchars($post['descricao']); ?></em></p>
                                    <?php endif; ?>
                                </div>
                                <?php

                                // Verifica se o usuário curtiu o post  foreach ($posts as $post):
                                $userName = $post['email'] ?? 'Usuário';
                                $profilePic = '../assets/img/vibe-logo.png';
                                $dataFormatada = formatDate($post['created_at']);

                                // total de likes
                                $stmtLikes = $conn->prepare('SELECT COUNT(*) FROM likes WHERE post_id = ?');
                                $stmtLikes->execute([$post['id']]);
                                $totalLikes = (int) $stmtLikes->fetchColumn();

                                // verifica se o usuário já curtiu
                                $stmtUserLike = $conn->prepare('SELECT id FROM likes WHERE user_id = ? AND post_id = ?');
                                $stmtUserLike->execute([$user_id, $post['id']]);
                                $liked = $stmtUserLike->fetch() ? 1 : 0;
                                ?>
                                <div class="post-card">
                                    <!-- cabeçalho e imagem aqui... -->

                                    <div class="post-actions">
                                        <?php
                                        // Conta curtidas
                                        $sqlLikes = "SELECT COUNT(*) FROM likes WHERE post_id = ?";
                                        $stmtLikes = $conn->prepare($sqlLikes);
                                        $stmtLikes->execute([$post['id']]);
                                        $totalLikes = $stmtLikes->fetchColumn();

                                        // Verifica se o usuário já curtiu
                                        $sqlUserLike = "SELECT 1 FROM likes WHERE post_id = ? AND user_id = ?";
                                        $stmtUserLike = $conn->prepare($sqlUserLike);
                                        $stmtUserLike->execute([$post['id'], $user_id]);
                                        $liked = $stmtUserLike->fetch();
                                        ?>
                                        <form method="post" action="../includes/like.php" style="display:inline;">
                                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                            <button type="submit" class="like-btn <?php echo $liked ? 'liked' : ''; ?>">
                                                <span class="heart-icon"><?php echo $liked ? '❤️' : '🤍'; ?></span>
                                                <?php echo $totalLikes; ?>
                                            </button>
                                        </form>


                                    </div>


                                    <div class="post-comments">
                                        <form action="../includes/comment.php" method="POST">
                                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                            <input type="text" name="comment" class="input-comment"
                                                placeholder="Escreva um comentário..." required>
                                            <button type="submit" class="btn-comment">Comentar</button>
                                        </form>

                                        <div class="comments-list">
                                            <?php
                                            // Buscar comentários do post
                                            $sqlComments = "SELECT c.comment, c.created_at, u.email 
                        FROM comments c 
                        JOIN users u ON c.user_id = u.id 
                        WHERE c.post_id = ? 
                        ORDER BY c.created_at ASC";
                                            $stmtComments = $conn->prepare($sqlComments);
                                            $stmtComments->execute([$post['id']]);
                                            $comments = $stmtComments->fetchAll();

                                            if ($comments && count($comments) > 0):
                                                foreach ($comments as $comment): ?>
                                                    <div class="comment">
                                                        <strong><?php echo htmlspecialchars($comment['email']); ?>:</strong>
                                                        <span><?php echo htmlspecialchars($comment['comment']); ?></span>
                                                        <small
                                                            class="comment-date"><?php echo formatDate($comment['created_at']); ?></small>
                                                    </div>
                                                <?php endforeach;
                                            else: ?>
                                                <div class="no-comments">Nenhum comentário ainda. Seja o primeiro!</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                </div>
                            <?php endforeach;
                    else:
                        ?>
                            <div class="empty-state">Nenhuma publicação encontrada.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
    </main>

    <!-- Botão flutuante adicionar postagem -->
    <button class="add-post-btn" id="addPostBtn" title="Nova postagem">
        <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="16" cy="16" r="16" fill="#5F2FEA" />
            <path d="M16 10V22" stroke="white" stroke-width="3" stroke-linecap="round" />
            <path d="M10 16H22" stroke="white" stroke-width="3" stroke-linecap="round" />
        </svg>
    </button>

    <!-- Modal de nova postagem -->
    <div class="modal-post" id="modalPost">
        <div class="modal-content-post">
            <span class="close-modal-post" id="closeModalPost">&times;</span>
            <h2>Nova publicação</h2>
            <form id="formNovaPostagem">
                <textarea name="conteudo" placeholder="O que você está pensando?" required></textarea>
                <input type="file" name="imagem" accept="image/*">
                <button type="submit" class="btn-modal-post">Publicar</button>
            </form>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
</body>

</html>