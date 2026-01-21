<?php
declare(strict_types=1);

namespace SimpleLMS;

/**
 * Legacy file kept for backwards compatibility.
 *
 * Custom post types are registered by Managers\CPTManager.
 */

if (!defined('ABSPATH')) {
    exit;
}

function renderManagementPageScripts(): void {
    ?>
    <script>
    function copyToClipboard(button, text) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(function() {
                showCopySuccess(button);
            }).catch(function() {
                fallbackCopyTextToClipboard(button, text);
            });
        } else {
            fallbackCopyTextToClipboard(button, text);
        }
    }
    function fallbackCopyTextToClipboard(button, text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            const successful = document.execCommand('copy');
            if (successful) { showCopySuccess(button); } else { showCopyError(button); }
        } catch (err) {
            showCopyError(button);
        }
        document.body.removeChild(textArea);
    }
    function showCopySuccess(button) {
        const originalText = button.textContent;
        button.textContent = 'Skopiowano!';
        button.classList.add('copied');
        setTimeout(function() {
            button.textContent = originalText;
            button.classList.remove('copied');
        }, 2000);
    }
    function showCopyError(button) {
        const originalText = button.textContent;
        button.textContent = 'Błąd!';
        button.style.background = '#dc3232';
        setTimeout(function() {
            button.textContent = originalText;
            button.style.background = '';
        }, 2000);
    }
    function toggleDetails(button, containerId) {
        const container = document.getElementById(containerId);
        const isExpanded = container.classList.contains('expanded');
        if (isExpanded) { container.classList.remove('expanded'); button.textContent = 'więcej'; }
        else { container.classList.add('expanded'); button.textContent = 'mniej'; }
    }
    </script>
    <?php
}

/**
 * CSS classes section renderer
 */
function renderCssClassesSection(): void {
    ?>
    <!-- CSS CLASSES SECTION -->
    <div class="management-section">
        <h2 class="section-header">🎨 Klasy CSS - Kontrola Dostępu</h2>
        
        <div class="item-container">
            <div class="item-header">
                <div class="item-code">.simple-lms-with-access</div>
                <div class="item-description">Widoczne dla użytkowników z dostępem do kursu (dodaj do kontenera w Elementorze)</div>
            </div>
            <div class="item-actions">
                <button class="copy-btn" onclick="copyToClipboard(this, 'simple-lms-with-access')">Kopiuj</button>
                <button class="more-btn" onclick="toggleDetails(this, 'details-css-with')">więcej</button>
            </div>
            <div id="details-css-with" class="item-details">
                <div class="detail-section">
                    <div class="detail-title">Zastosowanie:</div>
                    <div class="param-item">Dodaj tę klasę do kontenera w Elementorze zawierającego treść kursu/lessons</div>
                    <div class="param-item">Kontener będzie widoczny tylko dla użytkowników z odpowiednimi rolami</div>
                </div>
                <div class="detail-section">
                    <div class="detail-title">Przykład HTML:</div>
                    <code class="example-code">&lt;div class="simple-lms-with-access"&gt;Content dla użytkowników z dostępem&lt;/div&gt;</code>
                </div>
            </div>
        </div>

        <div class="item-container">
            <div class="item-header">
                <div class="item-code">.simple-lms-without-access</div>
                <div class="item-description">Widoczne dla użytkowników bez dostępu do kursu (dodaj do kontenera w Elementorze)</div>
            </div>
            <div class="item-actions">
                <button class="copy-btn" onclick="copyToClipboard(this, 'simple-lms-without-access')">Kopiuj</button>
                <button class="more-btn" onclick="toggleDetails(this, 'details-css-without')">więcej</button>
            </div>
            <div id="details-css-without" class="item-details">
                <div class="detail-section">
                    <div class="detail-title">Zastosowanie:</div>
                    <div class="param-item">Dodaj tę klasę do kontenera z komunikatem o braku dostępu</div>
                    <div class="param-item">Kontener będzie widoczny tylko dla użytkowników bez odpowiednich ról</div>
                </div>
                <div class="detail-section">
                    <div class="detail-title">Przykład HTML:</div>
                    <code class="example-code">&lt;div class="simple-lms-without-access"&gt;Nie masz dostępu. &lt;a href="/kup-kurs"&gt;Kup kurs&lt;/a&gt;&lt;/div&gt;</code>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Filter courses by user role - optimized version
 * 
 * @param \WP_Query $query Query object
 * @return \WP_Query Modified query object
 */
function filterCoursesByUserRole(\WP_Query $query): \WP_Query {
    if (is_admin() || !$query->is_main_query() || !$query->is_post_type_archive('course')) {
        return $query;
    }

    // REMOVED: Role-based filtering
    // Course access is now managed via user_meta tags (simple_lms_course_access)
    // Filtering by access is handled in individual course/lesson rendering
    // See includes/access-control.php for tag-based access checks
    
    // No filtering needed - courses are publicly visible, access is enforced at content level
    return $query;
}
add_filter('pre_get_posts', __NAMESPACE__ . '\filterCoursesByUserRole');

/**
 * Update post slug when title changes
 * 
 * @param int $postId Post ID
 * @param \WP_Post $post Post object  
 * @param bool $update Whether this is an update
 * @return void
 */
function updatePostSlug(int $postId, \WP_Post $post, bool $update): void {
    // Prevent infinite loop
    static $recursionGuard = false;
    if ($recursionGuard) {
        return;
    }

    if (!$update || wp_is_post_revision($postId)) {
        return;
    }

    if (!in_array($post->post_type, ['course', 'module', 'lesson'])) {
        return;
    }

    // Skip Elementor templates and library posts
    if (in_array($post->post_type, ['elementor_library', 'elementor_snippet', 'e-landing-page'], true)) {
        return;
    }

    $recursionGuard = true;
    remove_action('save_post', __NAMESPACE__ . '\updatePostSlug', 10);

    wp_update_post([
        'ID' => $postId,
        'post_name' => sanitize_title($post->post_title)
    ]);

    add_action('save_post', __NAMESPACE__ . '\updatePostSlug', 10, 3);
    $recursionGuard = false;
}
add_action('save_post', __NAMESPACE__ . '\updatePostSlug', 10, 3);

/**
 * REMOVED: fixExistingCourseRoles() - no longer needed with tag-based access
 * Use migration script to backfill user_meta tags from historical WooCommerce orders
 */