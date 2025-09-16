<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Buscar usuários para conversar
$stmt = $conn->prepare("SELECT id, username, full_name, profile_picture FROM users WHERE id != ?");
$stmt->execute([$user_id]);
$users = $stmt->fetchAll();

// Se um usuário for selecionado, buscar mensagens
$selected_user = null;
$messages = [];
if (isset($_GET['user_id'])) {
    $selected_user_id = $_GET['user_id'];
    $stmt = $conn->prepare("SELECT id, username, full_name, profile_picture FROM users WHERE id = ?");
    $stmt->execute([$selected_user_id]);
    $selected_user = $stmt->fetch();
    
    if ($selected_user) {
        // Buscar mensagens entre os dois usuários
        $stmt = $conn->prepare("
            SELECT m.*, u.username as sender_username 
            FROM messages m 
            JOIN users u ON m.sender_id = u.id 
            WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?) 
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$user_id, $selected_user_id, $selected_user_id, $user_id]);
        $messages = $stmt->fetchAll();
        
        // Marcar mensagens como lidas
        $stmt = $conn->prepare("UPDATE messages SET is_read = TRUE WHERE receiver_id = ? AND sender_id = ? AND is_read = FALSE");
        $stmt->execute([$user_id, $selected_user_id]);
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ConectaTech - Chat</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="container">
        <div class="chat-container">
            <div class="chat-list">
                <?php foreach ($users as $user): ?>
                    <div class="chat-item <?php echo ($selected_user && $selected_user['id'] == $user['id']) ? 'active' : ''; ?>" data-user-id="<?php echo $user['id']; ?>">
                        <img src="uploads/profiles/<?php echo $user['profile_picture']; ?>" alt="Foto de perfil" class="profile-pic-sm">
                        <div>
                            <strong><?php echo $user['full_name']; ?></strong>
                            <p>@<?php echo $user['username']; ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="chat-messages">
                <?php if ($selected_user): ?>
                    <h2>Conversa com <?php echo $selected_user['full_name']; ?></h2>
                    <div class="messages-container">
                        <?php foreach ($messages as $message): ?>
                            <div class="message <?php echo $message['sender_id'] == $user_id ? 'sent' : 'received'; ?>" data-message-id="<?php echo $message['id']; ?>">
                                <p><?php echo $message['message']; ?></p>
                                <span><?php echo date('H:i', strtotime($message['created_at'])); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <form class="message-form" method="POST" action="includes/send_message.php">
                        <input type="hidden" name="receiver_id" value="<?php echo $selected_user['id']; ?>">
                        <div class="message-input">
                            <input type="text" name="message" placeholder="Digite uma mensagem..." required>
                            <button type="submit">Enviar</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="empty-state">
                        <h2>Selecione uma conversa</h2>
                        <p>Escolha um usuário para começar a conversar.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/chat.js"></script>
</body>
</html>