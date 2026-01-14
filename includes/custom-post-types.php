<?php
/**
 * Custom Post Types for Simple LMS
 * 
 * @package SimpleLMS
 * @since 1.0.1
 */

declare(strict_types=1);

namespace SimpleLMS;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register custom post types for the LMS system
 * 
 * @return void
 */
function registerPostTypes(): void {
    $postTypes = [
        'course' => [
            'labels' => [
                'name'               => __('Kursy', 'simple-lms'),
                'singular_name'      => __('Kurs', 'simple-lms'),
                'menu_name'          => __('Kursy', 'simple-lms'),
                'add_new_item'       => __('Utwórz kurs', 'simple-lms'),
                'edit_item'          => __('Edytuj kurs', 'simple-lms'),
                'new_item'           => __('Nowy kurs', 'simple-lms'),
                'view_item'          => __('Zobacz kurs', 'simple-lms'),
                'all_items'          => __('Wszystkie kursy', 'simple-lms'),
                'search_items'       => __('Szukaj kursów', 'simple-lms'),
                'not_found'          => __('Nie znaleziono kursów', 'simple-lms'),
                'not_found_in_trash' => __('Brak kursów w koszu', 'simple-lms'),
            ],
            'public'              => true,
            'menu_icon'           => 'dashicons-welcome-learn-more',
            'has_archive'         => true,
            'show_in_menu'        => true,
            'show_in_rest'        => false,
            'rest_base'           => 'courses',
            'supports'            => ['title', 'editor', 'thumbnail'],
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
            'rewrite'            => [
                'slug'       => _x('kursy', 'slug', 'simple-lms'),
                'with_front' => false
            ]
        ],
        'module' => [
            'labels' => [
                'name'               => __('Moduły', 'simple-lms'),
                'singular_name'      => __('Moduł', 'simple-lms'),
                'menu_name'          => __('Moduły', 'simple-lms'),
                'add_new_item'       => __('Dodaj nowy moduł', 'simple-lms'),
                'edit_item'          => __('Edytuj moduł', 'simple-lms'),
                'new_item'           => __('Nowy moduł', 'simple-lms'),
                'view_item'          => __('Zobacz moduł', 'simple-lms'),
                'all_items'          => __('Wszystkie moduły', 'simple-lms'),
                'search_items'       => __('Szukaj modułów', 'simple-lms'),
                'not_found'          => __('Nie znaleziono modułów', 'simple-lms'),
                'not_found_in_trash' => __('Brak modułów w koszu', 'simple-lms'),
            ],
            'public'              => true,
            'menu_icon'           => 'dashicons-format-aside',
            'has_archive'         => false,
            'show_in_menu'        => 'edit.php?post_type=course',
            'show_in_rest'        => false,
            'rest_base'           => 'modules',
            'supports'            => ['title', 'editor'],
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
            'rewrite'            => [
                'slug'       => _x('moduly', 'slug', 'simple-lms'),
                'with_front' => false
            ]
        ],
        'lesson' => [
            'labels' => [
                'name'               => __('Lekcje', 'simple-lms'),
                'singular_name'      => __('Lekcja', 'simple-lms'),
                'menu_name'          => __('Lekcje', 'simple-lms'),
                'add_new_item'       => __('Dodaj nową lekcję', 'simple-lms'),
                'edit_item'          => __('Edytuj lekcję', 'simple-lms'),
                'new_item'           => __('Nowa lekcja', 'simple-lms'),
                'view_item'          => __('Zobacz lekcję', 'simple-lms'),
                'all_items'          => __('Wszystkie lekcje', 'simple-lms'),
                'search_items'       => __('Szukaj lekcji', 'simple-lms'),
                'not_found'          => __('Nie znaleziono lekcji', 'simple-lms'),
                'not_found_in_trash' => __('Brak lekcji w koszu', 'simple-lms'),
            ],
            'public'              => true,
            'menu_icon'           => 'dashicons-media-document',
            'has_archive'         => false,
            'show_in_menu'        => 'edit.php?post_type=course',
            'show_in_rest'        => false,
            'rest_base'           => 'lessons',
            'supports'            => ['title', 'editor'],
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
            'rewrite'            => [
                'slug'       => _x('lekcje', 'slug', 'simple-lms'),
                'with_front' => false
            ]
        ]
    ];

    foreach ($postTypes as $type => $args) {
        register_post_type($type, $args);
    }
}
add_action('init', __NAMESPACE__ . '\registerPostTypes');

