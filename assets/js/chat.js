document.addEventListener('DOMContentLoaded', function() {
    const chatItems = document.querySelectorAll('.chat-item');
    chatItems.forEach(item => {
        item.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            window.location.href = `chat.php?user_id=${userId}`;
        });
    });
    
    // Long polling para novas mensagens
    if (document.querySelector('.messages-container')) {
        let lastMessageId = 0;
        const messagesContainer = document.querySelector('.messages-container');
        const messageForm = document.querySelector('.message-form');
        const receiverId = document.querySelector('input[name="receiver_id"]').value;
        
        // Buscar o ID da última mensagem
        const messages = messagesContainer.querySelectorAll('.message');
        if (messages.length > 0) {
            lastMessageId = messages[messages.length - 1].getAttribute('data-message-id');
        }
        
        function checkNewMessages() {
            fetch(`includes/get_messages.php?receiver_id=${receiverId}&last_message_id=${lastMessageId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.messages && data.messages.length > 0) {
                        data.messages.forEach(message => {
                            const messageDiv = document.createElement('div');
                            messageDiv.className = message.sender_id == <?php echo $user_id; ?> ? 'message sent' : 'message received';
                            messageDiv.setAttribute('data-message-id', message.id);
                            messageDiv.innerHTML = `
                                <p>${message.message}</p>
                                <span>${new Date(message.created_at).toLocaleTimeString()}</span>
                            `;
                            messagesContainer.appendChild(messageDiv);
                            lastMessageId = message.id;
                        });
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    }
                    setTimeout(checkNewMessages, 3000);
                })
                .catch(error => {
                    console.error('Erro ao buscar mensagens:', error);
                    setTimeout(checkNewMessages, 3000);
                });
        }
        
        // Iniciar long polling
        checkNewMessages();
        
        // Enviar mensagem via AJAX
        messageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('includes/send_message.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const messageDiv = document.createElement('div');
                    messageDiv.className = 'message sent';
                    messageDiv.setAttribute('data-message-id', data.message_id);
                    messageDiv.innerHTML = `
                        <p>${formData.get('message')}</p>
                        <span>Agora</span>
                    `;
                    messagesContainer.appendChild(messageDiv);
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    this.reset();
                    lastMessageId = data.message_id;
                }
            })
            .catch(error => console.error('Erro ao enviar mensagem:', error));
        });
    }
});