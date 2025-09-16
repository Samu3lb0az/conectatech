// Funções para interações com posts
document.addEventListener('DOMContentLoaded', function() {
    // Sistema de likes
    const likeButtons = document.querySelectorAll('.like-btn');
    likeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const postId = this.getAttribute('data-post-id');
            const likeCount = this.querySelector('.like-count');
            
            fetch('includes/like.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `post_id=${postId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    likeCount.textContent = data.likeCount;
                    if (data.liked) {
                        this.classList.add('liked');
                    } else {
                        this.classList.remove('liked');
                    }
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });
    
    // Sistema de comentários
    const commentForms = document.querySelectorAll('.comment-form');
    commentForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const postId = this.getAttribute('data-post-id');
            const input = this.querySelector('input');
            const comment = input.value.trim();
            
            if (comment) {
                fetch('includes/comment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `post_id=${postId}&comment=${encodeURIComponent(comment)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Adicionar o comentário à lista
                        const commentsContainer = this.parentElement;
                        const commentDiv = document.createElement('div');
                        commentDiv.className = 'comment';
                        commentDiv.innerHTML = `
                            <strong>${data.userName}</strong>
                            <p>${comment}</p>
                        `;
                        commentsContainer.insertBefore(commentDiv, this);
                        input.value = '';
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        });
    });
    
    // Sistema de chat
    const messageInput = document.querySelector('.message-input input');
    const messageButton = document.querySelector('.message-input button');
    
    if (messageButton) {
        messageButton.addEventListener('click', sendMessage);
        messageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
    }
    
    function sendMessage() {
        const message = messageInput.value.trim();
        const receiverId = document.querySelector('.chat-item.active')?.getAttribute('data-user-id');
        
        if (message && receiverId) {
            fetch('includes/send_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `receiver_id=${receiverId}&message=${encodeURIComponent(message)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Adicionar mensagem ao chat
                    const messagesContainer = document.querySelector('.messages-container');
                    const messageDiv = document.createElement('div');
                    messageDiv.className = 'message sent';
                    messageDiv.innerHTML = `<p>${message}</p>`;
                    messagesContainer.appendChild(messageDiv);
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    messageInput.value = '';
                }
            })
            .catch(error => console.error('Error:', error));
        }
    }
});