/**
 * Render management page with shortcodes and CSS classes
 * 
 * @return void
 */
function displayShortcodesPage(): void {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    renderManagementPageStyles();
    ?>
    <div class="wrap">
        <h1>📚 Simple LMS - Zarządzanie</h1>
        <?php 
        renderShortcodesSection();
        renderCssClassesSection();
        ?>
    </div>
    <?php
    renderManagementPageScripts();
}

/**
 * Styles for the management admin page
 */
function renderManagementPageStyles(): void {
    ?>
}

/**
 * Styles for the management admin page
 */
function renderManagementPageStyles(): void {
    ?>
    <style>
    .management-section {
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 6px;
        margin: 20px 0;
        padding: 0;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
    }
    .section-header {
        background: #f6f7f7;
        border-bottom: 1px solid #ccd0d4;
        padding: 16px 20px;
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        color: #1e1e1e;
    }
    .item-container {
        padding: 16px 20px;
        border-bottom: 1px solid #f0f0f1;
        display: block;
    }
    .item-container:last-child {
        border-bottom: none;
    }
    .item-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 8px;
    }
    .item-info { flex: 1; }
    .item-code {
        font-family: 'Courier New', monospace;
        background: #f6f8fa;
        padding: 4px 8px;
        border-radius: 4px;
        color: #0969da;
        font-size: 14px;
        font-weight: 600;
        margin-right: 8px;
        display: inline-block;
    }
    .item-description {
        color: #656d76;
        font-size: 13px;
        line-height: 1.4;
        display: inline;
    }
    .item-actions {
        display: flex;
        gap: 8px;
        align-items: center;
        margin-top: 8px;
    }
    .copy-btn {
        background: #0073aa;
        color: #fff;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        font-weight: 500;
        transition: background-color 0.2s;
    }
    .copy-btn:hover { background: #005a87; }
    .copy-btn.copied { background: #00a32a; }
    .more-btn {
        background: transparent;
        color: #0073aa;
        border: 1px solid #0073aa;
        padding: 6px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        transition: all 0.2s;
    }
    .more-btn:hover { background: #0073aa; color: #fff; }
    .item-details {
        display: none;
        margin-top: 16px;
        padding: 16px;
        background: #f8f9fa;
        border-radius: 4px;
        border-left: 4px solid #0073aa;
        width: 100%;
    }
    .item-details.expanded { display: block; }
    .detail-section { margin: 12px 0; }
    .detail-title { font-weight: 600; margin-bottom: 8px; color: #1e1e1e; }
    .param-item { background: #fff; padding: 8px 12px; margin: 4px 0; border-radius: 4px; font-size: 13px; }
    .param-name { font-family: monospace; color: #0969da; font-weight: 600; }
    .example-code {
        background: #fff;
        border: 1px solid #d1d9e0;
        border-radius: 4px;
        padding: 8px 12px;
        margin: 4px 0;
        display: block;
        font-family: 'Courier New', monospace;
        font-size: 12px;
        color: #1f2937;
    }
    </style>
    <?php
}

/**
 * Scripts for the management admin page
 */
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
 * Shortcodes section renderer (extracted from displayShortcodesPage)
 */
function renderShortcodesSection(): void {
    ?>
    <!-- SHORTCODES SECTION -->
    <div class="management-section">
        <h2 class="section-header">📋 Shortcody Simple LMS</h2>

        <!-- Lesson Video URL (for Elementor/Bricks) -->
        <div class="item-container">
            <div class="item-header">
                <div class="item-code">[simple_lms_lesson_video_url]</div>
                <div class="item-description">URL wideo lekcji do użycia w widżetach Elementor/Bricks</div>
            </div>
            <div class="item-actions">
                <button class="copy-btn" onclick="copyToClipboard(this, '[simple_lms_lesson_video_url]')">Kopiuj</button>
                <button class="more-btn" onclick="toggleDetails(this, 'details-video-url')">więcej</button>
            </div>
            <div id="details-video-url" class="item-details">
                <div class="detail-section">
                    <div class="detail-title">Parametry:</div>
                    <div class="param-item"><span class="param-name">lesson_id</span> - ID lekcji (domyślnie: bieżący post)</div>
                </div>
                <div class="detail-section">
                    <div class="detail-title">Przykłady:</div>
                    <code class="example-code">[simple_lms_lesson_video_url]</code>
                    <code class="example-code">[simple_lms_lesson_video_url lesson_id="123"]</code>
                </div>
                <div class="detail-section">
                    <div class="detail-title">Użycie w Elementor/Bricks:</div>
                    <code class="example-code">1. Dodaj widżet Video<br>2. W Source/URL wklej: [simple_lms_lesson_video_url]<br>3. Widget automatycznie wykryje typ wideo</code>
                </div>
            </div>
        </div>

        <!-- Lesson Files -->
        <div class="item-container">
            <div class="item-header">
                <div class="item-code">[simple_lms_lesson_files]</div>
                <div class="item-description">Lista plików do pobrania dla lekcji</div>
            </div>
            <div class="item-actions">
                <button class="copy-btn" onclick="copyToClipboard(this, '[simple_lms_lesson_files]')">Kopiuj</button>
                <button class="more-btn" onclick="toggleDetails(this, 'details-files')">więcej</button>
            </div>
            <div id="details-files" class="item-details">
                <div class="detail-section">
                    <div class="detail-title">Parametry:</div>
                    <div class="param-item"><span class="param-name">lesson_id</span> - ID lekcji (domyślnie: bieżący post)</div>
                    <div class="param-item"><span class="param-name">show_download</span> - pokaż linki do pobrania (1/0)</div>
                </div>
                <div class="detail-section">
                    <div class="detail-title">Przykłady:</div>
                    <code class="example-code">[simple_lms_lesson_files]</code>
                    <code class="example-code">[simple_lms_lesson_files show_download="1"]</code>
                </div>
            </div>
        </div>

        <!-- Course Content -->
        <div class="item-container">
            <div class="item-header">
                <div class="item-code">[simple_lms_course_content]</div>
                <div class="item-description">Pełna zawartość kursu z edytora WYSIWYG (autodetect z lekcji)</div>
            </div>
            <div class="item-actions">
                <button class="copy-btn" onclick="copyToClipboard(this, '[simple_lms_course_content]')">Kopiuj</button>
                <button class="more-btn" onclick="toggleDetails(this, 'details-course-content')">więcej</button>
            </div>
            <div id="details-course-content" class="item-details">
                <div class="detail-section">
                    <div class="detail-title">Parametry:</div>
                    <div class="param-item"><span class="param-name">course_id</span> - ID kursu (autodetect z bieżącej lekcji/modułu)</div>
                </div>
                <div class="detail-section">
                    <div class="detail-title">Przykłady:</div>
                    <code class="example-code">[simple_lms_course_content]</code>
                    <code class="example-code">[simple_lms_course_content course_id="123"]</code>
                </div>
                <div class="detail-section">
                    <div class="detail-title">Użycie na szablonie lekcji:</div>
                    <code class="example-code">&lt;div class="course-description"&gt;
    &lt;h3&gt;O tym kursie&lt;/h3&gt;
    [simple_lms_course_content]
&lt;/div&gt;</code>
                </div>
            </div>
        </div>

        <!-- Course Navigation -->
        <div class="item-container">
            <div class="item-header">
                <div class="item-code">[simple_lms_course_navigation]</div>
                <div class="item-description">Nawigacja kursu z akordeonami (modułami i lekcjami)</div>
            </div>
            <div class="item-actions">
                <button class="copy-btn" onclick="copyToClipboard(this, '[simple_lms_course_navigation]')">Kopiuj</button>
                <button class="more-btn" onclick="toggleDetails(this, 'details-nav')">więcej</button>
            </div>
            <div id="details-nav" class="item-details">
                <div class="detail-section">
                    <div class="detail-title">Parametry:</div>
                    <div class="param-item"><span class="param-name">course_id</span> - ID kursu (autodetect z bieżącej lekcji)</div>
                    <div class="param-item"><span class="param-name">show_progress</span> - pokazuj kółeczka statusu (1/0)</div>
                </div>
                <div class="detail-section">
                    <div class="detail-title">Przykłady:</div>
                    <code class="example-code">[simple_lms_course_navigation]</code>
                    <code class="example-code">[simple_lms_course_navigation show_progress="1"]</code>
                </div>
            </div>
        </div>

        <!-- Course Overview -->
        <div class="item-container">
            <div class="item-header">
                <div class="item-code">[simple_lms_course_overview]</div>
                <div class="item-description">Przegląd kursu bez akordeonów (pełna lista modułów i lekcji)</div>
            </div>
            <div class="item-actions">
                <button class="copy-btn" onclick="copyToClipboard(this, '[simple_lms_course_overview]')">Kopiuj</button>
                <button class="more-btn" onclick="toggleDetails(this, 'details-overview')">więcej</button>
            </div>
            <div id="details-overview" class="item-details">
                <div class="detail-section">
                    <div class="detail-title">Parametry:</div>
                    <div class="param-item"><span class="param-name">course_id</span> - ID kursu (autodetect)</div>
                    <div class="param-item"><span class="param-name">show_progress</span> - pokazuj kółeczka statusu (1/0)</div>
                </div>
                <div class="detail-section">
                    <div class="detail-title">Przykłady:</div>
                    <code class="example-code">[simple_lms_course_overview]</code>
                    <code class="example-code">[simple_lms_course_overview show_progress="1"]</code>
                </div>
            </div>
        </div>

        <!-- Previous/Next Lesson -->
        <div class="item-container">
            <div class="item-header">
                <div class="item-code">[simple_lms_previous_lesson]</div>
                <div class="item-description">Przycisk poprzedniej lekcji</div>
            </div>
            <div class="item-actions">
                <button class="copy-btn" onclick="copyToClipboard(this, '[simple_lms_previous_lesson]')">Kopiuj</button>
                <button class="more-btn" onclick="toggleDetails(this, 'details-prev')">więcej</button>
            </div>
            <div id="details-prev" class="item-details">
                <div class="detail-section">
                    <div class="detail-title">Parametry:</div>
                    <div class="param-item"><span class="param-name">text</span> - tekst przycisku (domyślnie: "Poprzednia lekcja")</div>
                </div>
                <div class="detail-section">
                    <div class="detail-title">Przykłady:</div>
                    <code class="example-code">[simple_lms_previous_lesson]</code>
                    <code class="example-code">[simple_lms_previous_lesson text="← Poprzednia"]</code>
                </div>
            </div>
        </div>

        <div class="item-container">
            <div class="item-header">
                <div class="item-code">[simple_lms_next_lesson]</div>
                <div class="item-description">Przycisk następnej lekcji</div>
            </div>
            <div class="item-actions">
                <button class="copy-btn" onclick="copyToClipboard(this, '[simple_lms_next_lesson]')">Kopiuj</button>
                <button class="more-btn" onclick="toggleDetails(this, 'details-next')">więcej</button>
            </div>
            <div id="details-next" class="item-details">
                <div class="detail-section">
                    <div class="detail-title">Parametry:</div>
                    <div class="param-item"><span class="param-name">text</span> - tekst przycisku (domyślnie: "Następna lekcja")</div>
                </div>
                <div class="detail-section">
                    <div class="detail-title">Przykłady:</div>
                    <code class="example-code">[simple_lms_next_lesson]</code>
                    <code class="example-code">[simple_lms_next_lesson text="Następna →"]</code>
                </div>
            </div>
        </div>

        <!-- Lesson Complete Toggle -->
        <div class="item-container">
            <div class="item-header">
                <div class="item-code">[simple_lms_lesson_complete_toggle]</div>
                <div class="item-description">Przycisk oznaczania lekcji jako ukończonej/nieukończonej</div>
            </div>
            <div class="item-actions">
                <button class="copy-btn" onclick="copyToClipboard(this, '[simple_lms_lesson_complete_toggle]')">Kopiuj</button>
                <button class="more-btn" onclick="toggleDetails(this, 'details-toggle')">więcej</button>
            </div>
            <div id="details-toggle" class="item-details">
                <div class="detail-section">
                    <div class="detail-title">Parametry:</div>
                    <div class="param-item"><span class="param-name">lesson_id</span> - ID lekcji (domyślnie: bieżący post)</div>
                </div>
                <div class="detail-section">
                    <div class="detail-title">Przykłady:</div>
                    <code class="example-code">[simple_lms_lesson_complete_toggle]</code>
                    <code class="example-code">[simple_lms_lesson_complete_toggle lesson_id="123"]</code>
                </div>
            </div>
        </div>

        <!-- Basic Info Shortcodes -->
        <div class="item-container">
            <div class="item-header">
                <div class="item-code">[simple_lms_lesson_title]</div>
                <div class="item-description">Tytuł bieżącej lekcji</div>
            </div>
            <div class="item-actions">
                <button class="copy-btn" onclick="copyToClipboard(this, '[simple_lms_lesson_title]')">Kopiuj</button>
                <button class="more-btn" onclick="toggleDetails(this, 'details-title')">więcej</button>
            </div>
            <div id="details-title" class="item-details">
                <div class="detail-section">
                    <div class="detail-title">Parametry:</div>
                    <div class="param-item"><span class="param-name">lesson_id</span> - ID lekcji (domyślnie: bieżący post)</div>
                </div>
                <div class="detail-section">
                    <div class="detail-title">Przykłady:</div>
                    <code class="example-code">[simple_lms_lesson_title]</code>
                    <code class="example-code">[simple_lms_lesson_title lesson_id="123"]</code>
                </div>
            </div>
        </div>

        <!-- Lesson Module Title -->
        <div class="item-container">
            <div class="item-header">
                <div class="item-code">[simple_lms_lesson_module_title]</div>
                <div class="item-description">Tytuł modułu do którego należy bieżąca lekcja</div>
            </div>
            <div class="item-actions">
                <button class="copy-btn" onclick="copyToClipboard(this, '[simple_lms_lesson_module_title]')">Kopiuj</button>
                <button class="more-btn" onclick="toggleDetails(this, 'details-module-title')">więcej</button>
            </div>
            <div id="details-module-title" class="item-details">
                <div class="detail-section">
                    <div class="detail-title">Parametry:</div>
                    <div class="param-item"><span class="param-name">lesson_id</span> - ID lekcji (domyślnie: bieżący post)</div>
                    <div class="param-item"><span class="param-name">wrapper</span> - tag HTML (domyślnie: span)</div>
                    <div class="param-item"><span class="param-name">class</span> - klasa CSS (domyślnie: simple-lms-module-title)</div>
                </div>
                <div class="detail-section">
                    <div class="detail-title">Przykłady:</div>
                    <code class="example-code">[simple_lms_lesson_module_title]</code>
                    <code class="example-code">[simple_lms_lesson_module_title wrapper="h3"]</code>
                </div>
            </div>
        </div>

        <!-- Course Title -->
        <div class="item-container">
            <div class="item-header">
                <div class="item-code">[simple_lms_course_title]</div>
                <div class="item-description">Tytuł kursu (autodetect z lekcji/modułu lub konkretny ID)</div>
            </div>
            <div class="item-actions">
                <button class="copy-btn" onclick="copyToClipboard(this, '[simple_lms_course_title]')">Kopiuj</button>
                <button class="more-btn" onclick="toggleDetails(this, 'details-course-title')">więcej</button>
            </div>
            <div id="details-course-title" class="item-details">
                <div class="detail-section">
                    <div class="detail-title">Parametry:</div>
                    <div class="param-item"><span class="param-name">course_id</span> - ID kursu (autodetect z bieżącej lekcji/modułu)</div>
                </div>
                <div class="detail-section">
                    <div class="detail-title">Przykłady:</div>
                    <code class="example-code">[simple_lms_course_title]</code>
                    <code class="example-code">[simple_lms_course_title course_id="123"]</code>
                </div>
                <div class="detail-section">
                    <div class="detail-title">Użycie na szablonie lekcji:</div>
                    <code class="example-code">&lt;p&gt;&lt;strong&gt;Kurs:&lt;/strong&gt; [simple_lms_course_title]&lt;/p&gt;</code>
                </div>
            </div>
        </div>

        <!-- Access Control -->
        <div class="item-container">
            <div class="item-header">
                <div class="item-code">[simple_lms_access_control]</div>
                <div class="item-description">Kontrola dostępu do treści na podstawie ról użytkownika</div>
            </div>
            <div class="item-actions">
                <button class="copy-btn" onclick="copyToClipboard(this, '[simple_lms_access_control access=&quot;with&quot;]Treść dla użytkowników z dostępem[/simple_lms_access_control]')">Kopiuj</button>
                <button class="more-btn" onclick="toggleDetails(this, 'details-access')">więcej</button>
            </div>
            <div id="details-access" class="item-details">
                <div class="detail-section">
                    <div class="detail-title">Parametry:</div>
                    <div class="param-item"><span class="param-name">access</span> - "with" (z dostępem) lub "without" (bez dostępu)</div>
                    <div class="param-item"><span class="param-name">course_id</span> - ID kursu (autodetect)</div>
                </div>
                <div class="detail-section">
                    <div class="detail-title">Przykłady:</div>
                    <code class="example-code">[simple_lms_access_control access="with"]Treść dla użytkowników z dostępem[/simple_lms_access_control]</code>
                    <code class="example-code">[simple_lms_access_control access="without"]Kup kurs aby uzyskać dostęp[/simple_lms_access_control]</code>
                </div>
            </div>
        </div>

        <!-- Purchase Button -->
        <div class="item-container">
            <div class="item-header">
                <div class="item-code">[simple_lms_purchase_button]</div>
                <div class="item-description">Przycisk zakupu kursu z integracją WooCommerce</div>
            </div>
            <div class="item-actions">
                <button class="copy-btn" onclick="copyToClipboard(this, '[simple_lms_purchase_button]')">Kopiuj</button>
                <button class="more-btn" onclick="toggleDetails(this, 'details-purchase')">więcej</button>
            </div>
            <div id="details-purchase" class="item-details">
                <div class="detail-section">
                    <div class="detail-title">Parametry:</div>
                    <div class="param-item"><span class="param-name">course_id</span> - ID kursu (autodetect)</div>
                    <div class="param-item"><span class="param-name">text</span> - tekst przycisku (domyślnie: "Kup kurs")</div>
                    <div class="param-item"><span class="param-name">class</span> - klasy CSS (domyślnie: "button wc-forward")</div>
                    <div class="param-item"><span class="param-name">debug</span> - tryb debugowania ("1" aby włączyć)</div>
                </div>
                <div class="detail-section">
                    <div class="detail-title">Funkcjonalność:</div>
                    <div class="param-item">Automatycznie pobiera powiązany produkt WooCommerce</div>
                    <div class="param-item">Wyświetla cenę kursu</div>
                    <div class="param-item">Prowadzi do strony produktu (nie dodaje bezpośrednio do koszyka)</div>
                    <div class="param-item">Sprawdza dostępność produktu</div>
                    <div class="param-item">Zielone tło z niebieską czcionką (style Simple LMS)</div>
                </div>
                <div class="detail-section">
                    <div class="detail-title">Przykłady:</div>
                    <code class="example-code">[simple_lms_purchase_button]</code>
                    <code class="example-code">[simple_lms_purchase_button text="Kup teraz"]</code>
                    <code class="example-code">[simple_lms_purchase_button course_id="123"]</code>
                    <code class="example-code">[simple_lms_purchase_button debug="1"]</code>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * CSS classes section renderer (extracted from displayShortcodesPage)
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
                    <div class="param-item">Dodaj tę klasę do kontenera w Elementorze zawierającego treść kursu/lekcji</div>
                    <div class="param-item">Kontener będzie widoczny tylko dla użytkowników z odpowiednimi rolami</div>
                </div>
                <div class="detail-section">
                    <div class="detail-title">Przykład HTML:</div>
                    <code class="example-code">&lt;div class="simple-lms-with-access"&gt;Treść dla użytkowników z dostępem&lt;/div&gt;</code>
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