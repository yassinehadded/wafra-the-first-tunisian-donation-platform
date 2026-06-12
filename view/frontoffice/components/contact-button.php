<?php
/**
 * Contact Button Component
 * Reusable component for "Contact User" buttons
 * 
 * Usage:
 * include __DIR__ . '/components/contact-button.php';
 * renderContactButton($otherUserId, $entityType, $entityId, $options);
 */

/**
 * Render a contact button
 * 
 * @param int $otherUserId User ID to contact
 * @param string|null $entityType Entity type (post, donation, request)
 * @param int|null $entityId Entity ID
 * @param array $options Additional options:
 *   - 'label': Button label (default: "Contacter")
 *   - 'icon': Icon class (default: "fa-comments")
 *   - 'size': Button size (sm, md, lg)
 *   - 'disabled': Whether button is disabled
 *   - 'disabled_reason': Reason if disabled
 *   - 'show_helper': Show helper text (default: true)
 *   - 'class': Additional CSS classes
 */
function renderContactButton($otherUserId, $entityType = null, $entityId = null, $options = []) {
    global $baseUrl, $userId;
    
    $label = $options['label'] ?? '💬 Contacter';
    $icon = $options['icon'] ?? 'fa-comments';
    $size = $options['size'] ?? 'md';
    $disabled = $options['disabled'] ?? false;
    $disabledReason = $options['disabled_reason'] ?? '';
    $showHelper = $options['show_helper'] ?? true;
    $additionalClass = $options['class'] ?? '';
    
    // Size classes
    $sizeClasses = [
        'sm' => 'padding: 8px 16px; font-size: 13px;',
        'md' => 'padding: 12px 20px; font-size: 14px;',
        'lg' => 'padding: 14px 24px; font-size: 16px;'
    ];
    $sizeStyle = $sizeClasses[$size] ?? $sizeClasses['md'];
    
    // Don't show button if trying to contact self
    if ($otherUserId == $userId) {
        return '';
    }
    
    $buttonId = 'contact-btn-' . $otherUserId . '-' . ($entityId ?? 'general');
    $tooltipId = 'contact-tooltip-' . $otherUserId;
    
    ob_start();
    ?>
    <div class="contact-button-wrapper" style="margin: 12px 0;">
        <button 
            id="<?= htmlspecialchars($buttonId) ?>"
            class="contact-btn <?= htmlspecialchars($additionalClass) ?>"
            onclick="initiateContact(<?= (int)$otherUserId ?>, '<?= htmlspecialchars($entityType ?? '') ?>', <?= $entityId ? (int)$entityId : 'null' ?>)"
            <?= $disabled ? 'disabled title="' . htmlspecialchars($disabledReason) . '"' : '' ?>
            style="<?= $sizeStyle ?> <?= $disabled ? 'opacity: 0.6; cursor: not-allowed;' : '' ?>"
            data-other-user-id="<?= (int)$otherUserId ?>"
            data-entity-type="<?= htmlspecialchars($entityType ?? '') ?>"
            data-entity-id="<?= $entityId ? (int)$entityId : '' ?>"
        >
            <i class="fa <?= htmlspecialchars($icon) ?>"></i>
            <?= htmlspecialchars($label) ?>
        </button>
        
        <?php if ($showHelper && !$disabled): ?>
        <div class="contact-helper-text">
            <i class="fa fa-shield-alt"></i>
            <span>Messages privés et sécurisés</span>
        </div>
        <?php endif; ?>
        
        <?php if ($disabled && $disabledReason): ?>
        <div class="contact-helper-text" style="color: #e74c3c;">
            <i class="fa fa-info-circle"></i>
            <span><?= htmlspecialchars($disabledReason) ?></span>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
    // Contact button handler (global function, only define once)
    if (typeof window.initiateContact === 'undefined') {
        window.initiateContact = function(otherUserId, entityType, entityId) {
            if (!otherUserId || otherUserId <= 0) {
                alert('Erreur: ID utilisateur invalide');
                return;
            }
            
            // Show loading state
            const btn = document.querySelector(`[data-other-user-id="${otherUserId}"][data-entity-type="${entityType || ''}"][data-entity-id="${entityId || ''}"]`);
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Ouverture...';
            }
            
            // Create conversation
            fetch('<?= $baseUrl ?>/index.php?action=api_message&subaction=create_conversation', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    other_user_id: otherUserId,
                    entity_type: entityType || null,
                    entity_id: entityId || null
                })
            })
            .then(async response => {
                const responseText = await response.text();
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                try {
                    return JSON.parse(responseText);
                } catch (e) {
                    console.error('Invalid JSON response:', responseText.substring(0, 200));
                    throw new Error('Invalid JSON response');
                }
            })
            .then(data => {
                if (data.success && data.conversation) {
                    // Redirect to messages page with conversation
                    window.location.href = '<?= $baseUrl ?>/view/frontoffice/messages.php?conversation_id=' + data.conversation.id;
                } else {
                    alert('Erreur: ' + (data.error || 'Impossible de créer la conversation'));
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fa fa-comments"></i> <?= htmlspecialchars($label) ?>';
                    }
                }
            })
            .catch(error => {
                console.error('Error initiating contact:', error);
                alert('Erreur de connexion. Veuillez réessayer.');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa fa-comments"></i> <?= htmlspecialchars($label) ?>';
                }
            });
        };
    }
    </script>
    <?php
    return ob_get_clean();
}

