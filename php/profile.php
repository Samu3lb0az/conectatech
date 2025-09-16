<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

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
    <style>
        *{
            padding: 0;
            margin: 0;
            box-sizing: border-box;
        }

        body{
            font-family: Arial, Helvetica, sans-serif;
            background-color: #252525;
            color: white;
        }

        .sidebar {
            width: 200px;
            height: 100vh;
            font-weight: bolder;
            font-size: 22px;
            background: linear-gradient(#252525, #5F2FEA, #DB38B5);
            color: #fff;
            position: fixed;
            padding: 15px;
            border-right: white 1px solid;
        }

        .sidebar nav ul {
            list-style: none;
            height: 100%;
            display: flex;
            padding: 0;
            flex-direction: column;
            justify-content: center;
        }
        
        .sidebar nav ul li {
            margin: 15px 0;
        }

        .sidebar nav ul li a {
            color: #fff;
            text-decoration: none;
            display: block;
            padding: 10px;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .sidebar nav ul li a:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .main-content {
            margin-left: 200px;
            padding: 20px;
        }

        .profile-header {
            display: flex;
            align-items: center;
            padding: 40px 0;
            background: linear-gradient(to right, #252525, #5F2FEA, #DB38B5);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .profile-pic-lg {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 40px;
            background-color: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 14px;
            text-align: center;
            border: 3px solid white;
        }

        .profile-info {
            flex: 1;
        }

        .profile-info h1 {
            font-size: 28px;
            margin-bottom: 5px;
            color: white;
        }

        .profile-info p {
            margin-bottom: 10px;
            color: #ccc;
        }

        .bio {
            color: white !important;
            margin: 15px 0 !important;
        }

        .profile-stats {
            display: flex;
            margin-top: 20px;
        }

        .stat {
            margin-right: 30px;
            text-align: center;
            background: rgba(255, 255, 255, 0.1);
            padding: 10px 15px;
            border-radius: 8px;
        }

        .stat strong {
            display: block;
            font-size: 20px;
            color: white;
        }

        .stat span {
            font-size: 14px;
            color: #ccc;
        }

        .profile-posts {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 30px;
        }

        .profile-post {
            position: relative;
            aspect-ratio: 1/1;
            overflow: hidden;
            border-radius: 5px;
            background: linear-gradient(45deg, #5F2FEA, #DB38B5);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .post-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
            color: white;
        }

        .profile-post:hover .post-overlay {
            opacity: 1;
        }

        .likes, .comments {
            margin: 0 10px;
            font-weight: bold;
        }

        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            color: #ccc;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                border-right: none;
                border-bottom: white 1px solid;
            }
            
            .sidebar nav ul {
                flex-direction: row;
                justify-content: space-around;
                flex-wrap: wrap;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-pic-lg {
                margin-right: 0;
                margin-bottom: 20px;
            }
            
            .profile-stats {
                justify-content: center;
            }
            
            .profile-posts {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .profile-posts {
                grid-template-columns: 1fr;
            }
            
            .sidebar nav ul li {
                margin: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <nav>
            <ul>
                <li><a href="pagina_inicial.php">Feed</a></li>
                <li><a href="profile.php" style="background: rgba(255, 255, 255, 0.2);">Perfil</a></li>
                <li><a href="chat.php">Chat</a></li>
                <li><a href="includes/logout.php">Sair</a></li>
            </ul>
        </nav>
    </div>
    
    <div class="main-content">
        <div class="profile-header">
            <div class="profile-pic-lg">Foto de Perfil</div>
            <div class="profile-info">
                <h1><?php echo $user['full_name']; ?></h1>
                <p>@<?php echo $user['full_name']; ?></p>
                <p class="bio"><?php echo $user['bio']; ?></p>
                
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
                <div class="empty-state">
                    <h2>Nenhuma publicação ainda</h2>
                    <p>Compartilhe suas primeiras fotos!</p>
                </div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="profile-post">
                        Post #<?php echo $post['id']; ?>
                        <div class="post-overlay">
                            <span class="likes">❤️ <?php echo rand(10, 100); ?></span>
                            <span class="comments">💬 <?php echo rand(1, 20); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>