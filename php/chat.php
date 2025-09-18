<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Envio de mensagem por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['receiver_id'], $_POST['message'])) {
    $receiver_id = $_POST['receiver_id'];
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $receiver_id, $message]);
        // Redireciona para evitar reenvio do formulário
        header('Location: chat.php?user_id=' . $receiver_id);
        exit;
    }
}

// Buscar usuários disponíveis para conversar
$stmt = $conn->prepare("SELECT id, email, full_name FROM users WHERE id != ?");
$stmt->execute([$user_id]);
$users = $stmt->fetchAll();

// Buscar conversas recentes (consulta corrigida)
$stmt = $conn->prepare("
    SELECT 
        u.id, 
        u.full_name, 
        u.email,
        m.message, 
        m.created_at, 
        m.sender_id,
        (SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND sender_id = u.id AND is_read = FALSE) as unread
    FROM users u
    JOIN messages m ON (u.id = m.sender_id OR u.id = m.receiver_id)
    WHERE (m.sender_id = ? OR m.receiver_id = ?) 
    AND u.id != ?
    AND m.id IN (
        SELECT MAX(id) FROM messages 
        WHERE (sender_id = ? AND receiver_id = u.id) 
        OR (sender_id = u.id AND receiver_id = ?)
        GROUP BY LEAST(sender_id, receiver_id), GREATEST(sender_id, receiver_id)
    )
    ORDER BY m.created_at DESC
");
$stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
$conversations = $stmt->fetchAll();

// Se um usuário for selecionado, buscar mensagens
$selected_user = null;
$messages = [];
if (isset($_GET['user_id'])) {
    $selected_user_id = $_GET['user_id'];
    $stmt = $conn->prepare("SELECT id, email, full_name FROM users WHERE id = ?");
    $stmt->execute([$selected_user_id]);
    $selected_user = $stmt->fetch();
    
    if ($selected_user) {
        // Buscar mensagens entre os dois usuários
        $stmt = $conn->prepare("
            SELECT m.*, u.full_name as sender_name 
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
    <style>
        * {
            padding: 0;
            margin: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #252525;
            color: white;
            height: 100vh;
            display: flex;
        }

        .sidebar {
            width: 200px;
            height: 100%;
            font-weight: bolder;
            font-size: 22px;
            background: linear-gradient(#252525, #5F2FEA, #DB38B5);
            color: #fff;
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
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .chat-container {
            display: flex;
            flex: 1;
            overflow: hidden;
        }

        .conversations-list {
            width: 350px;
            border-right: 1px solid #444;
            overflow-y: auto;
            background: #1a1a1a;
        }

        .conversations-header {
            padding: 20px;
            border-bottom: 1px solid #444;
            font-size: 20px;
            font-weight: bold;
        }

        .conversation-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #333;
            cursor: pointer;
            transition: background 0.3s;
        }

        .conversation-item:hover, .conversation-item.active {
            background: #333;
        }

        .conversation-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(to right, #5F2FEA, #DB38B5);
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
        }

        .conversation-info {
            flex: 1;
        }

        .conversation-name {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .conversation-preview {
            font-size: 14px;
            color: #aaa;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
        }

        .conversation-time {
            font-size: 12px;
            color: #777;
        }

        .unread-badge {
            background: #5F2FEA;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            margin-left: 10px;
        }

        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            padding: 15px 20px;
            border-bottom: 1px solid #444;
            display: flex;
            align-items: center;
            background: #1a1a1a;
        }

        .chat-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(to right, #5F2FEA, #DB38B5);
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .messages-container {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #252525;
        }

        .message {
            max-width: 70%;
            margin-bottom: 15px;
            padding: 10px 15px;
            border-radius: 18px;
            position: relative;
        }

        .message.sent {
            background: linear-gradient(to right, #5F2FEA, #DB38B5);
            margin-left: auto;
            border-bottom-right-radius: 5px;
        }

        .message.received {
            background: #444;
            border-bottom-left-radius: 5px;
        }

        .message-time {
            font-size: 11px;
            color: #aaa;
            margin-top: 5px;
            text-align: right;
        }

        .message-input-container {
            padding: 15px;
            border-top: 1px solid #444;
            background: #1a1a1a;
        }

        .message-form {
            display: flex;
        }

        .message-input {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 20px;
            background: #333;
            color: white;
            margin-right: 10px;
        }

        .send-button {
            padding: 12px 20px;
            background: linear-gradient(to right, #5F2FEA, #DB38B5);
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
        }

        .no-conversation {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            color: #777;
        }

        .back-button {
            margin-right: 15px;
            cursor: pointer;
            font-size: 20px;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            
            .conversations-list {
                width: 100%;
                display: <?php echo $selected_user ? 'none' : 'block'; ?>;
            }
            
            .chat-area {
                display: <?php echo $selected_user ? 'flex' : 'none'; ?>;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <nav>
            <ul>
                <li><a href="pagina_inicial.php">Feed</a></li>
                <li><a href="profile.php">Perfil</a></li>
                <li><a href="#" style="background: rgba(255, 255, 255, 0.2);">Chat</a></li>
                <li><a href="logout.php">Sair</a></li>
            </ul>
        </nav>
    </div>
    
    <div class="main-content">
        <div class="chat-container">
            <div class="conversations-list">
                <div class="conversations-header">
                    Usuários
                </div>
                <?php if (empty($users)): ?>
                    <div style="padding: 20px; color: #777; text-align: center;">
                        Nenhum usuário encontrado
                    </div>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <div class="conversation-item <?php echo ($selected_user && $selected_user['id'] == $u['id']) ? 'active' : ''; ?>" 
                             onclick="window.location.href='chat.php?user_id=<?php echo $u['id']; ?>'">
                            <div class="conversation-avatar">
                                <?php echo strtoupper(substr($u['full_name'], 0, 1)); ?>
                            </div>
                            <div class="conversation-info">
                                <div class="conversation-name"><?php echo $u['full_name']; ?></div>
                                <div class="conversation-preview">
                                    <?php 
                                    // Mostra última mensagem se houver
                                    $lastMsg = null;
                                    foreach ($conversations as $conv) {
                                        if ($conv['id'] == $u['id']) {
                                            $lastMsg = $conv;
                                            break;
                                        }
                                    }
                                    if ($lastMsg) {
                                        echo ($lastMsg['sender_id'] == $user_id ? 'Você: ' : '') . $lastMsg['message'];
                                    } else {
                                        echo 'Clique para conversar';
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="conversation-time">
                                <?php 
                                    if (isset($lastMsg['created_at'])) {
                                        $time = strtotime($lastMsg['created_at']);
                                        echo date('H:i', $time); 
                                    }
                                ?>
                            </div>
                            <?php 
                                if ($lastMsg && $lastMsg['unread'] > 0) {
                                    echo '<div class="unread-badge">' . $lastMsg['unread'] . '</div>';
                                }
                            ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <?php if ($selected_user): ?>
                <div class="chat-area">
                    <div class="chat-header">
                        <div class="back-button" onclick="window.location.href='chat.php'">←</div>
                        <div class="chat-avatar">
                            <?php echo strtoupper(substr($selected_user['full_name'], 0, 1)); ?>
                        </div>
                        <div class="conversation-name"><?php echo $selected_user['full_name']; ?></div>
                    </div>
                    
                    <div class="messages-container" id="messages-container">
                        <?php foreach ($messages as $message): ?>
                            <div class="message <?php echo $message['sender_id'] == $user_id ? 'sent' : 'received'; ?>">
                                <div class="message-text"><?php echo $message['message']; ?></div>
                                <div class="message-time">
                                    <?php echo date('H:i', strtotime($message['created_at'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="message-input-container">
                        <form class="message-form" method="POST" action="">
                            <input type="hidden" name="receiver_id" value="<?php echo $selected_user['id']; ?>">
                            <input type="text" class="message-input" name="message" placeholder="Digite uma mensagem..." required autocomplete="off">
                            <button type="submit" class="send-button">Enviar</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="no-conversation">
                    <h2>Selecione uma conversa</h2>
                    <p>Escolha um contato para começar a conversar</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Função para rolar para o final das mensagens
        function scrollToBottom() {
            const container = document.getElementById('messages-container');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        }
        window.addEventListener('load', scrollToBottom);
    </script>
</body>
</html>