<?php
namespace SimpleLMS\Elementor\Widgets;

/**
 * Lesson Attachments Widget
 * Displays lesson downloadable files with icons
 *
 * @package SimpleLMS\Elementor
 */

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use SimpleLMS\Elementor\Elementor_Dynamic_Tags;

if (!defined('ABSPATH')) {
    exit;
}

// Ensure Elementor is loaded
if (!class_exists('\Elementor\Widget_Base')) {
    return;
}

/**
 * Lesson Attachments Widget
 */
class Lesson_Attachments_Widget extends Widget_Base {

    /**
     * Get widget name
     */
    public function get_name(): string {
        return 'simple-lms-lesson-attachments';
    }

    /**
     * Get widget title
     */
    public function get_title(): string {
        return __('Lesson attachments', 'simple-lms');
    }

    /**
     * Get widget icon
     */
    public function get_icon(): string {
        return 'eicon-download-button';
    }

    /**
     * Get widget categories
     */
    public function get_categories(): array {
        return ['simple-lms'];
    }

    /**
     * Get widget keywords
     */
    public function get_keywords(): array {
        return ['lesson', 'attachments', 'files', 'download', 'lesson', 'załączniki', 'pliki', 'pobierz'];
    }

    /**
     * Register widget controls
     */
    protected function register_controls(): void {
        // Content section
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Settings', 'simple-lms'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'lesson_id',
            [
                'label' => __('ID lessons (opcjonalne)', 'simple-lms'),
                'type' => Controls_Manager::NUMBER,
                'default' => '',
                'description' => __('Leave empty to automatically detect current lesson', 'simple-lms'),
            ]
        );

        $this->add_control(
            'layout',
            [
                'label' => __('Layout', 'simple-lms'),
                'type' => Controls_Manager::SELECT,
                'default' => 'list',
                'options' => [
                    'list' => __('Lista', 'simple-lms'),
                    'grid' => __('Siatka', 'simple-lms'),
                    'cards' => __('Karty', 'simple-lms'),
                ],
            ]
        );

