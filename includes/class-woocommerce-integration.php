<?php
namespace SimpleLMS;

if (!defined('ABSPATH')) {
    exit;
}

// Import global WordPress and WooCommerce functions for namespaced context
use function \add_action;
use function \add_shortcode;
use function \admin_url;
use function \checked;
use function \class_exists;
use function \current_user_can;
use function \current_time;
use function \delete_post_meta;
use function \esc_attr;
use function \esc_attr__;
use function \esc_html;
use function \function_exists;
use function \get_permalink;
use function \get_post_meta;
use function \get_post_type;
use function \get_user_by;
use function \get_user_meta;
use function \get_userdata;
use function \in_array;
use function \is_user_logged_in;
use function \sanitize_text_field;
use function \selected;
use function \shortcode_atts;
use function \absint;
use function \update_post_meta;
use function \update_user_meta;
use function \wp_die;
use function \wp_localize_script;
use function \wp_login_url;
use function \wp_redirect;
use function \wp_send_json;
use function \wp_verify_nonce;
use function \__;
// WooCommerce helpers
use function \wc_get_orders;
use function \wc_get_order;
use function \wc_get_product;
// Additional template/helper functions used later
use function \get_the_ID;
use function \is_singular;
use function \get_current_user_id;

/**
 * WooCommerce Integration for Simple LMS
 * 
 * Handles integration between courses and WooCommerce products
 */
class WooCommerce_Integration {

    /** @var Logger|null */
    private ?Logger $logger = null;
    /** @var Security_Service|null */
    private ?Security_Service $security = null;

    /**
     * Instance constructor (DI friendly)
     */
    public function __construct(?Logger $logger = null, ?Security_Service $security = null)
    {
        $this->logger = $logger;
        $this->security = $security;
    }

    /**
     * Instance registration (preferred path). Attaches all hooks.
     */
    public function register(): void
    {
        if (!self::is_woocommerce_active()) {
            if ($this->logger) {
                $this->logger->notice('WooCommerce inactive; integration disabled');
            }
            return;
        }

        // Product admin hooks
        add_action('woocommerce_product_options_general_product_data', [__CLASS__, 'add_course_product_fields']);
        add_action('woocommerce_process_product_meta', [__CLASS__, 'save_course_product_fields']);

        // Course admin hooks
        add_action('add_meta_boxes', [__CLASS__, 'add_course_product_metabox']);
        add_action('save_post', [__CLASS__, 'save_course_product_metabox']);

        // AJAX hooks
        add_action('wp_ajax_create_course_product', [__CLASS__, 'ajax_create_course_product']);
        add_action('wp_ajax_search_wc_products', [__CLASS__, 'ajax_search_wc_products']);
        add_action('wp_ajax_get_wc_product_details', [__CLASS__, 'ajax_get_wc_product_details']);
        add_action('wp_ajax_set_default_course_product', [__CLASS__, 'ajax_set_default_course_product']);
        add_action('wp_ajax_add_product_to_course', [__CLASS__, 'ajax_add_product_to_course']);
        add_action('wp_ajax_remove_product_from_course', [__CLASS__, 'ajax_remove_product_from_course']);

        // Order completion hooks
        add_action('woocommerce_order_status_completed', [__CLASS__, 'grant_course_access_on_order_complete']);
        add_action('woocommerce_payment_complete', [__CLASS__, 'grant_course_access_on_payment_complete']);
        add_action('woocommerce_order_status_changed', [__CLASS__, 'handle_order_status_change'], 10, 4);

        // Shortcodes
        add_shortcode('course_purchase_button', [__CLASS__, 'purchase_button_shortcode']);
        add_shortcode('simple_lms_purchase_url', [__CLASS__, 'purchase_url_shortcode']);

        // Course access control
        add_action('template_redirect', [__CLASS__, 'control_course_access']);

        if ($this->logger) {
            $this->logger->debug('WooCommerce integration hooks registered');
        }
    }

    /**
     * Internal logging helper for static contexts
     */
    private static function log(string $level, string $message, array $context = []): void
    {
        try {
            $container = ServiceContainer::getInstance();
            if ($container->has(Logger::class)) {
                /** @var Logger $logger */
                $logger = $container->get(Logger::class);
                if (method_exists($logger, $level)) {
                    $logger->{$level}($message, $context);
                } else {
                    $logger->info($message, $context);
                }
            }
        } catch (\Throwable $e) {
            // silent
        }
    }

    /**
     * Initialize WooCommerce integration
     * 
     * Sets up hooks for product/course linking, order completion,
     * and automatic course access granting upon purchase.
     * 
     * @return void
     */
    public static function init() {
        // Backward compatibility shim: prefer container-managed instance
        try {
            $container = ServiceContainer::getInstance();
            if ($container->has(self::class)) {
                $instance = $container->get(self::class);
                if (method_exists($instance, 'register')) {
                    $instance->register();
                    return;
                }
            }
        } catch (\Throwable $e) {
            // fall through to legacy static registration
        }

        // Legacy fallback
        $tmp = new self();
        $tmp->register();
    }

    /**
     * Validate admin AJAX requests for WooCommerce integration
     *
     * @param string $nonceAction Expected nonce action string
     * @param int|null $objectId Optional post ID to validate with edit_post capability
     * @param string $capability Fallback capability if no objectId is provided
     * @return array|null Error array with 'message' key on failure, null on success
     */
    private static function validateAjax(string $nonceAction, ?int $objectId = null, string $capability = 'edit_posts'): ?array {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field((string) $_POST['nonce']) : '';
        if (!$nonce || !wp_verify_nonce($nonce, $nonceAction)) {
            return ['message' => __('Invalid security token', 'simple-lms')];
        }

        if (!is_user_logged_in()) {
            return ['message' => __('You must be logged in', 'simple-lms')];
        }

        if ($objectId && !current_user_can('edit_post', $objectId)) {
            return ['message' => __('No permission to edit this element', 'simple-lms')];
        }

        if (!$objectId && $capability && !current_user_can($capability)) {
            return ['message' => __('No permission to perform this operation', 'simple-lms')];
        }

        return null;
    }

    /**
     * Check if user has active completed order for course
     */
    private static function user_has_active_course_order($user_id, $course_id) {
        $product_ids = get_post_meta($course_id, '_wc_product_ids', true);
        
        if (!is_array($product_ids) || empty($product_ids)) {
            return true; // If no products linked, rely on role check only
        }
        
        // Get user's orders
        $orders = wc_get_orders([
            'customer_id' => $user_id,
            'status' => 'completed',
            'limit' => -1
        ]);
        
        try {
            foreach ($orders as $order) {
                foreach ($order->get_items() as $item) {
                    if (in_array($item->get_product_id(), $product_ids)) {
                        return true; // Found completed order for this course
                    }
                }
            }
        } catch (\Throwable $e) {
            try {
                $container = ServiceContainer::getInstance();
                /** @var Logger $logger */
                $logger = $container->get(Logger::class);
                $logger->error('Error scanning orders for user {userId}, course {courseId}: {error}', ['userId' => $user_id, 'courseId' => $course_id, 'error' => $e]);
            } catch (\Throwable $t) {
                // fallback no-op
            }
        }
        
        return false; // No completed order found
    }

    /**
     * Check if WooCommerce is active
     * 
     * @return bool True if WooCommerce plugin is active and functional
     */
    public static function is_woocommerce_active() {
        return class_exists('WooCommerce') && function_exists('wc_get_product');
    }

