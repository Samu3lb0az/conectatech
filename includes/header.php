<?php
if (!isset($_SESSION)) {
    session_start();
}
require_once 'config/database.php';
require_once 'includes/auth.php';

$user_id = isLoggedIn() ? $_SESSION['user_id'] : null;
$user = null;
if ($user_id) {
    $stmt = $conn->prepare("SELECT username, profile_picture FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
}
?>

<header>
    <div class="container header-content">
        <div class="logo">
            <a href="dashboard.php">ConectaTech</a>
        </div>
        
        <nav>
            <ul>
                <?php if (isLoggedIn()): ?>
                    <li><a href="dashboard.php">Feed</a></li>
                    <li><a href="profile.php">Perfil</a></li>
                    <li><a href="chat.php">Chat</a></li>
                    <li><a href="includes/logout.php">Sair</a></li>
                    <?php if ($user): ?>
                        <li>
                            <a href="profile.php">
                                <img src="uploads/profiles/<?php echo $user['profile_picture']; ?>" alt="Foto de perfil" class="profile-pic-sm">
                            </a>
                        </li>
                    <?php endif; ?>
                <?php else: ?>
                    <li><a href="index.php">Login</a></li>
                    <li><a href="register.php">Cadastrar</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>