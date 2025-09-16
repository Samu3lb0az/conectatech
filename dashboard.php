<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$posts = getFeedPosts($user_id);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ConectaTech - Feed</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="container">
        <div class="feed">
            <?php if (empty($posts)): ?>
                <div class="empty-state">
                    <h2>Nenhuma postagem ainda</h2>
                    <p>Siga outros usuários para ver suas postagens aqui.</p>
                </div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post-card">
                        <div class="post-header">
                            <img src="uploads/profiles/<?php echo $post['profile_picture']; ?>" alt="Foto de perfil" class="profile-pic-sm">
                            <div class="post-user-info">
                                <strong><?php echo $post['full_name']; ?></strong>
                                <span><?php echo formatDate($post['created_at']); ?></span>
                            </div>
                        </div>
                        
                        <div class="post-image">
                            <img src="uploads/posts/<?php echo $post['image_path']; ?>" alt="Post image">
                        </div>
                        
                        <div class="post-actions">
                            <?php $isLiked = isPostLiked($user_id, $post['id']); ?>
                            <button class="like-btn <?php echo $isLiked ? 'liked' : ''; ?>" data-post-id="<?php echo $post['id']; ?>">
                                <span class="heart-icon">❤</span>
                                <span class="like-count"><?php echo getLikeCount($post['id']); ?></span>
                            </button>
                            <button class="comment-btn">
                                <span class="comment-icon">💬</span>
                                Comentar
                            </button>
                        </div>
                        
                        <div class="post-caption">
                            <p><strong><?php echo $post['full_name']; ?></strong> <?php echo $post['caption']; ?></p>
                        </div>
                        
                        <div class="post-comments">
                            <h4>Comentários</h4>
                            <?php $comments = getPostComments($post['id']); ?>
                            <?php foreach ($comments as $comment): ?>
                                <div class="comment">
                                    <strong><?php echo $comment['full_name']; ?></strong>
                                    <p><?php echo $comment['comment']; ?></p>
                                </div>
                            <?php endforeach; ?>
                            
                            <form class="comment-form" data-post-id="<?php echo $post['id']; ?>">
                                <input type="text" placeholder="Adicione um comentário..." required>
                                <button type="submit">Publicar</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/script.js"></script>
</body>
</html>