/**
 * Modern Posts System
 * Handles all frontend functionality for the modern posts interface
 */

class ModernPosts {
    constructor() {
        this.baseUrl = window.location.origin + '/wafra/wafra-integration';
        this.postsContainer = document.getElementById('posts-container');
        this.tabButtons = document.querySelectorAll('.tab-button');
        this.currentTab = 'all';
        this.isLoading = false;
        
        // Store the original posts container HTML to restore when needed
        this.originalContent = this.postsContainer ? this.postsContainer.innerHTML : '';
        
        this.initialize();
    }

    initialize() {
        if (!this.postsContainer) {
            console.error('Posts container not found');
            return;
        }
        
        this.attachEventListeners();
        this.setupEventDelegation();
    }

    setupEventDelegation() {
        // Handle like button clicks
        document.addEventListener('click', (e) => {
            const likeBtn = e.target.closest('.action-btn:not(.report-btn)');
            if (likeBtn) {
                e.preventDefault();
                const postId = likeBtn.closest('.post-card').querySelector('[id^="like-count-"]')?.id.replace('like-count-', '');
                if (postId) {
                    this.handleLikeClick(postId, likeBtn);
                }
            }
            
            // Handle comment form submission
            const commentForm = e.target.closest('.comment-form button');
            if (commentForm) {
                e.preventDefault();
                const postId = commentForm.closest('.comments-section').id.replace('comments-', '');
                const input = commentForm.previousElementSibling;
                if (postId && input) {
                    this.handleCommentSubmit(postId, input);
                }
            }
            
            // Toggle comments
            const commentBtn = e.target.closest('.action-btn .fa-comment')?.closest('.action-btn');
            if (commentBtn) {
                e.preventDefault();
                const postId = commentBtn.closest('.post-card').querySelector('[id^="comments-"]')?.id.replace('comments-', '');
                if (postId) {
                    this.toggleComments(postId);
                }
            }
        });
    }

    async handleLikeClick(postId, button) {
        if (this.isLoading) return;
        this.isLoading = true;
        
        const likeCountEl = document.getElementById(`like-count-${postId}`);
        const isLiked = button.classList.contains('liked');
        
        try {
            const formData = new FormData();
            formData.append('post_id', postId);
            
            const response = await fetch(`${this.baseUrl}/index.php?action=forum_like`, {
                method: isLiked ? 'DELETE' : 'POST',
                body: formData
            });
            
            if (response.ok) {
                // Toggle the like state
                button.classList.toggle('liked');
                const icon = button.querySelector('i');
                if (icon) {
                    icon.classList.toggle('fa-heart');
                    icon.classList.toggle('fa-heart-o');
                }
                
                // Update like count
                if (likeCountEl) {
                    const currentCount = parseInt(likeCountEl.textContent) || 0;
                    likeCountEl.textContent = isLiked ? Math.max(0, currentCount - 1) : currentCount + 1;
                }
            }
        } catch (error) {
            console.error('Error toggling like:', error);
        } finally {
            this.isLoading = false;
        }
    }

    async handleCommentSubmit(postId, input) {
        if (this.isLoading) return;
        this.isLoading = true;
        
        const content = input.value.trim();
        if (!content) {
            this.isLoading = false;
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('post_id', postId);
            formData.append('content', content);
            
            const response = await fetch(`${this.baseUrl}/index.php?action=forum_comment`, {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                // Clear the input
                input.value = '';
                
                // Reload the page to show the new comment
                window.location.reload();
            }
        } catch (error) {
            console.error('Error posting comment:', error);
        } finally {
            this.isLoading = false;
        }
    }

    toggleComments(postId) {
        const commentsSection = document.getElementById(`comments-${postId}`);
        if (!commentsSection) return;
        
        // Toggle display
        if (commentsSection.style.display === 'none' || !commentsSection.style.display) {
            commentsSection.style.display = 'block';
            // Focus the comment input
            const input = commentsSection.querySelector('.comment-input');
            if (input) input.focus();
        } else {
            commentsSection.style.display = 'none';
        }
    }
    }

