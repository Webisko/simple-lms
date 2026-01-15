<?php
/**
 * Lesson Video Player Widget
 * Displays lesson video with full styling control
 *
 * @package SimpleLMS\Elementor
 */

namespace SimpleLMS\Elementor\Widgets;

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
 * Lesson Video Player Widget
 */
class Lesson_Video_Widget extends Widget_Base {

    /**
     * Get widget name
     */
    public function get_name(): string {
        return 'simple-lms-lesson-video';
    }

    /**
     * Get widget title
     */
    public function get_title(): string {
        return __('Lesson video', 'simple-lms');
    }

    /**
     * Get widget icon
     */
    public function get_icon(): string {
        return 'eicon-play';
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
        return ['video', 'lesson', 'player', 'wideo', 'lesson', 'odtwarzacz', 'youtube', 'vimeo'];
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
            'video_source',
            [
                'label' => __('Video source', 'simple-lms'),
                'type' => Controls_Manager::SELECT,
                'default' => 'custom_field',
                'options' => [
                    'custom_field' => __('Z pola niestandardowego lessons', 'simple-lms'),
                    'custom_url' => __('Custom URL', 'simple-lms'),
                ],
            ]
        );

        $this->add_control(
            'custom_video_url',
            [
                'label' => __('Video URL', 'simple-lms'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => 'https://www.youtube.com/watch?v=...',
                'description' => __('Supports YouTube, Vimeo, MP4 files', 'simple-lms'),
                'condition' => [
                    'video_source' => 'custom_url',
                ],
            ]
        );

        $this->add_control(
            'poster_heading',
            [
                'label' => __('Video poster', 'simple-lms'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'show_poster',
            [
                'label' => __('Show poster', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'poster_source',
            [
                'label' => __('Poster source', 'simple-lms'),
                'type' => Controls_Manager::SELECT,
                'default' => 'featured',
                'options' => [
                    'featured' => __('Featured lesson thumbnail', 'simple-lms'),
                    'custom' => __('Custom obraz', 'simple-lms'),
                ],
                'condition' => [
                    'show_poster' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'custom_poster',
            [
                'label' => __('Select poster', 'simple-lms'),
                'type' => Controls_Manager::MEDIA,
                'default' => [
                    'url' => '',
                ],
                'condition' => [
                    'show_poster' => 'yes',
                    'poster_source' => 'custom',
                ],
            ]
        );

        $this->add_control(
            'show_play_icon',
            [
                'label' => __('Show icon Play', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'return_value' => 'yes',
                'condition' => [
                    'show_poster' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'play_icon',
            [
                'label' => __('Ikona Play', 'simple-lms'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-play-circle',
                    'library' => 'fa-solid',
                ],
                'condition' => [
                    'show_poster' => 'yes',
                    'show_play_icon' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'aspect_ratio',
            [
                'label' => __('Proporcje (aspect ratio)', 'simple-lms'),
                'type' => Controls_Manager::SELECT,
                'default' => '16-9',
                'options' => [
                    '16-9' => '16:9',
                    '4-3' => '4:3',
                    '1-1' => '1:1',
                    '21-9' => '21:9',
                    'custom' => __('Custom', 'simple-lms'),
                ],
            ]
        );

        $this->add_responsive_control(
            'custom_height',
            [
                'label' => __('Height', 'simple-lms'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'vh', '%'],
                'range' => [
                    'px' => [
                        'min' => 200,
                        'max' => 1000,
                    ],
                    'vh' => [
                        'min' => 20,
                        'max' => 100,
                    ],
                    '%' => [
                        'min' => 20,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'size' => 500,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-video-wrapper' => 'height: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'aspect_ratio' => 'custom',
                ],
            ]
        );

        $this->add_control(
            'autoplay',
            [
                'label' => __('Automatyczne odtwarzanie', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => '',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'muted',
            [
                'label' => __('Wyciszone', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => '',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'controls',
            [
                'label' => __('Show controls', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'show_when_no_video',
            [
                'label' => __('Gdy brak wideo', 'simple-lms'),
                'type' => Controls_Manager::SELECT,
                'default' => 'hide',
                'options' => [
                    'hide' => __('Hide completely', 'simple-lms'),
                    'message' => __('Show message', 'simple-lms'),
                ],
            ]
        );

        $this->add_control(
            'no_video_message',
            [
                'label' => __('Komunikat o braku wideo', 'simple-lms'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Video for this lesson is not yet available.', 'simple-lms'),
                'condition' => [
                    'show_when_no_video' => 'message',
                ],
            ]
        );

        $this->end_controls_section();

        // Style section - Video Container
        $this->start_controls_section(
            'video_style_section',
            [
                'label' => __('Kontener wideo', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'video_border_radius',
            [
                'label' => __('Border radius', 'simple-lms'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem'],
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-video-wrapper' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; overflow: hidden;',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'video_shadow',
                'selector' => '{{WRAPPER}} .simple-lms-video-wrapper',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'video_border',
                'selector' => '{{WRAPPER}} .simple-lms-video-wrapper',
            ]
        );

        $this->end_controls_section();

        // Style section - Poster & Play Icon
        $this->start_controls_section(
            'poster_style_section',
            [
                'label' => __('Poster and Play icon', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_poster' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'poster_overlay_heading',
            [
                'label' => __('Overlay', 'simple-lms'),
                'type' => Controls_Manager::HEADING,
            ]
        );

        $this->add_control(
            'poster_overlay_color',
            [
                'label' => __('Overlay color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => 'rgba(0,0,0,0.3)',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-video-poster::before' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'poster_overlay_hover_color',
            [
                'label' => __('Overlay color (hover)', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => 'rgba(0,0,0,0.5)',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-video-poster:hover::before' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'play_icon_heading',
            [
                'label' => __('Ikona Play', 'simple-lms'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'show_play_icon' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'play_icon_size',
            [
                'label' => __('Rozmiar ikony', 'simple-lms'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 30,
                        'max' => 200,
                    ],
                    'em' => [
                        'min' => 2,
                        'max' => 15,
                    ],
                    'rem' => [
                        'min' => 2,
                        'max' => 15,
                    ],
                ],
                'default' => [
                    'size' => 80,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-video-play-icon' => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .simple-lms-video-play-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'show_play_icon' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'play_icon_color',
            [
                'label' => __('Icon color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-video-play-icon' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .simple-lms-video-play-icon svg' => 'fill: {{VALUE}};',
                ],
                'condition' => [
                    'show_play_icon' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'play_icon_hover_color',
            [
                'label' => __('Icon color (hover)', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ff0000',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-video-poster:hover .simple-lms-video-play-icon' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .simple-lms-video-poster:hover .simple-lms-video-play-icon svg' => 'fill: {{VALUE}};',
                ],
                'condition' => [
                    'show_play_icon' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Text_Shadow::get_type(),
            [
                'name' => 'play_icon_shadow',
                'selector' => '{{WRAPPER}} .simple-lms-video-play-icon',
                'condition' => [
                    'show_play_icon' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // Style section - Fallback Message
        $this->start_controls_section(
            'fallback_style_section',
            [
                'label' => __('Fallback message', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_when_no_video' => 'message',
                ],
            ]
        );

        $this->add_control(
            'fallback_bg_color',
            [
                'label' => __('Background color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#f5f5f5',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-video-fallback' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'fallback_text_color',
            [
                'label' => __('Text color', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#666666',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-video-fallback' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'fallback_message_typography',
                'selector' => '{{WRAPPER}} .simple-lms-video-fallback',
            ]
        );

        $this->add_responsive_control(
            'fallback_Padding',
            [
                'label' => __('Padding', 'simple-lms'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', 'rem'],
                'default' => [
                    'top' => '40',
                    'right' => '20',
                    'bottom' => '40',
                    'left' => '20',
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-video-fallback' => 'Padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

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

        if (!$lesson_id && $settings['video_source'] === 'custom_field') {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="elementor-alert elementor-alert-warning">';
                echo esc_html__('Cannot detect lesson. Make sure the widget is used on a lesson page.', 'simple-lms');
                echo '</div>';
            }
            return;
        }

        // Get video URL
        if ($settings['video_source'] === 'custom_url') {
            $video_url = $settings['custom_video_url'];
        } else {
            $video_url = get_post_meta($lesson_id, 'lesson_video_url', true);
        }

        // If no video URL
        if (!$video_url) {
            // Hide completely (default behavior)
            if ($settings['show_when_no_video'] === 'hide') {
                // Return nothing in frontend, show notice in editor
                if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                    echo '<div class="elementor-alert elementor-alert-info">';
                    echo esc_html__('No video - widget will be hidden on frontend', 'simple-lms');
                    echo '</div>';
                }
                return;
            }
            
            // Show fallback message
            if ($settings['show_when_no_video'] === 'message') {
                $aspect_class = 'aspect-' . $settings['aspect_ratio'];
                echo '<div class="simple-lms-video-container ' . esc_attr($aspect_class) . '">';
                echo '<div class="simple-lms-video-fallback simple-lms-video-wrapper" style="display: flex; align-items: center; justify-content: center; text-align: center; min-height: 300px;">';
                echo '<p>' . esc_html($settings['no_video_message']) . '</p>';
                echo '</div>';
                echo '</div>';
                return;
            }
        }

        // Video exists - proceed with rendering
        $aspect_class = 'aspect-' . $settings['aspect_ratio'];
        $wrapper_style = $settings['aspect_ratio'] !== 'custom' ? '' : '';
        $show_poster = $settings['show_poster'] === 'yes';
        $show_play_icon = $settings['show_play_icon'] === 'yes' && $show_poster;

        echo '<div class="simple-lms-video-container ' . esc_attr($aspect_class) . '">';

        // Get poster image
        $poster_url = '';
        if ($show_poster) {
            if ($settings['poster_source'] === 'custom' && !empty($settings['custom_poster']['url'])) {
                $poster_url = $settings['custom_poster']['url'];
            } elseif ($settings['poster_source'] === 'featured' && $lesson_id) {
                $poster_url = get_the_post_thumbnail_url($lesson_id, 'large');
            }
        }

        // Video options
        $autoplay = $settings['autoplay'] === 'yes' ? 'autoplay' : '';
        $muted = $settings['muted'] === 'yes' ? 'muted' : '';
        $controls = $settings['controls'] === 'yes' ? 'controls' : '';

        // Get video HTML
        $video_html = $this->get_video_embed($video_url, $autoplay, $muted, $controls);
        $video_id = 'video-' . $this->get_id();

        echo '<div class="simple-lms-video-wrapper" id="' . esc_attr($video_id) . '" style="' . esc_attr($wrapper_style) . '">';
        
        // Show poster with play icon
        if ($show_poster && $poster_url) {
            echo '<div class="simple-lms-video-poster" style="background-image: url(' . esc_url($poster_url) . ');" data-video-id="' . esc_attr($video_id) . '">';
            
            if ($show_play_icon) {
                echo '<div class="simple-lms-video-play-icon">';
                \Elementor\Icons_Manager::render_icon($settings['play_icon'], ['aria-hidden' => 'true']);
                echo '</div>';
            }
            
            echo '</div>';
        }
        
        // Video iframe/video tag (hidden until play)
        echo '<div class="simple-lms-video-content" style="' . ($show_poster && $poster_url ? 'display:none;' : '') . '">';
        echo $video_html;
        echo '</div>';
        
        echo '</div>';
        echo '</div>';

        // Add CSS for aspect ratios and poster
        if ($settings['aspect_ratio'] !== 'custom') {
            $ratios = [
                '16-9' => '56.25%',
                '4-3' => '75%',
                '1-1' => '100%',
                '21-9' => '42.86%',
            ];
            $Padding = $ratios[$settings['aspect_ratio']] ?? '56.25%';
            ?>
            <style>
                .simple-lms-video-container.aspect-<?php echo esc_attr($settings['aspect_ratio']); ?> .simple-lms-video-wrapper {
                    position: relative;
                    Padding-bottom: <?php echo esc_attr($Padding); ?>;
                    height: 0;
                    overflow: hidden;
                }
                .simple-lms-video-container.aspect-<?php echo esc_attr($settings['aspect_ratio']); ?> .simple-lms-video-wrapper iframe,
                .simple-lms-video-container.aspect-<?php echo esc_attr($settings['aspect_ratio']); ?> .simple-lms-video-wrapper video {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                }
            </style>
            <?php
        }
        
        // Add poster styles and JS
        if ($show_poster && $poster_url) {
            ?>
            <style>
                .simple-lms-video-poster {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background-size: cover;
                    background-position: center;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 2;
                    transition: all 0.3s ease;
                }
                .simple-lms-video-poster::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    transition: background-color 0.3s ease;
                }
                .simple-lms-video-play-icon {
                    position: relative;
                    z-index: 3;
                    transition: all 0.3s ease;
                }
                .simple-lms-video-content {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    z-index: 1;
                }
                .simple-lms-video-content iframe,
                .simple-lms-video-content video {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                }
            </style>
            <script>
            (function($) {
                $(document).ready(function() {
                    $('.simple-lms-video-poster').on('click', function() {
                        var poster = $(this);
                        var videoId = poster.data('video-id');
                        var wrapper = $('#' + videoId);
                        var content = wrapper.find('.simple-lms-video-content');
                        
                        // Hide poster, show video
                        poster.fadeOut(300, function() {
                            content.fadeIn(300);
                            
                            // Auto-play if iframe (YouTube/Vimeo)
                            var iframe = content.find('iframe');
                            if (iframe.length) {
                                var src = iframe.attr('src');
                                if (src.indexOf('?') > -1) {
                                    iframe.attr('src', src + '&autoplay=1');
                                } else {
                                    iframe.attr('src', src + '?autoplay=1');
                                }
                            }
                            
                            // Auto-play if video tag
                            var video = content.find('video');
                            if (video.length) {
                                video[0].play();
                            }
                        });
                    });
                });
            })(jQuery);
            </script>
            <?php
        }
    }

    /**
     * Get video embed HTML
     */
    private function get_video_embed($url, $autoplay, $muted, $controls): string {
        // YouTube
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\s]+)/', $url, $matches)) {
            $video_id = $matches[1];
            $params = [];
            if ($autoplay) $params[] = 'autoplay=1';
            if ($muted) $params[] = 'mute=1';
            if (!$controls) $params[] = 'controls=0';
            $query = !empty($params) ? '?' . implode('&', $params) : '';
            
            return '<iframe src="https://www.youtube.com/embed/' . esc_attr($video_id) . $query . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
        }

        // Vimeo
        if (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
            $video_id = $matches[1];
            $params = [];
            if ($autoplay) $params[] = 'autoplay=1';
            if ($muted) $params[] = 'muted=1';
            $query = !empty($params) ? '?' . implode('&', $params) : '';
            
            return '<iframe src="https://player.vimeo.com/video/' . esc_attr($video_id) . $query . '" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>';
        }

        // Direct video file (MP4, WebM, etc.)
        if (preg_match('/\.(mp4|webm|ogg)$/i', $url)) {
            $attrs = [$controls];
            if ($autoplay) $attrs[] = 'autoplay';
            if ($muted) $attrs[] = 'muted';
            
            return '<video ' . implode(' ', $attrs) . ' style="width: 100%; height: 100%;"><source src="' . esc_url($url) . '" type="video/' . pathinfo($url, PATHINFO_EXTENSION) . '">Twoja przeglądarka nie obsługuje odtwarzania wideo.</video>';
        }

        // Fallback - try iframe
        return '<iframe src="' . esc_url($url) . '" frameborder="0" allowfullscreen style="width: 100%; height: 100%;"></iframe>';
    }

    /**
     * Render widget output in the editor
     */
    protected function content_template(): void {
        ?>
        <#
        var aspectClass = 'aspect-' + settings.aspect_ratio;
        #>
        <div class="simple-lms-video-container {{{aspectClass}}}">
            <div class="simple-lms-video-fallback simple-lms-video-wrapper" style="display: flex; align-items: center; justify-content: center; text-align: center; min-height: 300px; background-color: #f5f5f5;">
                <p style="color: #666;">
                    <# if (settings.video_source === 'custom_url' && settings.custom_video_url) { #>
                        {{{ settings.custom_video_url }}}
                    <# } else { #>
                        <?php echo esc_html__('Preview wideo w edytorze', 'simple-lms'); ?>
                    <# } #>
                </p>
            </div>
        </div>
        <?php
    }
}
