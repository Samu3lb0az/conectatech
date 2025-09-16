// Funções para interações com posts
document.addEventListener('DOMContentLoaded', function() {
    // Seleções (com optional chaining)
    const addPostBtn = document.getElementById('addPostBtn');
    const modalPost = document.getElementById('modalPost');
    const closeModalPost = document.getElementById('closeModalPost');
    const formNovaPostagem = document.getElementById('formNovaPostagem');
    const inputImagem = formNovaPostagem?.querySelector('input[name="imagem"]') ?? null;

    // Adiciona campo descrição dinamicamente
    let descricaoField = null;
    if (inputImagem) {
        inputImagem.addEventListener('change', function() {
            if (inputImagem.files.length > 0) {
                if (!descricaoField) {
                    descricaoField = document.createElement('input');
                    descricaoField.type = 'text';
                    descricaoField.name = 'descricao';
                    descricaoField.placeholder = 'Descrição da imagem (opcional)';
                    descricaoField.className = 'descricao-post';
                    // se .querySelector('.btn-modal-post') for null, insertBefore com null funciona (insere no fim)
                    formNovaPostagem.insertBefore(descricaoField, formNovaPostagem.querySelector('.btn-modal-post'));
                }
            } else {
                if (descricaoField) {
                    descricaoField.remove();
                    descricaoField = null;
                }
            }
        });
    } else {
        // Aviso opcional para debug
        // console.warn('input[name="imagem"] não encontrado dentro do formNovaPostagem');
    }

    // Abrir/fechar modal (só se existir)
    if (addPostBtn && modalPost) {
        addPostBtn.onclick = () => modalPost.style.display = 'flex';
    }
    if (closeModalPost && modalPost) {
        closeModalPost.onclick = () => modalPost.style.display = 'none';
    }
    window.addEventListener('click', function(event) {
        if (modalPost && event.target === modalPost) {
            modalPost.style.display = 'none';
        }
    });

    // Envio AJAX da nova postagem
    if (formNovaPostagem) {
        formNovaPostagem.onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(formNovaPostagem);
            fetch('../includes/add_post.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Publicação enviada com sucesso!');
                    if (modalPost) modalPost.style.display = 'none';
                    formNovaPostagem.reset();
                    if (descricaoField) {
                        descricaoField.remove();
                        descricaoField = null;
                    }
                } else {
                    alert(data.error || 'Erro ao publicar.');
                }
            })
            .catch(() => {
                alert('Erro ao publicar.');
            });
        }
    }

    // Sistema de likes
    const likeButtons = document.querySelectorAll('.like-btn');
    likeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const postId = this.getAttribute('data-post-id');
            const likeCount = this.querySelector('.like-count');
            
            fetch('../includes/like.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `post_id=${postId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && likeCount) {
                    likeCount.textContent = data.likeCount;
                    this.classList.toggle('liked', !!data.liked);
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
            const comment = input?.value.trim();

            if (comment) {
                fetch('../includes/comment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `post_id=${postId}&comment=${encodeURIComponent(comment)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const commentsContainer = this.parentElement;
                        const commentDiv = document.createElement('div');
                        commentDiv.className = 'comment';
                        commentDiv.innerHTML = `<strong>${data.userName}</strong><p>${comment}</p>`;
                        commentsContainer.insertBefore(commentDiv, this);
                        if (input) input.value = '';
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        });
    });

    // Sistema de chat
    const messageInput = document.querySelector('.message-input input');
    const messageButton = document.querySelector('.message-input button');
    
    if (messageButton && messageInput) {
        messageButton.addEventListener('click', sendMessage);
        messageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') sendMessage();
        });
    }
    
    function sendMessage() {
        const message = messageInput?.value.trim();
        const receiverId = document.querySelector('.chat-item.active')?.getAttribute('data-user-id');
        
        if (message && receiverId) {
            fetch('includes/send_message.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `receiver_id=${receiverId}&message=${encodeURIComponent(message)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const messagesContainer = document.querySelector('.messages-container');
                    if (messagesContainer) {
                        const messageDiv = document.createElement('div');
                        messageDiv.className = 'message sent';
                        messageDiv.innerHTML = `<p>${message}</p>`;
                        messagesContainer.appendChild(messageDiv);
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    }
                    if (messageInput) messageInput.value = '';
                }
            })
            .catch(error => console.error('Error:', error));
        }
    }
});
