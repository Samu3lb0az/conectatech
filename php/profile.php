<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Processar upload de foto de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $target_dir = "../uploads/profiles/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION);
    $new_filename = "profile_" . $user_id . "_" . time() . "." . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Verificar se é uma imagem real
    $check = getimagesize($_FILES["profile_picture"]["tmp_name"]);
    if ($check !== false) {
        // Limitar o tamanho do arquivo (5MB)
        if ($_FILES["profile_picture"]["size"] > 5000000) {
            $upload_error = "Arquivo muito grande. Tamanho máximo: 5MB.";
        } else {
            // Permitir apenas certos formatos
            $allowed_extensions = array("jpg", "jpeg", "png", "gif");
            if (in_array(strtolower($file_extension), $allowed_extensions)) {
                if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
                    // Atualizar no banco de dados
                    $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                    if ($stmt->execute([$new_filename, $user_id])) {
                        $upload_success = "Foto de perfil atualizada com sucesso!";
                    } else {
                        $upload_error = "Erro ao atualizar no banco de dados.";
                    }
                } else {
                    $upload_error = "Erro ao fazer upload do arquivo.";
                }
            } else {
                $upload_error = "Apenas arquivos JPG, JPEG, PNG e GIF são permitidos.";
            }
        }
    } else {
        $upload_error = "O arquivo não é uma imagem válida.";
    }
}

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

// Função para formatar a data
function formatDate($date) {
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

        .profile-pic-container {
            position: relative;
            margin-right: 40px;
            cursor: pointer;
        }

        .profile-pic-lg {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            background-color: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 14px;
            text-align: center;
            border: 3px solid white;
            transition: opacity 0.3s;
        }

        .profile-pic-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
            color: white;
            font-size: 14px;
            text-align: center;
        }

        .profile-pic-container:hover .profile-pic-overlay {
            opacity: 1;
        }

        .profile-pic-container:hover .profile-pic-lg {
            opacity: 0.8;
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

        .post-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .post-text {
            padding: 15px;
            text-align: center;
            word-break: break-word;
        }

        .post-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
            color: white;
            padding: 15px;
            text-align: center;
        }

        .profile-post:hover .post-overlay {
            opacity: 1;
        }

        .post-content {
            margin-bottom: 10px;
            font-size: 14px;
        }

        .post-date {
            font-size: 12px;
            color: #ccc;
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

        /* Modal de upload */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: #333;
            padding: 30px;
            border-radius: 10px;
            width: 400px;
            max-width: 90%;
            text-align: center;
        }

        .modal h2 {
            margin-bottom: 20px;
            color: white;
        }

        .modal-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-primary {
            background: linear-gradient(to right, #5F2FEA, #DB38B5);
            color: white;
        }

        .btn-secondary {
            background-color: #666;
            color: white;
        }

        .alert {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .alert-success {
            background-color: #4CAF50;
            color: white;
        }

        .alert-error {
            background-color: #f44336;
            color: white;
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
            
            .profile-pic-container {
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
        <!-- Mensagens de sucesso/erro -->
        <?php if (isset($upload_success)): ?>
            <div class="alert alert-success"><?php echo $upload_success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($upload_error)): ?>
            <div class="alert alert-error"><?php echo $upload_error; ?></div>
        <?php endif; ?>
        
        <div class="profile-header">
            <div class="profile-pic-container" onclick="openModal()">
                <?php if (!empty($user['profile_picture'])): ?>
                    <img src="../uploads/profiles/<?php echo $user['profile_picture']; ?>" class="profile-pic-lg" alt="Foto de Perfil">
                <?php else: ?>
                    <div class="profile-pic-lg">Clique para adicionar foto</div>
                <?php endif; ?>
                <div class="profile-pic-overlay">
                    Alterar foto
                </div>
            </div>
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
                        <?php if (!empty($post['imagem'])): ?>
                            <img src="data:<?php echo $post['mime_type']; ?>;base64,<?php echo base64_encode($post['imagem']); ?>" 
                                 class="post-image" 
                                 alt="Post de <?php echo $user['full_name']; ?>">
                        <?php else: ?>
                            <div class="post-text">
                                <?php echo nl2br(htmlspecialchars($post['conteudo'])); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="post-overlay">
                            <?php if (!empty($post['conteudo']) && !empty($post['imagem'])): ?>
                                <div class="post-content">
                                    <?php echo nl2br(htmlspecialchars($post['conteudo'])); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($post['descricao'])): ?>
                                <div class="post-content">
                                    <em><?php echo htmlspecialchars($post['descricao']); ?></em>
                                </div>
                            <?php endif; ?>
                            
                            <div class="post-date">
                                <?php echo formatDate($post['created_at']); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para upload de foto -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <h2>Alterar foto de perfil</h2>
            <form action="profile.php" method="post" enctype="multipart/form-data" id="uploadForm">
                <input type="file" name="profile_picture" id="profilePictureInput" accept="image/*" style="display: none;" onchange="document.getElementById('uploadForm').submit()">
                <button type="button" class="btn btn-primary" onclick="document.getElementById('profilePictureInput').click()">Selecionar imagem</button>
                <div class="modal-buttons">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('uploadModal').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('uploadModal').style.display = 'none';
        }
        
        // Fechar modal clicando fora dele
        window.onclick = function(event) {
            const modal = document.getElementById('uploadModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>