    /**
     * Add course selection fields to WooCommerce product admin
     * 
     * Renders checkbox and dropdown to link products with courses.
     * Only available for virtual products.
     * 
     * @return void
     */
    public static function add_course_product_fields() {
        global $post;
        
        $current_course_id = get_post_meta($post->ID, '_course_id', true);
        $is_course_product = get_post_meta($post->ID, '_is_course_product', true);
        $is_virtual = get_post_meta($post->ID, '_virtual', true);
        
        // Localize data for admin JS (WooCommerce section)
        wp_localize_script('simple-lms-admin', 'simpleLMSWoo', [
            'currentCourseId' => $current_course_id ?: '',
            'editCourseText' => __('Edit Course', 'simple-lms'),
            'editCourseUrl' => admin_url('post.php?action=edit&post='),
        ]);

        // Log field render for diagnostics when verbose enabled
        try {
            $container = ServiceContainer::getInstance();
            /** @var Logger $logger */
            $logger = $container->get(Logger::class);
            $logger->debug('Render product course fields for product {productId}', ['productId' => $post->ID]);
        } catch (\Throwable $t) {}
        
        echo '<div class="options_group">';
        
        // Show checkbox only for virtual products
        if ($is_virtual === 'yes') {
            // Course product checkbox
            echo '<p class="form-field _is_course_product_field">';
            echo '<label for="_is_course_product">' . __('To jest produkt kursu', 'simple-lms') . '&nbsp;<span class="woocommerce-help-tip" data-tip="' . esc_attr__('Check if this product gives access to the course', 'simple-lms') . '"></span></label>';
            echo '<input type="checkbox" class="checkbox" name="_is_course_product" id="_is_course_product" value="yes"' . checked($is_course_product, 'yes', false) . ' />';
            echo '</p>';
            
            // Course selection dropdown - include current product ID to get its course
            $courses = self::get_available_courses($post->ID);
            $course_options = ['' => __('Select course...', 'simple-lms')];
            
            foreach ($courses as $course) {
                $course_options[$course->ID] = $course->post_title;
            }
            
            echo '<p class="form-field _course_id_field" id="_course_id_field">';
            echo '<label for="_course_id">' . __('Przypisany kurs', 'simple-lms') . '&nbsp;<span class="woocommerce-help-tip" data-tip="' . esc_attr__('Select course to which this product should give access', 'simple-lms') . '"></span></label>';
            echo '<select name="_course_id" id="_course_id" class="short">';
            foreach ($course_options as $value => $label) {
                echo '<option value="' . esc_attr($value) . '"' . selected($current_course_id, $value, false) . '>' . esc_html($label) . '</option>';
            }
            echo '</select>';
            echo '</p>';
            
            // Edit course button container - under the select field
            echo '<p class="form-field" id="edit-course-button-field" style="display: none;">';
            echo '<label>&nbsp;</label>'; // Empty label for alignment
            echo '<span id="edit-course-button-container"></span>';
            echo '</p>';
        } else {
            echo '<p class="form-field">';
            echo '<label>' . __('Settings kursu', 'simple-lms') . '</label>';
            echo '<em>' . __('Course settings are only available for virtual products.', 'simple-lms') . '</em>';
            echo '</p>';
        }
        
        echo '</div>';
        
        // CSS for field styling (inline styles removed; moved to admin-style.css if needed)
        ?>
        <style type="text/css">
        /* Course field styling */
        #_course_id_field {
            <?php echo ($is_course_product !== 'yes') ? 'display: none;' : ''; ?>
        }
        #edit-course-button-field {
            <?php echo (!$current_course_id || $is_course_product !== 'yes') ? 'display: none;' : ''; ?>
        }
        /* Make select field same width as price fields */
        #_course_id {
            width: auto !important;
            min-width: 200px !important;
        }
        /* Align checkbox properly */
        ._is_course_product_field .checkbox {
            margin-left: 0 !important;
        }
        /* Better tooltip positioning */
        .woocommerce-help-tip {
            vertical-align: middle !important;
            margin-left: 2px !important;
        }
        </style>
        <?php
    }

    /**
     * Save course product fields
     */
    public static function save_course_product_fields($post_id) {
        $is_course_product = isset($_POST['_is_course_product']) ? 'yes' : 'no';
        update_post_meta($post_id, '_is_course_product', $is_course_product);
        
        // Get the old course assignment to clean up
        $old_course_id = get_post_meta($post_id, '_course_id', true);
        
        if ($is_course_product === 'yes' && isset($_POST['_course_id'])) {
            $new_course_id = absint($_POST['_course_id']);
            
            if ($new_course_id) {
                // Remove old relationship if different course selected
                if ($old_course_id && $old_course_id != $new_course_id) {
                    // Remove product from old course's product list
                    $old_product_ids = get_post_meta($old_course_id, '_wc_product_ids', true);
                    if (is_array($old_product_ids)) {
                        $old_product_ids = array_diff($old_product_ids, [$post_id]);
                        update_post_meta($old_course_id, '_wc_product_ids', $old_product_ids);
                    }
                    // Clean up old single product meta if exists
                    delete_post_meta($old_course_id, '_wc_product_id');
                }
                
                // Save new product->course relationship
                update_post_meta($post_id, '_course_id', $new_course_id);
                
                // Add product to new course's product list
                $product_ids = get_post_meta($new_course_id, '_wc_product_ids', true);
                if (!is_array($product_ids)) {
                    $product_ids = [];
                }
                if (!in_array($post_id, $product_ids)) {
                    $product_ids[] = $post_id;
                    update_post_meta($new_course_id, '_wc_product_ids', $product_ids);
                }
            } else {
                // No course selected, remove old relationships
                if ($old_course_id) {
                    // Remove product from old course's product list
                    $old_product_ids = get_post_meta($old_course_id, '_wc_product_ids', true);
                    if (is_array($old_product_ids)) {
                        $old_product_ids = array_diff($old_product_ids, [$post_id]);
                        update_post_meta($old_course_id, '_wc_product_ids', $old_product_ids);
                    }
                    // Clean up old single product meta if exists
                    delete_post_meta($old_course_id, '_wc_product_id');
                }
                delete_post_meta($post_id, '_course_id');
            }
        } else {
            // Remove relationships if unchecked or no course selected
            if ($old_course_id) {
                // Remove product from old course's product list
                $old_product_ids = get_post_meta($old_course_id, '_wc_product_ids', true);
                if (is_array($old_product_ids)) {
                    $old_product_ids = array_diff($old_product_ids, [$post_id]);
                    update_post_meta($old_course_id, '_wc_product_ids', $old_product_ids);
                }
                // Clean up old single product meta if exists
                delete_post_meta($old_course_id, '_wc_product_id');
            }
            delete_post_meta($post_id, '_course_id');
        }
    }

    /**
     * Get courses that don't have assigned products
     */
    public static function get_available_courses($exclude_product_id = null) {
        $args = [
            'post_type'      => 'course',
            'post_status'    => ['publish', 'draft'],
            'posts_per_page' => -1
        ];
        
        $courses = get_posts($args);
        
        return $courses;
    }

    /**
     * Add product metabox to course admin
     */
    public static function add_course_product_metabox() {
        add_meta_box(
            'course_woocommerce_product',
            __('Products WooCommerce', 'simple-lms'),
            [__CLASS__, 'course_product_metabox_content'],
            'course',
            'side',
            'low'
        );
    }

    /**
     * Content for course product metabox
     */
    public static function course_product_metabox_content($post) {
        wp_nonce_field('course_product_metabox', 'course_product_metabox_nonce');
        
        $product_ids = get_post_meta($post->ID, '_wc_product_ids', true);
        if (!is_array($product_ids)) {
            $product_ids = [];
            // Migracja z starego systemu
            $old_product_id = get_post_meta($post->ID, '_wc_product_id', true);
            if ($old_product_id) {
                $product_ids = [$old_product_id];
                update_post_meta($post->ID, '_wc_product_ids', $product_ids);
                delete_post_meta($post->ID, '_wc_product_id');
            }
        }
        
        echo '<div style="Padding: 0;">';
        echo '<h4>' . __('Przypisane Products:', 'simple-lms') . '</h4>';
        
        // Kafelki produktów - układ pionowy dla sidebar
        echo '<div id="course-products-grid" style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px;">';
        
        $default_product_id = get_post_meta($post->ID, '_default_wc_product_id', true);
        
        if (!empty($product_ids)) {
            // Sort products so default is first
            if ($default_product_id) {
                $default_key = array_search($default_product_id, $product_ids);
                if ($default_key !== false) {
                    unset($product_ids[$default_key]);
                    array_unshift($product_ids, $default_product_id);
                }
            }
            
            foreach ($product_ids as $index => $product_id) {
                $product = wc_get_product($product_id);
                if ($product) {
                    $is_default = ($default_product_id == $product_id);
                    
                    echo '<div class="course-product-card' . ($is_default ? ' default-product' : '') . '" style="background: #fff; border: 2px solid ' . ($is_default ? '#007cba' : '#ddd') . '; border-radius: 8px; Padding: 12px; position: relative; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: flex; flex-direction: column;" data-product-id="' . $product_id . '">';
                    
                    // Badge domyślnego produktu
                    if ($is_default) {
                        echo '<div style="position: absolute; top: -6px; left: 10px; background: #007cba; color: white; Padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: bold; z-index: 1;">' . 
                             __('DEFAULT', 'simple-lms') . '</div>';
                    }
                    
                    // Przycisk usuwania - tylko czerwona ikona X (większa)
                    echo '<button type="button" class="remove-product" style="position: absolute; top: 4px; right: 4px; background: none; color: #dc3545; border: none; width: 24px; height: 24px; cursor: pointer; font-size: 16px; line-height: 1; font-weight: bold;" title="' . __('Remove produkt', 'simple-lms') . '">×</button>';
                    
                    // Górna część - informacje o produkcie
                    echo '<div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">';
                    
                    // Miniaturka
                    echo '<div style="flex: 0 0 50px;">';
                    if ($product->get_image_id()) {
                        echo '<img src="' . wp_get_attachment_image_src($product->get_image_id(), 'thumbnail')[0] . '" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">';
                    } else {
                        echo '<div style="width: 50px; height: 50px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #999; font-size: 20px;">📦</div>';
                    }
                    echo '</div>';
                    
                    // Informacje produktu
                    echo '<div style="flex: 1; min-width: 0;">';
                    echo '<h4 style="margin: 0 0 4px 0; font-size: 13px; line-height: 1.2; color: #333;">' . esc_html($product->get_name()) . '</h4>';
                    
                    // Cena
                    $regular_price = $product->get_regular_price();
                    $sale_price = $product->get_sale_price();
                    
                    echo '<div style="margin-bottom: 4px; font-weight: bold; font-size: 12px;">';
                    if ($sale_price && $sale_price !== $regular_price) {
                        echo '<span style="text-decoration: line-through; color: #999;">' . 
                             wc_price($regular_price) . '</span> ';
                        echo '<span style="color: #e74c3c;">' . 
                             wc_price($sale_price) . '</span>';
                    } else {
                        echo '<span style="color: #2c5aa0;">' . wc_price($regular_price) . '</span>';
                    }
                    echo '</div>';
                    
                    // Status
                    $status = $product->get_status();
                    $status_color = ($status === 'publish') ? '#28a745' : '#ffc107';
                    $status_text = ($status === 'publish') ? __('Opublikowany', 'simple-lms') : __('Szkic', 'simple-lms');
                    
                    echo '<div style="font-size: 11px; color: ' . $status_color . '; font-weight: 500;">' . $status_text . '</div>';
                    echo '</div>';
                    echo '</div>';
                    
                    // Dolna część - przyciski akcji
                    echo '<div style="display: flex; gap: 8px; margin-top: auto;">';
                    
                    if (!$is_default) {
                        echo '<button type="button" class="set-default-product button button-primary" data-product-id="' . $product_id . '" style="flex: 1; font-size: 11px; Padding: 4px 8px;">' . 
                             __('Set default', 'simple-lms') . '</button>';
                    }
                    
                    echo '<a href="' . get_edit_post_link($product_id) . '" target="_blank" class="button button-small" style="flex: 1; font-size: 11px; Padding: 4px 8px; text-decoration: none; text-align: center;">' . 
                         __('Edytuj', 'simple-lms') . '</a>';
                    
                    echo '</div>';
                    
                    echo '</div>';
                }
            }
        } else {
            echo '<div style="text-align: center; Padding: 20px; background: #f9f9f9; border: 2px dashed #ddd; border-radius: 8px; color: #666;">';
            echo '<div style="font-size: 32px; margin-bottom: 10px;">📦</div>';
            echo '<h4 style="margin: 0 0 8px 0; color: #666; font-size: 14px;">' . __('No products', 'simple-lms') . '</h4>';
            echo '<p style="margin: 0; font-size: 12px;">' . __('Add Producty WooCommerce do kursu.', 'simple-lms') . '</p>';
            echo '</div>';
        }
        
        echo '</div>';
        
        // Przyciski akcji - kompaktowe dla sidebar
        echo '<div style="margin-bottom: 15px; display: flex; flex-direction: column; gap: 8px;">';
        echo '<button type="button" class="button button-primary" id="create-product" style="width: 100%; text-align: center;">' . 
             __('Create new product', 'simple-lms') . '</button>';
        echo '<button type="button" class="button" id="add-existing-product" style="width: 100%; text-align: center;">' . 
             __('Add existing product', 'simple-lms') . '</button>';
        echo '</div>';
        
        // Modal do wybierania produktów
        echo '<div id="product-selection-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">';
        echo '<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; Padding: 20px; border-radius: 5px; max-width: 600px; width: 90%; max-height: 80%; overflow-y: auto;">';
        echo '<h3>' . __('Select product WooCommerce', 'simple-lms') . '</h3>';
        echo '<div id="product-search">';
        echo '<input type="text" id="product-search-input" placeholder="' . __('Search products...', 'simple-lms') . '" style="width: 100%; Padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 3px;">';
        echo '<div id="product-search-results" style="max-height: 300px; overflow-y: auto;"></div>';
        echo '</div>';
        echo '<div style="text-align: right; margin-top: 15px;">';
        echo '<button type="button" class="button" id="close-product-modal">' . __('Cancel', 'simple-lms') . '</button>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        // Hidden field dla produktów
        echo '<input type="hidden" id="course_product_ids" name="course_product_ids" value="' . esc_attr(json_encode($product_ids)) . '">';
        
        echo '</div>';
        
        ?>
        <script type="text/javascript">
        (function($) {
            
            
            // Define ajaxurl for WordPress admin
            if (typeof ajaxurl === 'undefined') {
                var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
            }
            
            // DEFINICJE FUNKCJI - na początku
            
            // Funkcja ładowania produktów
            function loadProducts(search = '') {
                
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'search_wc_products',
                        search: search,
                        nonce: '<?php echo wp_create_nonce('search_wc_products'); ?>'
                    },
                    beforeSend: function() {
                        
                        $('#product-list').html('<p>Ładowanie produktów...</p>');
                    },
                    success: function(response) {
                        
                        if (response.success) {
                            var html = '';
                            $.each(response.data, function(index, product) {
                                html += '<div class="product-item" style="Padding: 10px; border-bottom: 1px solid #ddd; cursor: pointer;" data-product-id="' + product.id + '">';
                                html += '<strong>' + product.name + '</strong>';
                                if (product.price) {
                                    html += ' - ' + product.price;
                                }
                                html += '</div>';
                            });
                            $('#product-list').html(html);
                            
                        } else {
                            
                            $('#product-list').html('<p><?php echo esc_js(__('No products found', 'simple-lms')); ?></p>');
                        }
                    },
                    error: function(xhr, status, error) {
                        
                        $('#product-list').html('<p><?php echo esc_js(__('Error loading products', 'simple-lms')); ?></p>');
                    }
                });
            }
            
            // Funkcja wyświetlania powiadomień
            function showNotice(message, type) {
                var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
                var notice = $('<div class="notice ' + noticeClass + ' is-dismissible" style="margin: 10px 0;"><p>' + message + '</p></div>');
                $('#course-products-grid').before(notice);
                
                setTimeout(function() {
                    notice.fadeOut(function() {
                        notice.remove();
                    });
                }, 3000);
            }
            
            // Sprawdzenie stanu pustej listy
            function checkEmptyState() {
                var hasProducts = $('.course-product-card').length > 0;
                
                if (!hasProducts) {
                    $('#course-products-grid').html('<div style="grid-column: 1 / -1; text-align: center; Padding: 40px; background: #f9f9f9; border: 2px dashed #ddd; border-radius: 8px; color: #666;">' +
                        '<div style="font-size: 48px; margin-bottom: 15px;">📦</div>' +
                        '<h3 style="margin: 0 0 10px 0; color: #666;"><?php echo esc_js(__('No assigned products', 'simple-lms')); ?></h3>' +
                        '<p style="margin: 0;"><?php echo esc_js(__('Add WooCommerce Products to enable sales of this course.', 'simple-lms')); ?></p>' +
                        '</div>');
                }
            }
            
            // Funkcja ustawiania domyślnego produktu
            function setDefaultProduct(productId) {
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'set_default_course_product',
                        course_id: <?php echo $post->ID; ?>,
                        product_id: productId,
                        nonce: '<?php echo wp_create_nonce('set_default_course_product'); ?>'
                    },
                    success: function(response) {
                        
                        if (response.success) {
                            
                            
                            // NAJPIERW usuń wszystkie badge'y i przywróć przyciski
                            $('.course-product-card').each(function() {
                                var $card = $(this);
                                
                                
                                // Remove klasy i style domyślnego produktu
                                $card.removeClass('default-product');
                                $card.css('border-color', '#ddd');
                                
                                // Remove wszystkie możliwe badge'y (różne selektory)
                                $card.find('.default-badge').remove();
                                $card.find('[class*="default-badge"]').remove();
                                $card.children().filter(function() {
                                    return $(this).text().trim() === '<?php echo esc_js(__('DEFAULT', 'simple-lms')); ?>';
                                }).remove();
                                
                                // Dodaj przycisk "Set as default" jeśli go nie ma
                                var setDefaultBtn = $card.find('.set-default-product');
                                if (setDefaultBtn.length === 0) {
                                    
                                    var newBtn = $('<button type="button" class="set-default-product button button-primary" style="font-size: 12px; Padding: 6px 12px;">' + 
                                                '<?php echo esc_js(__('Set as default', 'simple-lms')); ?></button>');
                                    newBtn.attr('data-product-id', $card.data('product-id'));
                                    // Dodaj na końcu (po przycisku "Edytuj produkt")
                                    $card.find('div[style*="margin-top: auto"]').append(newBtn);
                                }
                            });
                            
                            // TERAZ ustaw nowy domyślny produkt
                            
                            var newDefaultCard = $('.course-product-card[data-product-id="' + productId + '"]');
                            newDefaultCard.addClass('default-product');
                            newDefaultCard.css('border-color', '#007cba');
                            
                            // Remove przycisk "Set as default" z nowej domyślnej karty
                            newDefaultCard.find('.set-default-product').remove();
                            
                            
                            // Dodaj badge domyślnego (sprawdzamy czy już nie ma)
                            newDefaultCard.find('.default-badge').remove(); // Remove na wszelki wypadek
                            var badge = $('<div class="default-badge" style="position: absolute; top: -8px; left: 15px; background: #007cba; color: white; Padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold; z-index: 10;">' + 
                                       '<?php echo esc_js(__('DEFAULT', 'simple-lms')); ?></div>');
                            newDefaultCard.prepend(badge);
                            
                            
                            // Show message sukcesu
                            showNotice('<?php echo esc_js(__('Product has been set as default', 'simple-lms')); ?>', 'success');
                        } else {
                            showNotice('<?php echo esc_js(__('Error setting default product', 'simple-lms')); ?>', 'error');
                        }
                    },
                    error: function() {
                        showNotice('<?php echo esc_js(__('An error occurred while communicating with the server', 'simple-lms')); ?>', 'error');
                    }
                });
            }
            
            function updateProductIds() {
                var ids = [];
                $('.course-product-card').each(function() {
                    ids.push($(this).data('product-id').toString());
                });
                $('#course_product_ids').val(JSON.stringify(ids));
                return ids;
            }
            
            // EVENT HANDLERY - po definicjach funkcji
            
            // Tworzenie nowego produktu - używamy delegacji zdarzeń
            $(document).on('click', '#create-product', function() {
                
                var button = $(this);
                button.prop('disabled', true).text('<?php echo esc_js(__('Tworzenie...', 'simple-lms')); ?>');
            
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'create_course_product',
                        course_id: <?php echo $post->ID; ?>,
                        nonce: '<?php echo wp_create_nonce('create_course_product'); ?>'
                    },
                    success: function(response) {
                        if (response.success && response.data.product_id) {
                            // Redirect to product edit page
                            window.open('<?php echo admin_url('post.php?action=edit&post='); ?>' + response.data.product_id, '_blank');
                        } else {
                            alert('<?php echo esc_js(__('Error creating product:', 'simple-lms')); ?> ' + response.data);
                        }
                        button.prop('disabled', false).text('<?php echo esc_js(__('Create new product', 'simple-lms')); ?>');
                    },
                    error: function() {
                        alert('<?php echo esc_js(__('An error occurred while communicating with the server', 'simple-lms')); ?>');
                        button.prop('disabled', false).text('<?php echo esc_js(__('Create new product', 'simple-lms')); ?>');
                    }
                });
            });
            
            // Otwieranie modalu wyboru produktu - używamy delegacji zdarzeń
            $(document).on('click', '#add-existing-product', function() {
                
                
                // Upewnij się że modal jest widoczny
                var modal = $('#product-selection-modal');
                modal.css({
                    'display': 'block',
                    'position': 'fixed',
                    'top': '0',
                    'left': '0',
                    'width': '100%',
                    'height': '100%',
                    'background': 'rgba(0,0,0,0.8)',
                    'z-index': '999999'
                });
                
                
                loadProducts();
                
            });
            
            // Zamykanie modalu - używamy delegacji zdarzeń
            $(document).on('click', '#close-product-modal, #product-selection-modal', function(e) {
                if (e.target === this) {
                    $('#product-selection-modal').hide();
                }
            });
            
            // Wyszukiwanie produktów
            var searchTimeout;
            $('#product-search-input').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    loadProducts($('#product-search-input').val());
                }, 300);
            });
            
            // Usuwanie produktu z listy
            $(document).on('click', '.remove-product', function() {
                if (confirm('<?php echo esc_js(__('Are you sure you want to remove this product from the course?', 'simple-lms')); ?>')) {
                    var productId = $(this).closest('.course-product-card').data('product-id');
                    var $card = $(this).closest('.course-product-card');
                    
                    // Remove z interfejsu
                    $card.remove();
                    updateProductIds();
                    checkEmptyState();
                    
                    // Remove z bazy danych
                    removeProductFromCourse(productId);
                }
            });
            
            // Ustawianie domyślnego produktu
            $(document).on('click', '.set-default-product', function() {
                var productId = $(this).data('product-id');
                
                setDefaultProduct(productId);
            });
            
            // Edytowanie produktu - otwiera WooCommerce w nowej karcie
            $(document).on('click', '.edit-product', function() {
                var productId = $(this).data('product-id');
                var editUrl = '<?php echo admin_url('post.php?post='); ?>' + productId + '&action=edit';
                window.open(editUrl, '_blank');
            });
            
            // Dodawanie produktów z modalu
            $(document).on('click', '.product-item', function() {
                var productId = $(this).data('product-id');
                
                // Sprawdź czy produkt już nie jest dodany
                var existingCard = $('.course-product-card[data-product-id="' + productId + '"]');
                if (existingCard.length > 0) {
                    alert('<?php echo esc_js(__('This product is already added to the course', 'simple-lms')); ?>');
                    return;
                }
                
                // Używaj tej samej funkcji co dla nowych produktów
                addProductToCourse(productId);
                
                // Zamknij modal
                $('#product-selection-modal').hide();
            });
            
            // Pobieranie aktualnych ID produktów
            function getCurrentProductIds() {
                var ids = [];
                $('.course-product-card').each(function() {
                    ids.push($(this).data('product-id').toString());
                });
                return ids;
            }
            
            // Funkcja ustawiania domyślnego produktu
            function setDefaultProduct(productId) {
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'set_default_course_product',
                        course_id: <?php echo $post->ID; ?>,
                        product_id: productId,
                        nonce: '<?php echo wp_create_nonce('set_default_course_product'); ?>'
                    },
                    success: function(response) {
                        
                        if (response.success) {
                            
                            
                            // NAJPIERW usuń wszystkie badge'y i przywróć przyciski
                            $('.course-product-card').each(function() {
                                var $card = $(this);
                                
                                
                                // Remove klasy i style domyślnego produktu
                                $card.removeClass('default-product');
                                $card.css('border-color', '#ddd');
                                
                                // Remove wszystkie możliwe badge'y (różne selektory)
                                $card.find('.default-badge').remove();
                                $card.find('[class*="default-badge"]').remove();
                                $card.children().filter(function() {
                                    return $(this).text().trim() === '<?php echo esc_js(__('DEFAULT', 'simple-lms')); ?>';
                                }).remove();
                                
                                // Dodaj przycisk "Set as default" jeśli go nie ma
                                var setDefaultBtn = $card.find('.set-default-product');
                                if (setDefaultBtn.length === 0) {
                                    
                                    var newBtn = $('<button type="button" class="set-default-product button button-primary" style="font-size: 12px; Padding: 6px 12px;">' + 
                                                '<?php echo esc_js(__('Set as default', 'simple-lms')); ?></button>');
                                    newBtn.attr('data-product-id', $card.data('product-id'));
                                    // Dodaj na końcu (po przycisku "Edytuj produkt")
                                    $card.find('div[style*="margin-top: auto"]').append(newBtn);
                                }
                            });
                            
                            // TERAZ ustaw nowy domyślny produkt
                            
                            var newDefaultCard = $('.course-product-card[data-product-id="' + productId + '"]');
                            newDefaultCard.addClass('default-product');
                            newDefaultCard.css('border-color', '#007cba');
                            
                            // Remove przycisk "Set as default" z nowej domyślnej karty
                            newDefaultCard.find('.set-default-product').remove();
                            
                            
                            // Dodaj badge domyślnego (sprawdzamy czy już nie ma)
                            newDefaultCard.find('.default-badge').remove(); // Remove na wszelki wypadek
                            var badge = $('<div class="default-badge" style="position: absolute; top: -8px; left: 15px; background: #007cba; color: white; Padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold; z-index: 10;">' + 
                                       '<?php echo esc_js(__('DEFAULT', 'simple-lms')); ?></div>');
                            newDefaultCard.prepend(badge);
                            
                            
                            // Show message sukcesu
                            showNotice('<?php echo esc_js(__('Product has been set as default', 'simple-lms')); ?>', 'success');
                        } else {
                            showNotice('<?php echo esc_js(__('Error setting default product', 'simple-lms')); ?>', 'error');
                        }
                    },
                    error: function() {
                        showNotice('<?php echo esc_js(__('An error occurred while communicating with the server', 'simple-lms')); ?>', 'error');
                    }
                });
            }
            
            // Funkcja ładowania produktów
            function loadProducts(search = '') {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'search_wc_products',
                        search: search,
                        nonce: '<?php echo wp_create_nonce('search_wc_products'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var html = '';
                            var currentIds = getCurrentProductIds();
                            
                            $.each(response.data, function(index, product) {
                                if (currentIds.indexOf(product.id.toString()) === -1) {
                                    html += '<div class="product-search-item" style="Padding: 10px; border: 1px solid #ddd; margin-bottom: 5px; cursor: pointer;" data-product-id="' + product.id + '">';
                                    html += '<strong>' + product.name + '</strong><br>';
                                    html += '<small>ID: ' + product.id + ' | ' + product.price + '</small>';
                                    html += '</div>';
                                }
                            });
                            
                            if (html === '') {
                                html = '<p style="color: #666; font-style: italic;"><?php echo esc_js(__('No available products or all are already assigned.', 'simple-lms')); ?></p>';
                            }
                            
                            $('#product-search-results').html(html);
                        }
                    }
                });
            }
            
            // Dodawanie produktu do kursu
            $(document).on('click', '.product-search-item', function() {
                var productId = $(this).data('product-id');
                addProductToCourse(productId);
                $('#product-selection-modal').hide();
            });
            
            // Funkcja dodawania produktu
            function addProductToCourse(productId) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_wc_product_details',
                        product_id: productId,
                        nonce: '<?php echo wp_create_nonce('get_wc_product_details'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var product = response.data;
                            
                            // Sprawdź czy to pierwszy produkt
                            var isFirstProduct = $('.course-product-card').length === 0;
                            
                            // Nowy layout - pionowy, kompaktowy
                            var html = '<div class="course-product-card" style="background: #fff; border: 2px solid #ddd; border-radius: 8px; Padding: 12px; position: relative; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: flex; flex-direction: column;" data-product-id="' + product.id + '">';
                            
                            // Przycisk usuwania - tylko czerwona ikona X (większa)
                            html += '<button type="button" class="remove-product" style="position: absolute; top: 4px; right: 4px; background: none; color: #dc3545; border: none; width: 24px; height: 24px; cursor: pointer; font-size: 16px; line-height: 1; font-weight: bold;" title="<?php echo esc_js(__('Remove produkt', 'simple-lms')); ?>">×</button>';
                            
                            // Górna część - informacje o produkcie
                            html += '<div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">';
                            
                            // Miniaturka
                            html += '<div style="flex: 0 0 50px;">';
                            if (product.image) {
                                html += '<img src="' + product.image + '" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">';
                            } else {
                                html += '<div style="width: 50px; height: 50px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #999; font-size: 20px;">📦</div>';
                            }
                            html += '</div>';
                            
                            // Informacje produktu
                            html += '<div style="flex: 1; min-width: 0;">';
                            html += '<h4 style="margin: 0 0 4px 0; font-size: 13px; line-height: 1.2; color: #333;">' + product.name + '</h4>';
                            
                            // Cena
                            html += '<div style="margin-bottom: 4px; font-weight: bold; font-size: 12px;">';
                            html += '<span style="color: #2c5aa0;">' + product.price + '</span>';
                            html += '</div>';
                            
                            // Status
                            var statusColor = product.status === '<?php echo esc_js(__('Opublikowany', 'simple-lms')); ?>' ? '#28a745' : '#ffc107';
                            html += '<div style="font-size: 11px; color: ' + statusColor + '; font-weight: 500;">' + product.status + '</div>';
                            html += '</div>'; // koniec div informacji produktu
                            html += '</div>'; // koniec div górnej części
                            
                            // Przyciski akcji wyrównane do dołu
                            html += '<div style="display: flex; gap: 8px; margin-top: auto;">';
                            html += '<button type="button" class="set-default-product button button-primary" data-product-id="' + product.id + '" style="flex: 1; font-size: 11px; Padding: 4px 8px;"><?php echo esc_js(__('Set default', 'simple-lms')); ?></button>';
                            html += '<a href="' + product.edit_link + '" target="_blank" class="button button-small" style="flex: 1; text-align: center; font-size: 11px; Padding: 4px 8px; text-decoration: none;"><?php echo esc_js(__('Edytuj', 'simple-lms')); ?></a>';
                            html += '</div>';
                            html += '</div>';
                            
                            // Remove placeholder jeśli istnieje
                            $('#course-products-grid').find('[style*="grid-column: 1 / -1"]').remove();
                            
                            // Dodaj nowy kafelek
                            $('#course-products-grid').append(html);
                            
                            // Jeśli to pierwszy produkt, automatycznie ustaw jako domyślny
                            if (isFirstProduct) {
                                setDefaultProduct(product.id);
                            }
                            
                            updateProductIds();
                            
                            // Automatycznie zapisz produkt w bazie danych
                            saveProductToCourse(product.id);
                        }
                    }
                });
            }
            
            // Aktualizacja ukrytego pola z ID produktów
            function updateProductIds() {
                var ids = [];
                $('.course-product-card').each(function() {
                    ids.push($(this).data('product-id'));
                });
                $('#course_product_ids').val(JSON.stringify(ids));
            }
            
            // Pobieranie aktualnych ID produktów
            function getCurrentProductIds() {
                var ids = [];
                $('.course-product-card').each(function() {
                    ids.push($(this).data('product-id').toString());
                });
                return ids;
            }
            
            // Zapisywanie produktu w bazie danych
            function saveProductToCourse(productId) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'add_product_to_course',
                        course_id: <?php echo $post->ID; ?>,
                        product_id: productId,
                        nonce: '<?php echo wp_create_nonce('add_product_to_course'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Product saved successfully
                        } else {
                            
                            alert('Błąd zapisywania produktu: ' + response.data);
                        }
                    },
                    error: function() {
                        
                        alert('Wystąpił błąd podczas zapisywania produktu. Spróbuj ponownie.');
                    }
                });
            }
            
            // Usuwanie produktu z bazy danych
            function removeProductFromCourse(productId) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'remove_product_from_course',
                        course_id: <?php echo $post->ID; ?>,
                        product_id: productId,
                        nonce: '<?php echo wp_create_nonce('remove_product_from_course'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Product removed successfully
                        } else {
                            
                            alert('Błąd usuwania produktu: ' + response.data);
                        }
                    },
                    error: function() {
                        
                        alert('Wystąpił błąd podczas usuwania produktu. Spróbuj ponownie.');
                    }
                });
            }
            
        })(jQuery); // end IIFE
        </script>
        <?php
    }

    /**
     * Save course product metabox
     */
    public static function save_course_product_metabox($post_id) {
        if (!isset($_POST['course_product_metabox_nonce']) || 
            !wp_verify_nonce($_POST['course_product_metabox_nonce'], 'course_product_metabox')) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Zapisywanie nowej listy produktów
        if (isset($_POST['course_product_ids'])) {
            $product_ids = json_decode(stripslashes($_POST['course_product_ids']), true);
            
            if (is_array($product_ids)) {
                // Pobieranie starych produktów
                $old_product_ids = get_post_meta($post_id, '_wc_product_ids', true);
                if (!is_array($old_product_ids)) {
                    $old_product_ids = [];
                    // Migracja ze starego systemu
                    $old_product_id = get_post_meta($post_id, '_wc_product_id', true);
                    if ($old_product_id) {
                        $old_product_ids = [$old_product_id];
                        delete_post_meta($post_id, '_wc_product_id');
                    }
                }
                
                // Usuwanie powiązań dla produktów które zostały usunięte
                $removed_products = array_diff($old_product_ids, $product_ids);
                foreach ($removed_products as $removed_id) {
                    delete_post_meta($removed_id, '_course_id');
                    delete_post_meta($removed_id, '_is_course_product');
                }
                
                // Dodawanie powiązań dla nowych produktów
                $added_products = array_diff($product_ids, $old_product_ids);
                foreach ($added_products as $added_id) {
                    update_post_meta($added_id, '_course_id', $post_id);
                    update_post_meta($added_id, '_is_course_product', '1');
                }
                
                // Zapisywanie nowej listy
                update_post_meta($post_id, '_wc_product_ids', $product_ids);
            }
        }
        
        // Zapisywanie domyślnego produktu
        if (isset($_POST['default_wc_product_id'])) {
            $default_product_id = absint($_POST['default_wc_product_id']);
            
            if ($default_product_id > 0) {
                // Sprawdź czy wybrany produkt jest na liście produktów kursu
                $current_product_ids = get_post_meta($post_id, '_wc_product_ids', true);
                if (is_array($current_product_ids) && in_array($default_product_id, $current_product_ids)) {
                    update_post_meta($post_id, '_default_wc_product_id', $default_product_id);
                } else {
                    // Jeśli produkt nie jest na liście, usuń domyślny
                    delete_post_meta($post_id, '_default_wc_product_id');
                }
            } else {
                // Remove domyślny produkt jeśli wybrano "automatyczny"
                delete_post_meta($post_id, '_default_wc_product_id');
            }
        }
    }

    /**
     * AJAX: Create WooCommerce product for course
     */
    public static function ajax_create_course_product() {
        $course_id = absint($_POST['course_id']);
        if ($error = self::validateAjax('create_course_product', $course_id)) {
            wp_send_json_error($error);
            return;
        }
        if (!$course_id) {
            wp_send_json_error(__('Invalid course ID', 'simple-lms'));
        }
        
        $course = get_post($course_id);
        if (!$course || $course->post_type !== 'course') {
            wp_send_json_error(__('Course nie znaleziony', 'simple-lms'));
        }

        try {
            // Create WooCommerce product
            $product = new \WC_Product_Simple();
            $product->set_name($course->post_title);
            $product->set_description($course->post_content);
            $product->set_short_description($course->post_excerpt);
            $product->set_status('draft'); // Start as draft
            $product->set_catalog_visibility('visible');
            // Nie ustawiamy ceny - admin ustawi ją ręcznie
            $product->set_virtual(true); // Digital product
            $product->set_downloadable(false);
            $product->set_sold_individually(true); // Can't buy multiple
            
            // Copy featured image
            $thumbnail_id = get_post_thumbnail_id($course_id);
            if ($thumbnail_id) {
                $product->set_image_id($thumbnail_id);
            }
            
            $product_id = $product->save();
            
            if ($product_id) {
                // Set course product meta
                update_post_meta($product_id, '_is_course_product', 'yes');
                update_post_meta($product_id, '_course_id', $course_id);
                
                // Add to course products list
                $product_ids = get_post_meta($course_id, '_wc_product_ids', true);
                if (!is_array($product_ids)) {
                    $product_ids = [];
                }
                $product_ids[] = $product_id;
                update_post_meta($course_id, '_wc_product_ids', $product_ids);
                
                wp_send_json_success([
                    'product_id' => $product_id,
                    'edit_link' => get_edit_post_link($product_id)
                ]);
            } else {
                wp_send_json_error(__('Failed to create product', 'simple-lms'));
            }
            
        } catch (\Exception $e) {
            try {
                $container = ServiceContainer::getInstance();
                /** @var Logger $logger */
                $logger = $container->get(Logger::class);
                $logger->error('Error creating course product: {error}', ['error' => $e, 'courseId' => $course_id ?? null]);
            } catch (\Throwable $t) {}
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Grant course access when order is completed
     */
    public static function grant_course_access_on_order_complete($order_id) {
        self::process_course_access($order_id, 'grant');
    }

    /**
     * Grant course access when payment is completed
     */
    public static function grant_course_access_on_payment_complete($order_id) {
        self::process_course_access($order_id, 'grant');
    }

    /**
     * Handle order status changes
     */
    public static function handle_order_status_change($order_id, $old_status, $new_status, $order) {
        // Grant access only when order is completed
        if ($new_status === 'completed') {
            self::process_course_access($order_id, 'grant');
        } 
        // Revoke access for any other status if it was previously completed
        elseif ($old_status === 'completed' && in_array($new_status, ['cancelled', 'refunded', 'failed', 'pending', 'processing', 'on-hold'])) {
            self::process_course_access($order_id, 'revoke');
        }
        // Also revoke access when order goes from any status to cancelled/refunded/failed
        elseif (in_array($new_status, ['cancelled', 'refunded', 'failed'])) {
            self::process_course_access($order_id, 'revoke');
        }
    }

    /**
     * Process course access for order
     */
    private static function process_course_access($order_id, $action = 'grant') {
        $order = wc_get_order($order_id);
        if (!$order) {
            self::log('warning', 'Order not found for course access processing', ['orderId' => $order_id]);
            return;
        }
        
        $user_id = $order->get_user_id();
        if (!$user_id) {
            return; // Guest checkout - no access granted
        }
        
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return;
        }
        
        // Process each order item
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $is_course_product = get_post_meta($product_id, '_is_course_product', true);
            
            if ($is_course_product === 'yes') {
                $course_id = get_post_meta($product_id, '_course_id', true);
                if ($course_id) {
                    if ($action === 'grant') {
                        self::grant_user_course_access($user_id, $course_id);
                    } elseif ($action === 'revoke') {
                        self::revoke_user_course_access($user_id, $course_id);
                    }
                }
            }
        }
    }

    /**
     * Grant user access to course by adding user_meta tag
     * 
     * Assigns course access and initializes drip schedule start time.
     * Called automatically on WooCommerce order completion.
     * 
     * @param int $user_id User ID to grant access to
     * @param int $course_id Course ID to grant access for
     * @return bool True on success, false if user not found
     */
    public static function grant_user_course_access($user_id, $course_id) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }
        // Assign course access tag
        \SimpleLMS\simple_lms_assign_course_access_tag($user_id, $course_id);
        // Record access start for drip schedules if not set
        $key = 'simple_lms_course_access_start_' . (int)$course_id;
        if (!(int) get_user_meta($user_id, $key, true)) {
            update_user_meta($user_id, $key, current_time('timestamp', true)); // GMT
        }
        // Log the access grant
        self::log('info', 'Granted course access', ['userId' => $user_id, 'courseId' => $course_id]);
        return true;
    }

    /**
     * Revoke user access to course by removing user_meta tag
     */
    public static function revoke_user_course_access($user_id, $course_id) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }
        // Remove course access tag
        \SimpleLMS\simple_lms_remove_course_access_tag($user_id, $course_id);
        // Log the access revocation
        self::log('info', 'Revoked course access', ['userId' => $user_id, 'courseId' => $course_id]);
        return true;
    }

    /**
     * Purchase button shortcode
     */
    public static function purchase_button_shortcode($atts) {
        $atts = shortcode_atts([
            'course_id' => self::getCurrentCourseId(),
            'text' => __('Kup kurs', 'simple-lms'),
            'class' => 'button wc-forward',
            'debug' => '0'
        ], $atts);
        
        $course_id = absint($atts['course_id']);
        if (!$course_id) {
            return '';
        }

        // Debug information
        if ($atts['debug'] === '1') {
            $debug_info = '<div style="background: #fff3cd; border: 1px solid #ffeaa7; Padding: 10px; margin: 10px 0;">';
            $debug_info .= '<strong>Debug informacje:</strong><br>';
            $debug_info .= 'Bieżąca strona ID: ' . get_the_ID() . '<br>';
            $debug_info .= 'Typ postu: ' . get_post_type() . '<br>';
            $debug_info .= 'Znaleziony course_id: ' . $course_id . '<br>';
            $product_ids = get_post_meta($course_id, '_wc_product_ids', true);
            $debug_info .= 'Product IDs: ' . (is_array($product_ids) ? implode(', ', $product_ids) : 'brak') . '<br>';
            $debug_info .= '</div>';
            return $debug_info;
        }
        
        $product_ids = get_post_meta($course_id, '_wc_product_ids', true);
        if (!is_array($product_ids) || empty($product_ids)) {
            // Sprawdzenie czy istnieje stary system
            $old_product_id = get_post_meta($course_id, '_wc_product_id', true);
            if ($old_product_id) {
                $product_ids = [$old_product_id];
            } else {
                return '<p>' . __('This course is not available for purchase.', 'simple-lms') . '</p>';
            }
        }
        
        // Sprawdź czy kurs ma ustawiony domyślny produkt
        $default_product_id = get_post_meta($course_id, '_default_wc_product_id', true);
        $selected_product = null;
        
        if ($default_product_id && in_array($default_product_id, $product_ids)) {
            $selected_product = wc_get_product($default_product_id);
            if ($selected_product && $selected_product->get_status() === 'publish') {
                // Użyj domyślnego produktu
            } else {
                $selected_product = null;
            }
        }
        
        // Jeśli nie ma domyślnego produktu lub jest niedostępny, znajdź pierwszy dostępny
        if (!$selected_product) {
            foreach ($product_ids as $product_id) {
                $product = wc_get_product($product_id);
                if ($product && $product->get_status() === 'publish') {
                    $selected_product = $product;
                    break;
                }
            }
        }
        
        if (!$selected_product) {
            return '<p>' . __('This course is not available for purchase.', 'simple-lms') . '</p>';
        }
        
        $product_url = esc_url(get_permalink($selected_product->get_id()));
        $price = $selected_product->get_price_html();
        
        $output = '<div class="simple-lms-purchase-area">';
        $output .= '<div class="course-price">' . $price . '</div>';
        $output .= '<a href="' . $product_url . '" class="simple-lms-purchase-btn ' . esc_attr($atts['class']) . '">';
        $output .= esc_html($atts['text']) . '</a>';
        $output .= '</div>';
        
        return $output;
    }

    /**
     * Get current course ID - handles course, module, and lesson contexts
     * 
     * Resolves course ID from the current post, traversing parent relationships
     * for modules and lessons.
     * 
     * @return int Course ID or 0 if not in course context
     */
    public static function getCurrentCourseId(): int {
        $current_post_id = get_the_ID();
        $post_type = get_post_type($current_post_id);
        
        switch ($post_type) {
            case 'course':
                return $current_post_id;
                
            case 'module':
                $course_id = get_post_meta($current_post_id, 'parent_course', true);
                return (int) $course_id;
                
            case 'lesson':
                $module_id = get_post_meta($current_post_id, 'parent_module', true);
                if ($module_id) {
                    $course_id = get_post_meta($module_id, 'parent_course', true);
                    return (int) $course_id;
                }
                break;
        }
        
        return 0;
    }

    /**
     * Get purchase product URL for a course (uses default product or first available)
     * 
     * Returns WooCommerce product URL for purchasing course access.
     * Prioritizes default product, falls back to first linked product.
     * 
     * @param int $course_id Course ID
     * @return string Product permalink or empty string if no products linked
     */
    public static function get_purchase_url_for_course(int $course_id): string {
        if (!$course_id || !self::is_woocommerce_active()) {
            return '';
        }

        $product_ids = get_post_meta($course_id, '_wc_product_ids', true);
        if (!is_array($product_ids) || empty($product_ids)) {
            // Backward compatibility with old single product meta
            $old_product_id = get_post_meta($course_id, '_wc_product_id', true);
            if ($old_product_id) {
                $product_ids = [$old_product_id];
            } else {
                return '';
            }
        }

        // Try default product first
        $selected_product = null;
        $default_product_id = get_post_meta($course_id, '_default_wc_product_id', true);
        if ($default_product_id && in_array($default_product_id, $product_ids)) {
            $selected = wc_get_product($default_product_id);
            if ($selected && $selected->get_status() === 'publish') {
                $selected_product = $selected;
            }
        }

        // Fallback: first available published product
        if (!$selected_product) {
            foreach ($product_ids as $pid) {
                $product = wc_get_product($pid);
                if ($product && $product->get_status() === 'publish') {
                    $selected_product = $product;
                    break;
                }
            }
        }

        if (!$selected_product) {
            return '';
        }

        return esc_url(get_permalink($selected_product->get_id()));
    }

    /**
     * Get the selected WooCommerce product object for a course
     * Uses default product if set and published, otherwise first available published product
     */
    public static function get_selected_product_for_course(int $course_id) {
        if (!$course_id || !self::is_woocommerce_active()) {
            return null;
        }

        $product_ids = get_post_meta($course_id, '_wc_product_ids', true);
        if (!is_array($product_ids) || empty($product_ids)) {
            $old_product_id = get_post_meta($course_id, '_wc_product_id', true);
            if ($old_product_id) {
                $product_ids = [$old_product_id];
            } else {
                return null;
            }
        }

        $default_product_id = get_post_meta($course_id, '_default_wc_product_id', true);
        if ($default_product_id && in_array($default_product_id, $product_ids)) {
            $selected = wc_get_product($default_product_id);
            if ($selected && $selected->get_status() === 'publish') {
                return $selected;
            }
        }

        foreach ($product_ids as $pid) {
            $product = wc_get_product($pid);
            if ($product && $product->get_status() === 'publish') {
                return $product;
            }
        }

        return null;
    }

    /**
     * Shortcode that returns only the purchase URL for the current course context
     * Usage: [simple_lms_purchase_url] or [simple_lms_purchase_url course_id="123"]
     */
    public static function purchase_url_shortcode($atts): string {
        $atts = shortcode_atts([
            'course_id' => self::getCurrentCourseId(),
            'debug' => '0',
        ], $atts);

        $course_id = absint($atts['course_id']);
        if (!$course_id) {
            return '';
        }

        if ($atts['debug'] === '1') {
            $product_ids = get_post_meta($course_id, '_wc_product_ids', true);
            $debug = [
                'page_id' => get_the_ID(),
                'post_type' => get_post_type(),
                'course_id' => $course_id,
                'product_ids' => is_array($product_ids) ? implode(', ', $product_ids) : 'brak',
            ];
            return '<pre style="background:#f6f8fa;border:1px solid #e1e4e8;Padding:8px;">' . esc_html(print_r($debug, true)) . '</pre>';
        }

        return self::get_purchase_url_for_course($course_id) ?: '';
    }

    /**
     * Control course access on frontend
     */
    public static function control_course_access() {
        if (!is_singular('course')) {
            return;
        }
        
        $course_id = get_the_ID();
        $user_id = get_current_user_id();
        
        // Allow access for administrators and editors
        if (current_user_can('edit_posts')) {
            return;
        }
        
        // Check if user has access to course
        if (!self::user_has_course_access($user_id, $course_id)) {
            // Store the attempted course ID for redirect after purchase
            if ($user_id) {
                update_user_meta($user_id, '_attempted_course_access', $course_id);
            }
            
            // Don't use wp_die() - let Access_Control handle CSS classes
            // The Access_Control will add 'simple-lms-no-access' class to body
            return;
        }
    }

    /**
     * Check if user has access to course
     */
    public static function user_has_course_access($user_id, $course_id) {
        if (!$user_id || !$course_id) {
            return false;
        }
        // Use tag-based access control from Access_Control
        $fn = __NAMESPACE__ . '\\simple_lms_user_has_course_access';
        $has_tag_access = function_exists($fn) ? $fn($user_id, $course_id) : false;
        // Additionally check if user has an active (completed) order for this course (backward compatibility)
        $has_active_order = self::user_has_active_course_order($user_id, $course_id);
        // User must have tag-based access AND an active order (if product is linked)
        return $has_tag_access && $has_active_order;
    }

    /**
     * AJAX: Search WooCommerce products
     */
    public static function ajax_search_wc_products() {
        if ($error = self::validateAjax('search_wc_products', null, 'edit_posts')) {
            wp_send_json_error($error);
            return;
        }
        
        $search = sanitize_text_field($_POST['search'] ?? '');
        
        $args = [
            'post_type' => 'product',
            'post_status' => ['publish', 'draft'],
            'posts_per_page' => 20,
            'meta_query' => [
                [
                    'key' => '_is_course_product',
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ];
        
        if (!empty($search)) {
            $args['s'] = $search;
        }
        
        $products = get_posts($args);
        $result = [];
        
        foreach ($products as $product_post) {
            $product = wc_get_product($product_post->ID);
            if ($product) {
                $price = $product->get_price_html();
                if (empty($price)) {
                    $price = __('Brak ceny', 'simple-lms');
                }
                
                $result[] = [
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'price' => $price,
                    'status' => $product->get_status()
                ];
            }
        }
        
        wp_send_json_success($result);
    }

    /**
     * AJAX: Get WooCommerce product details
     */
    public static function ajax_get_wc_product_details() {
        if ($error = self::validateAjax('get_wc_product_details', null, 'edit_posts')) {
            wp_send_json_error($error);
            return;
        }
        
        $product_id = absint($_POST['product_id']);
        if (!$product_id) {
            wp_send_json_error(__('Invalid product ID', 'simple-lms'));
        }
        
        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error(__('Produkt nie znaleziony', 'simple-lms'));
        }
        
        $image_url = '';
        if ($product->get_image_id()) {
            $image_url = wp_get_attachment_image_src($product->get_image_id(), 'thumbnail')[0];
        }
        
        $price = $product->get_price_html();
        if (empty($price)) {
            $price = __('Brak ceny', 'simple-lms');
        }
        
        $status_label = $product->get_status() === 'publish' 
            ? __('Opublikowany', 'simple-lms') 
            : __('Szkic', 'simple-lms');
        
        $result = [
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'price' => $price,
            'status' => $status_label,
            'image' => $image_url,
            'edit_link' => get_edit_post_link($product_id)
        ];
        
        wp_send_json_success($result);
    }

    /**
     * AJAX: Set default course product
     */
    public static function ajax_set_default_course_product() {
        $course_id = absint($_POST['course_id']);
        $product_id = absint($_POST['product_id']);
        if ($error = self::validateAjax('set_default_course_product', $course_id)) {
            wp_send_json_error($error);
            return;
        }

        if (!$course_id || !$product_id) {
            wp_send_json_error(__('Invalid parameters', 'simple-lms'));
        }

        // Sprawdź czy produkt istnieje
        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error(__('Product does not exist', 'simple-lms'));
        }

        // Sprawdź czy produkt jest przypisany do kursu
        $course_products = get_post_meta($course_id, '_wc_product_ids', true);
        if (empty($course_products)) {
            $course_products = [];
        }
        
        if (is_string($course_products)) {
            $course_products = json_decode($course_products, true);
        }
        
        if (!in_array($product_id, $course_products)) {
            wp_send_json_error(__('Produkt nie jest przypisany do tego kursu', 'simple-lms'));
        }

        // Set default produkt
        update_post_meta($course_id, '_default_wc_product_id', $product_id);

        wp_send_json_success([
            'message' => __('Product has been set as default', 'simple-lms'),
            'product_id' => $product_id,
            'course_id' => $course_id
        ]);
    }

    /**
     * AJAX: Add product to course
     */
    public static function ajax_add_product_to_course() {
        $course_id = absint($_POST['course_id']);
        $product_id = absint($_POST['product_id']);
        if ($error = self::validateAjax('add_product_to_course', $course_id)) {
            wp_send_json_error($error);
            return;
        }

        if (!$course_id || !$product_id) {
            wp_send_json_error(__('Invalid parameters', 'simple-lms'));
        }

        // Sprawdź czy produkt istnieje
        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error(__('Product does not exist', 'simple-lms'));
        }

        // Pobierz aktualną listę produktów kursu
        $course_products = get_post_meta($course_id, '_wc_product_ids', true);
        if (empty($course_products)) {
            $course_products = [];
        }
        
        if (is_string($course_products)) {
            $course_products = json_decode($course_products, true);
        }

        // Sprawdź czy produkt już nie jest przypisany
        if (in_array($product_id, $course_products)) {
            wp_send_json_error(__('Product is already assigned to this course', 'simple-lms'));
        }

        // Add Product do listy
        $course_products[] = $product_id;
        update_post_meta($course_id, '_wc_product_ids', $course_products);

        // Ustaw relację w produkcie
        update_post_meta($product_id, '_course_id', $course_id);
        update_post_meta($product_id, '_is_course_product', 'yes');

        // Jeśli to pierwszy produkt, ustaw jako domyślny
        $default_product = get_post_meta($course_id, '_default_wc_product_id', true);
        if (empty($default_product)) {
            update_post_meta($course_id, '_default_wc_product_id', $product_id);
        }

        wp_send_json_success([
            'message' => __('Product has been added to the course', 'simple-lms'),
            'product_id' => $product_id,
            'course_id' => $course_id
        ]);
    }

    /**
     * AJAX: Remove product from course
     */
    public static function ajax_remove_product_from_course() {
        $course_id = absint($_POST['course_id']);
        $product_id = absint($_POST['product_id']);
        if ($error = self::validateAjax('remove_product_from_course', $course_id)) {
            wp_send_json_error($error);
            return;
        }

        if (!$course_id || !$product_id) {
            wp_send_json_error(__('Invalid parameters', 'simple-lms'));
        }

        // Pobierz aktualną listę produktów kursu
        $course_products = get_post_meta($course_id, '_wc_product_ids', true);
        if (empty($course_products)) {
            wp_send_json_error(__('No products assigned to course', 'simple-lms'));
        }
        
        if (is_string($course_products)) {
            $course_products = json_decode($course_products, true);
        }

        // Remove produkt z listy
        $course_products = array_values(array_diff($course_products, [$product_id]));
        update_post_meta($course_id, '_wc_product_ids', $course_products);

        // Remove relację z produktu
        delete_post_meta($product_id, '_course_id');
        delete_post_meta($product_id, '_is_course_product');

        // Jeśli usuwany produkt był domyślny, ustaw nowy domyślny
        $default_product = get_post_meta($course_id, '_default_wc_product_id', true);
        if ($default_product == $product_id) {
            if (!empty($course_products)) {
                // Ustaw pierwszy pozostały produkt jako domyślny
                update_post_meta($course_id, '_default_wc_product_id', $course_products[0]);
            } else {
                // Remove domyślny produkt jeśli nie ma więcej produktów
                delete_post_meta($course_id, '_default_wc_product_id');
            }
        }

        wp_send_json_success([
            'message' => __('Product was deleted z kursu', 'simple-lms'),
            'product_id' => $product_id,
            'course_id' => $course_id
        ]);
    }
}

// WooCommerce_Integration is now managed by ServiceContainer
// and conditionally registered in Plugin::registerCoreServices()
?>
