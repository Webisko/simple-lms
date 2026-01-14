<?php
/**
 * Module Title Dynamic Tag
 * Displays the title of the current module
 *
 * @package SimpleLMS\Elementor
 */

namespace SimpleLMS\Elementor\Tags;

use Elementor\Core\DynamicTags\Tag;
use Elementor\Controls_Manager;
use SimpleLMS\Elementor\Elementor_Dynamic_Tags;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Module Title Tag
 */
class Module_Title_Tag extends Tag {

    /**
     * Get tag name
     */
    public function get_name(): string {
        return 'simple-lms-module-title';
    }

    /**
     * Get tag title
     */
    public function get_title(): string {
        return __('Module Title', 'simple-lms');
    }

    /**
     * Get tag group
     */
    public function get_group(): string {
        return 'simple-lms';
    }

    /**
     * Get tag categories
     */
    public function get_categories(): array {
        return [
            \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY,
            \Elementor\Modules\DynamicTags\Module::URL_CATEGORY,
        ];
    }

    /**
     * Register controls
     */
    protected function register_controls(): void {
        // Module ID override
        $this->add_control(
            'module_id',
            [
                'label' => __('Module ID (optional)', 'simple-lms'),
                'type' => Controls_Manager::NUMBER,
                'description' => __('Leave empty to use current context', 'simple-lms'),
            ]
        );

        // Link to module
        $this->add_control(
            'link',
            [
                'label' => __('Link to Module', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => '',
                'return_value' => 'yes',
            ]
        );

        // Fallback text
        $this->add_control(
            'fallback_text',
            [
                'label' => __('Fallback Text', 'simple-lms'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'description' => __('Text to show if no module is found', 'simple-lms'),
            ]
        );
    }

    /**
     * Render tag output
     */
    public function render(): void {
        $settings = $this->get_settings();
        
        // Get module ID from settings or context
        $module_id = !empty($settings['module_id']) 
            ? absint($settings['module_id']) 
            : Elementor_Dynamic_Tags::get_current_module_id();

        if (!$module_id) {
            echo esc_html($settings['fallback_text'] ?? '');
            return;
        }

        $module = get_post($module_id);
        if (!$module || $module->post_type !== 'module') {
            echo esc_html($settings['fallback_text'] ?? '');
            return;
        }

        $title = get_the_title($module);

        // Output with or without link
        if ($settings['link'] === 'yes') {
            $url = get_permalink($module_id);
            printf(
                '<a href="%s">%s</a>',
                esc_url($url),
                esc_html($title)
            );
        } else {
            echo esc_html($title);
        }
    }

    /**
     * Render for URL category
     */
    public function render_url(): string {
        $settings = $this->get_settings();
        
        $module_id = !empty($settings['module_id']) 
            ? absint($settings['module_id']) 
            : Elementor_Dynamic_Tags::get_current_module_id();

        if (!$module_id) {
            return '';
        }

        return get_permalink($module_id);
    }
}