    escapeHtml(unsafe) {
        if (!unsafe) return '';
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    showNoPostsMessage() {
        if (this.postsContainer) {
            this.postsContainer.innerHTML = `
                <div class="text-center" style="padding: 50px;">
                    <h3>Aucun post trouvé</h3>
                    <p>Soyez le premier à partager !</p>
                </div>
            `;
        }
    }

    showError(message) {
        console.error(message);
        // You can add a more visible error display here
    }

    setupInfiniteScroll() {
        window.addEventListener('scroll', () => {
            if (this.isLoading || !this.hasMorePosts) return;
            
            const scrollPosition = window.innerHeight + window.scrollY;
            const pageHeight = document.documentElement.scrollHeight - 100; // 100px buffer
                
            if (scrollPosition >= pageHeight) {
                this.loadPosts(true);
            }
        });
    }

    // Additional helper methods
    attachPostEventListeners() {
        // Like button click handler
        document.querySelectorAll('.like-button').forEach(button => {
            button.addEventListener('click', (e) => this.handleLikeClick(e));
        });
        
        // Comment button click handler
        document.querySelectorAll('.comment-button').forEach(button => {
            button.addEventListener('click', (e) => this.toggleComments(e));
        });
    }

    switchTab(tab) {
        if (this.currentTab === tab) return;
        
        this.currentTab = tab;
        this.currentPage = 1;
        
        // Show loading state
        if (this.postsContainer) {
            this.postsContainer.innerHTML = `
                <div class="text-center" style="padding: 20px;">
                    <i class="fa fa-spinner fa-spin"></i> Chargement...
                </div>
            `;
        }
        
        // Load posts for the new tab
        this.loadPosts();
    }
    
    async handleLikeClick(event) {
        event.preventDefault();
        const button = event.currentTarget;
        const postId = button.dataset.postId;
        const isLiked = button.classList.contains('liked');
        const likeCountEl = button.querySelector('.like-count');
        
        try {
            const formData = new FormData();
            formData.append('post_id', postId);
            
            const response = await fetch(`${this.baseUrl}/index.php?action=forum_like`, {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                // Toggle the like state
                button.classList.toggle('liked');
                const icon = button.querySelector('i');
                if (icon) {
                    icon.classList.toggle('fa-heart');
                    icon.classList.toggle('fa-heart-o');
                }
                
                // Update like count
                if (likeCountEl) {
                    const currentCount = parseInt(likeCountEl.textContent) || 0;
                    likeCountEl.textContent = isLiked ? Math.max(0, currentCount - 1) : currentCount + 1;
                }
            }
        } catch (error) {
            console.error('Error toggling like:', error);
        }
    }
    
    toggleComments(event) {
        const button = event.currentTarget;
        const postId = button.dataset.postId;
        const commentsSection = document.getElementById(`comments-${postId}`);
        
        if (!commentsSection) return;
        
        // Toggle the comments section
        if (commentsSection.style.display === 'none' || !commentsSection.style.display) {
            commentsSection.style.display = 'block';
            this.loadComments(postId, commentsSection);
        } else {
            commentsSection.style.display = 'none';
        }
    }
    
    async loadComments(postId, container) {
        try {
            const response = await fetch(`${this.baseUrl}/index.php?action=forum_comments&post_id=${postId}`);
            if (response.ok) {
                const comments = await response.json();
                this.renderComments(comments, container);
            }
        } catch (error) {
            console.error('Error loading comments:', error);
        }
    }
    
    renderComments(comments, container) {
        if (!comments || comments.length === 0) {
            container.innerHTML = '<div class="no-comments">Aucun commentaire pour le moment</div>';
            return;
        }
        
        const commentsHtml = comments.map(comment => `
            <div class="comment">
                <div class="comment-header">
                    <strong>${this.escapeHtml(comment.author_name || 'Utilisateur')}</strong>
                    <span class="comment-date">${new Date(comment.date_creation).toLocaleString('fr-FR')}</span>
                </div>
                <div class="comment-content">${this.escapeHtml(comment.contenu)}</div>
            </div>
        `).join('');
        
        container.innerHTML = `
            <div class="comments-list">
                ${commentsHtml}
            </div>
            <div class="add-comment">
                <textarea class="comment-input" placeholder="Ajouter un commentaire..." rows="2"></textarea>
                <button class="btn-primary post-comment" data-post-id="${container.id.replace('comments-', '')}">
                    <i class="fa fa-paper-plane"></i> Envoyer
                </button>
            </div>
        `;
        
        // Add event listener for posting comments
        const postButton = container.querySelector('.post-comment');
        if (postButton) {
            postButton.addEventListener('click', (e) => this.postComment(e));
        }
    }
    
    async postComment(event) {
        event.preventDefault();
        const button = event.currentTarget;
        const postId = button.dataset.postId;
        const input = button.previousElementSibling;
        const content = input.value.trim();
        
        if (!content) return;
        
        try {
            const formData = new FormData();
            formData.append('post_id', postId);
            formData.append('content', content);
            
            const response = await fetch(`${this.baseUrl}/index.php?action=forum_comment`, {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                // Clear the input
                input.value = '';
                
                // Reload comments
                const commentsSection = document.getElementById(`comments-${postId}`);
                if (commentsSection) {
                    this.loadComments(postId, commentsSection);
                }
            }
        } catch (error) {
            console.error('Error posting comment:', error);
        }
    }
}

// Helper function to escape HTML
escapeHtml(unsafe) {
    if (!unsafe) return '';
    return unsafe
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.modernPosts = new ModernPosts();
    });
} else {
    window.modernPosts = new ModernPosts();
}
