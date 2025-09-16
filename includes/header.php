<?php
// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Obtém informações do usuário
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>
<header>
    <div class="logo">
        <h1>ConectaTech</h1>
    </div>
    <nav>
        <ul>
            <li><a href="dashboard.php">Feed</a></li>
            <li><a href="profile.php">Perfil</a></li>
            <li><a href="chat.php">Chat</a></li>
            <li><a href="logout.php">Sair</a></li>
        </ul>
    </nav>
</header>