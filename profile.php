<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Buscar informações do usuário
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Buscar posts do usuário
$stmt = $conn->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$posts = $stmt->fetchAll();

// Buscar estatísticas
$stmt = $conn->prepare("SELECT COUNT(*) as post_count FROM posts WHERE user_id = ?");
$stmt->execute([$user_id]);
$post_count = $stmt->fetch()['post_count'];

$stmt = $conn->prepare("SELECT COUNT(*) as follower_count FROM followers WHERE following_id = ?");
$stmt->execute([$user_id]);
$follower_count = $stmt->fetch()['follower_count'];

$stmt = $conn->prepare("SELECT COUNT(*) as following_count FROM followers WHERE follower_id = ?");
$stmt->execute([$user_id]);
$following_count = $stmt->fetch()['following_count'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ConectaTech - Perfil</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="container">
        <div class="profile-header">
            <img src="uploads/profiles/<?php echo $user['profile_picture']; ?>" alt="Foto de perfil" class="profile-pic-lg">
            <div class="profile-info">
                <h1><?php echo $user['full_name']; ?></h1>
                <p>@<?php echo $user['username']; ?></p>
                <p><?php echo $user['bio']; ?></p>
                
                <div class="profile-stats">
                    <div class="stat">
                        <strong><?php echo $post_count; ?></strong>
                        <span>Publicações</span>
                    </div>
                    <div class="stat">
                        <strong><?php echo $follower_count; ?></strong>
                        <span>Seguidores</span>
                    </div>
                    <div class="stat">
                        <strong><?php echo $following_count; ?></strong>
                        <span>Seguindo</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="profile-posts">
            <?php if (empty($posts)): ?>
                <p>Nenhuma publicação ainda.</p>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="profile-post">
                        <img src="uploads/posts/<?php echo $post['image_path']; ?>" alt="Post">
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>