        $this->add_responsive_control(
            'columns',
            [
                'label' => __('Number of columns', 'simple-lms'),
                'type' => Controls_Manager::SELECT,
                'default' => '2',
                'options' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ],
                'condition' => [
                    'layout' => ['grid', 'cards'],
                ],
            ]
        );

        $this->add_control(
            'show_icon',
            [
                'label' => __('Show icon typu pliku', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'show_size',
            [
                'label' => __('Show file size', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'show_download_button',
            [
                'label' => __('Show Download button', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'download_text',
            [
                'label' => __('Button text', 'simple-lms'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Pobierz', 'simple-lms'),
                'condition' => [
                    'show_download_button' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'empty_message',
            [
                'label' => __('Message when no files', 'simple-lms'),
                'type' => Controls_Manager::TEXT,
                'default' => __('No attachments dla tej lessons.', 'simple-lms'),
            ]
        );

        $this->end_controls_section();

        // Style section - Container
        $this->start_controls_section(
            'container_style_section',
            [
                'label' => __('Kontener', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'gap',
            [
                'label' => __('Spacing between elements', 'simple-lms'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                    'em' => [
                        'min' => 0,
                        'max' => 5,
                    ],
                    'rem' => [
                        'min' => 0,
                        'max' => 5,
                    ],
                ],
                'default' => [
                    'size' => 15,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-attachments-list' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style section - Items
        $this->start_controls_section(
            'item_style_section',
            [
                'label' => __('Elementy', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'item_bg_color',
            [
                'label' => __('Background color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#f5f5f5',
                'selectors' => [
                    '{{WRAPPER}} .attachment-item' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'item_hover_bg_color',
            [
                'label' => __('Background color (hover)', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .attachment-item:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'item_Padding',
            [
                'label' => __('Padding', 'simple-lms'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', 'rem'],
                'default' => [
                    'top' => '15',
                    'right' => '20',
                    'bottom' => '15',
                    'left' => '20',
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .attachment-item' => 'Padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'item_border_radius',
            [
                'label' => __('Border radius', 'simple-lms'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem'],
                'selectors' => [
                    '{{WRAPPER}} .attachment-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'item_border',
                'selector' => '{{WRAPPER}} .attachment-item',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'item_shadow',
                'selector' => '{{WRAPPER}} .attachment-item',
            ]
        );

        $this->end_controls_section();

        // Style section - Icon
        $this->start_controls_section(
            'icon_style_section',
            [
                'label' => __('Ikona', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_icon' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'icon_size',
            [
                'label' => __('Rozmiar ikony', 'simple-lms'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 20,
                        'max' => 80,
                    ],
                    'em' => [
                        'min' => 1,
                        'max' => 5,
                    ],
                    'rem' => [
                        'min' => 1,
                        'max' => 5,
                    ],
                ],
                'default' => [
                    'size' => 40,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .attachment-icon' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'icon_color',
            [
                'label' => __('Icon color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#2196F3',
                'selectors' => [
                    '{{WRAPPER}} .attachment-icon' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'icon_spacing',
            [
                'label' => __('Spacing od tekstu', 'simple-lms'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                    'em' => [
                        'min' => 0,
                        'max' => 5,
                    ],
                    'rem' => [
                        'min' => 0,
                        'max' => 5,
                    ],
                ],
                'default' => [
                    'size' => 15,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .attachment-icon' => 'margin-right: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style section - Text
        $this->start_controls_section(
            'text_style_section',
            [
                'label' => __('Tekst', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'filename_color',
            [
                'label' => __('Kolor nazwy pliku', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .attachment-name' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'filename_typography',
                'label' => __('Typografia nazwy', 'simple-lms'),
                'selector' => '{{WRAPPER}} .attachment-name',
            ]
        );

        $this->add_control(
            'filesize_color',
            [
                'label' => __('Kolor rozmiaru pliku', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#999999',
                'selectors' => [
                    '{{WRAPPER}} .attachment-size' => 'color: {{VALUE}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'filesize_typography',
                'label' => __('Typografia rozmiaru', 'simple-lms'),
                'selector' => '{{WRAPPER}} .attachment-size',
            ]
        );

        $this->end_controls_section();

        // Style section - Button
        $this->start_controls_section(
            'button_style_section',
            [
                'label' => __('Przycisk', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_download_button' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'button_typography',
                'selector' => '{{WRAPPER}} .attachment-download-btn',
            ]
        );

        $this->add_responsive_control(
            'button_width',
            [
                'label' => __('Width przycisku', 'simple-lms'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'auto'],
                'range' => [
                    'px' => [
                        'min' => 50,
                        'max' => 500,
                    ],
                    '%' => [
                        'min' => 10,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'unit' => 'auto',
                ],
                'selectors' => [
                    '{{WRAPPER}} .attachment-download-btn' => 'width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'button_Padding',
            [
                'label' => __('Padding', 'simple-lms'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', 'rem'],
                'default' => [
                    'top' => '8',
                    'right' => '16',
                    'bottom' => '8',
                    'left' => '16',
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .attachment-download-btn' => 'Padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'button_border_radius',
            [
                'label' => __('Border radius', 'simple-lms'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem'],
                'selectors' => [
                    '{{WRAPPER}} .attachment-download-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->start_controls_tabs('button_style_tabs');

        // Normal state
        $this->start_controls_tab(
            'button_normal_tab',
            [
                'label' => __('Normalny', 'simple-lms'),
            ]
        );

        $this->add_control(
            'button_color',
            [
                'label' => __('Text color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .attachment-download-btn' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_bg_color',
            [
                'label' => __('Background color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#2196F3',
                'selectors' => [
                    '{{WRAPPER}} .attachment-download-btn' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        // Hover state
        $this->start_controls_tab(
            'button_hover_tab',
            [
                'label' => __('Hover', 'simple-lms'),
            ]
        );

        $this->add_control(
            'button_hover_color',
            [
                'label' => __('Text color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .attachment-download-btn:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_hover_bg_color',
            [
                'label' => __('Background color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .attachment-download-btn:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    /**
     * Render widget output
     */
    protected function render(): void {
        $settings = $this->get_settings_for_display();

        // Get lesson ID
        $lesson_id = !empty($settings['lesson_id']) 
            ? absint($settings['lesson_id']) 
            : Elementor_Dynamic_Tags::get_current_lesson_id();

        if (!$lesson_id) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="elementor-alert elementor-alert-warning">';
                echo esc_html__('Cannot detect lesson. Make sure the widget is used on a lesson page.', 'simple-lms');
                echo '</div>';
            }
            return;
        }

        // Get attachments
        $attachments = get_post_meta($lesson_id, 'lesson_attachments', true);
        
        if (empty($attachments) || !is_array($attachments)) {
            echo '<div class="simple-lms-no-attachments" style="Padding: 20px; text-align: center; color: #999;">';
            echo esc_html($settings['empty_message']);
            echo '</div>';
            return;
        }

        $layout = $settings['layout'];
        $columns = $settings['columns'] ?? '2';
        $show_icon = $settings['show_icon'] === 'yes';
        $show_size = $settings['show_size'] === 'yes';
        $show_button = $settings['show_download_button'] === 'yes';

        // Container classes
        $container_class = 'simple-lms-attachments-list layout-' . $layout;
        if (in_array($layout, ['grid', 'cards'])) {
            $container_class .= ' columns-' . $columns;
        }

        echo '<div class="' . esc_attr($container_class) . '" style="display: ' . ($layout === 'list' ? 'flex' : 'grid') . '; flex-direction: ' . ($layout === 'list' ? 'column' : 'row') . ';">';

        foreach ($attachments as $attachment_id) {
            $file_url = wp_get_attachment_url($attachment_id);
            $file_name = basename(get_attached_file($attachment_id));
            $file_size = size_format(filesize(get_attached_file($attachment_id)));
            $file_ext = strtoupper(pathinfo($file_name, PATHINFO_EXTENSION));

            echo '<div class="attachment-item" style="display: flex; align-items: center; transition: all 0.3s ease;">';

            // Icon
            if ($show_icon) {
                $icon = $this->get_file_icon($file_ext);
                echo '<span class="attachment-icon" style="flex-shrink: 0;">' . $icon . '</span>';
            }

            // Info
            echo '<div class="attachment-info" style="flex: 1; min-width: 0;">';
            echo '<div class="attachment-name" style="font-weight: 600; margin-bottom: 4px; word-break: break-word;">' . esc_html($file_name) . '</div>';
            if ($show_size) {
                echo '<div class="attachment-size" style="font-size: 0.9em;">' . esc_html($file_size) . '</div>';
            }
            echo '</div>';

            // Download button
            if ($show_button) {
                echo '<a href="' . esc_url($file_url) . '" class="attachment-download-btn" download style="display: inline-block; text-decoration: none; white-space: nowrap; transition: all 0.3s ease; border: none; cursor: pointer;">';
                echo esc_html($settings['download_text']);
                echo '</a>';
            }

            echo '</div>';
        }

        echo '</div>';

        // Add grid styles
        if (in_array($layout, ['grid', 'cards'])) {
            ?>
            <style>
                .simple-lms-attachments-list.layout-grid.columns-<?php echo esc_attr($columns); ?>,
                .simple-lms-attachments-list.layout-cards.columns-<?php echo esc_attr($columns); ?> {
                    grid-template-columns: repeat(<?php echo esc_attr($columns); ?>, 1fr);
                }
                @media (max-width: 768px) {
                    .simple-lms-attachments-list.layout-grid,
                    .simple-lms-attachments-list.layout-cards {
                        grid-template-columns: 1fr;
                    }
                }
            </style>
            <?php
        }
    }

    /**
     * Get file icon based on extension
     */
    private function get_file_icon($extension): string {
        $icons = [
            'PDF' => '📄',
            'DOC' => '📝',
            'DOCX' => '📝',
            'XLS' => '📊',
            'XLSX' => '📊',
            'PPT' => '📊',
            'PPTX' => '📊',
            'ZIP' => '🗜️',
            'RAR' => '🗜️',
            'MP3' => '🎵',
            'MP4' => '🎬',
            'JPG' => '🖼️',
            'JPEG' => '🖼️',
            'PNG' => '🖼️',
            'GIF' => '🖼️',
            'TXT' => '📃',
        ];

        return $icons[$extension] ?? '📎';
    }

    /**
     * Render widget output in the editor
     */
    protected function content_template(): void {
        ?>
        <#
        var layout = settings.layout;
        var showIcon = settings.show_icon === 'yes';
        var showSize = settings.show_size === 'yes';
        var showButton = settings.show_download_button === 'yes';
        var containerClass = 'simple-lms-attachments-list layout-' + layout;
        
        // Demo data
        var demoFiles = [
            { name: 'Materiały-do-lessons.pdf', size: '2.5 MB', icon: '📄' },
            { name: 'Prezentacja.pptx', size: '1.8 MB', icon: '📊' },
            { name: 'Zadanie-domowe.docx', size: '456 KB', icon: '📝' }
        ];
        #>
        <div class="{{{containerClass}}}" style="display: flex; flex-direction: column; gap: 15px;">
            <# _.each(demoFiles, function(file) { #>
                <div class="attachment-item" style="display: flex; align-items: center; background-color: #f5f5f5; Padding: 15px 20px; border-radius: 4px;">
                    <# if (showIcon) { #>
                        <span class="attachment-icon" style="font-size: 40px; margin-right: 15px;">{{{file.icon}}}</span>
                    <# } #>
                    <div class="attachment-info" style="flex: 1;">
                        <div class="attachment-name" style="font-weight: 600; margin-bottom: 4px;">{{{file.name}}}</div>
                        <# if (showSize) { #>
                            <div class="attachment-size" style="font-size: 0.9em; color: #999;">{{{file.size}}}</div>
                        <# } #>
                    </div>
                    <# if (showButton) { #>
                        <span class="attachment-download-btn" style="display: inline-block; Padding: 8px 16px; background-color: #2196F3; color: #fff; border-radius: 4px;">
                            {{{settings.download_text}}}
                        </span>
                    <# } #>
                </div>
            <# }); #>
        </div>
        <?php
    }
}
