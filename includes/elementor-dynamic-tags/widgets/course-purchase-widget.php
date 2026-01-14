<?php
/**
 * Course Purchase CTA Widget for Elementor
 *
 * Displays purchase button with WooCommerce price and add-to-cart functionality
 *
 * @package SimpleLMS
 */

namespace SimpleLMS\Elementor\Widgets;

use SimpleLMS\Access_Control;
use SimpleLMS\Elementor\Elementor_Dynamic_Tags;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Course Purchase CTA Widget
 */
class Course_Purchase_Widget extends Widget_Base {

    /**
     * Get widget name
     */
    public function get_name(): string {
        return 'simple_lms_course_purchase';
    }

    /**
     * Get widget title
     */
    public function get_title(): string {
        return __('Course Purchase Button', 'simple-lms');
    }

    /**
     * Get widget icon
     */
    public function get_icon(): string {
        return 'eicon-cart';
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
        return ['lms', 'course', 'purchase', 'buy', 'woocommerce', 'price', 'cart', 'simple lms'];
    }

    /**
     * Register widget controls
     */
    protected function register_controls(): void {
        
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Ustawienia', 'simple-lms'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'course_id',
            [
                'label' => __('ID kursu', 'simple-lms'),
                'type' => Controls_Manager::NUMBER,
                'default' => 0,
                'description' => __('Zostaw 0 aby automatycznie wykryć kurs z aktualnej strony', 'simple-lms'),
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label' => __('Tekst przycisku', 'simple-lms'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Kup teraz', 'simple-lms'),
            ]
        );

        $this->add_control(
            'button_icon',
            [
                'label' => __('Ikona', 'simple-lms'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-shopping-cart',
                    'library' => 'fa-solid',
                ],
            ]
        );

        $this->add_control(
            'icon_position',
            [
                'label' => __('Pozycja ikony', 'simple-lms'),
                'type' => Controls_Manager::SELECT,
                'default' => 'left',
                'options' => [
                    'left' => __('Przed tekstem', 'simple-lms'),
                    'right' => __('Po tekście', 'simple-lms'),
                ],
            ]
        );

        $this->add_control(
            'show_price',
            [
                'label' => __('Pokaż cenę', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'price_position',
            [
                'label' => __('Pozycja ceny', 'simple-lms'),
                'type' => Controls_Manager::SELECT,
                'default' => 'above',
                'options' => [
                    'above' => __('Nad przyciskiem', 'simple-lms'),
                    'inline' => __('W przycis ku', 'simple-lms'),
                    'below' => __('Pod przyciskiem', 'simple-lms'),
                ],
                'condition' => [
                    'show_price' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'hide_when_has_access',
            [
                'label' => __('Ukryj gdy user ma dostęp', 'simple-lms'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'return_value' => 'yes',
                'description' => __('Widget będzie ukryty dla użytkowników z dostępem do kursu', 'simple-lms'),
            ]
        );

        $this->add_control(
            'already_owned_text',
            [
                'label' => __('Tekst gdy już posiada', 'simple-lms'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Już posiadasz ten kurs', 'simple-lms'),
                'condition' => [
                    'hide_when_has_access!' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'alignment',
            [
                'label' => __('Wyrównanie', 'simple-lms'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => __('Do lewej', 'simple-lms'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __('Wyśrodkuj', 'simple-lms'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => __('Do prawej', 'simple-lms'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'default' => 'center',
                'selectors' => [
                    '{{WRAPPER}} .simple-lms-purchase-wrapper' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Section - Price
        $this->start_controls_section(
            'price_style_section',
            [
                'label' => __('Cena', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_price' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'price_typography',
                'label' => __('Typografia ceny', 'simple-lms'),
                'selector' => '{{WRAPPER}} .purchase-price .woocommerce-Price-amount',
            ]
        );

        $this->add_control(
            'price_color',
            [
                'label' => __('Kolor ceny', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#2c3e50',
                'selectors' => [
                    '{{WRAPPER}} .purchase-price .woocommerce-Price-amount' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'sale_price_typography',
                'label' => __('Typografia ceny promocyjnej', 'simple-lms'),
                'selector' => '{{WRAPPER}} .purchase-price ins .woocommerce-Price-amount',
            ]
        );

        $this->add_control(
            'sale_price_color',
            [
                'label' => __('Kolor ceny promocyjnej', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#e74c3c',
                'selectors' => [
                    '{{WRAPPER}} .purchase-price ins .woocommerce-Price-amount' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'regular_price_color',
            [
                'label' => __('Kolor ceny regularnej (przekreślona)', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#95a5a6',
                'selectors' => [
                    '{{WRAPPER}} .purchase-price del .woocommerce-Price-amount' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'price_spacing',
            [
                'label' => __('Odstęp od przycisku', 'simple-lms'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
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
                    '{{WRAPPER}} .price-above' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .price-below' => 'margin-top: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .price-inline' => 'margin-left: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Section - Button
        $this->start_controls_section(
            'button_style_section',
            [
                'label' => __('Przycisk', 'simple-lms'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'button_typography',
                'selector' => '{{WRAPPER}} .purchase-button',
            ]
        );

        $this->add_responsive_control(
            'button_width',
            [
                'label' => __('Szerokość', 'simple-lms'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'auto'],
                'range' => [
                    'px' => [
                        'min' => 100,
                        'max' => 800,
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
                    '{{WRAPPER}} .purchase-button' => 'width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'button_padding',
            [
                'label' => __('Padding', 'simple-lms'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', 'rem'],
                'default' => [
                    'top' => '18',
                    'right' => '35',
                    'bottom' => '18',
                    'left' => '35',
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .purchase-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'button_border_radius',
            [
                'label' => __('Zaokrąglenie rogów', 'simple-lms'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem'],
                'selectors' => [
                    '{{WRAPPER}} .purchase-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                        'min' => 10,
                        'max' => 50,
                    ],
                    'em' => [
                        'min' => 0.5,
                        'max' => 3,
                    ],
                    'rem' => [
                        'min' => 0.5,
                        'max' => 3,
                    ],
                ],
                'default' => [
                    'size' => 18,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .purchase-button i' => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .purchase-button svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'icon_spacing',
            [
                'label' => __('Odstęp ikony', 'simple-lms'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 30,
                    ],
                    'em' => [
                        'min' => 0,
                        'max' => 3,
                    ],
                    'rem' => [
                        'min' => 0,
                        'max' => 3,
                    ],
                ],
                'default' => [
                    'size' => 8,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .purchase-icon-left' => 'margin-right: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .purchase-icon-right' => 'margin-left: {{SIZE}}{{UNIT}};',
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
            'button_text_color',
            [
                'label' => __('Kolor tekstu', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .purchase-button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_bg_color',
            [
                'label' => __('Kolor tła', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#e74c3c',
                'selectors' => [
                    '{{WRAPPER}} .purchase-button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'button_border',
                'selector' => '{{WRAPPER}} .purchase-button',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'button_shadow',
                'selector' => '{{WRAPPER}} .purchase-button',
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
            'button_hover_text_color',
            [
                'label' => __('Kolor tekstu', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .purchase-button:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_hover_bg_color',
            [
                'label' => __('Kolor tła', 'simple-lms'),
                'type' => Controls_Manager::COLOR,
                'default' => '#c0392b',
                'selectors' => [
                    '{{WRAPPER}} .purchase-button:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'button_hover_border',
                'selector' => '{{WRAPPER}} .purchase-button:hover',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'button_hover_shadow',
                'selector' => '{{WRAPPER}} .purchase-button:hover',
            ]
        );

        $this->add_control(
            'button_hover_transition',
            [
                'label' => __('Czas przejścia (ms)', 'simple-lms'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 1000,
                    ],
                ],
                'default' => [
                    'size' => 300,
                ],
                'selectors' => [
                    '{{WRAPPER}} .purchase-button' => 'transition: all {{SIZE}}ms ease;',
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
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="elementor-alert elementor-alert-warning">';
                echo esc_html__('WooCommerce nie jest aktywny. Ten widget wymaga WooCommerce.', 'simple-lms');
                echo '</div>';
            }
            return;
        }

        $settings = $this->get_settings_for_display();

        // Get course ID
        $course_id = !empty($settings['course_id']) 
            ? absint($settings['course_id']) 
            : Elementor_Dynamic_Tags::get_current_course_id();

        if (!$course_id) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="elementor-alert elementor-alert-warning">';
                echo esc_html__('Nie można wykryć kursu. Upewnij się, że widget jest używany na stronie kursu.', 'simple-lms');
                echo '</div>';
            }
            return;
        }

        // Get product ID
        $product_id = get_post_meta($course_id, 'course_product_id', true);

        if (!$product_id) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="elementor-alert elementor-alert-warning">';
                echo esc_html__('Ten kurs nie ma przypisanego produktu WooCommerce.', 'simple-lms');
                echo '</div>';
            }
            return;
        }

        // Check if user already has access
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $has_access = Access_Control::userHasAccessToCourse($user_id, $course_id);

            if ($has_access) {
                if ($settings['hide_when_has_access'] === 'yes') {
                    return;
                }
                
                echo '<div class="simple-lms-purchase-wrapper">';
                echo '<div class="already-owned-message">' . esc_html($settings['already_owned_text']) . '</div>';
                echo '</div>';
                return;
            }
        }

        // Get product
        $product = wc_get_product($product_id);

        if (!$product) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="elementor-alert elementor-alert-warning">';
                echo esc_html__('Produkt WooCommerce nie istnieje.', 'simple-lms');
                echo '</div>';
            }
            return;
        }

        $this->render_purchase_button($product, $settings);
    }

    /**
     * Render purchase button with price
     */
    private function render_purchase_button($product, $settings): void {
        $show_price = $settings['show_price'] === 'yes';
        $price_position = $settings['price_position'];
        $icon_position = $settings['icon_position'];
        $add_to_cart_url = $product->add_to_cart_url();

        echo '<div class="simple-lms-purchase-wrapper">';

        // Price above button
        if ($show_price && $price_position === 'above') {
            echo '<div class="purchase-price price-above">' . $product->get_price_html() . '</div>';
        }

        // Purchase button
        echo '<a href="' . esc_url($add_to_cart_url) . '" class="purchase-button" data-product-id="' . esc_attr($product->get_id()) . '">';

        // Icon before text
        if ($icon_position === 'left' && !empty($settings['button_icon']['value'])) {
            echo '<span class="purchase-icon-left">';
            \Elementor\Icons_Manager::render_icon($settings['button_icon'], ['aria-hidden' => 'true']);
            echo '</span>';
        }

        // Button text
        echo '<span class="purchase-button-text">' . esc_html($settings['button_text']) . '</span>';

        // Price inline in button
        if ($show_price && $price_position === 'inline') {
            echo '<span class="purchase-price price-inline">' . $product->get_price_html() . '</span>';
        }

        // Icon after text
        if ($icon_position === 'right' && !empty($settings['button_icon']['value'])) {
            echo '<span class="purchase-icon-right">';
            \Elementor\Icons_Manager::render_icon($settings['button_icon'], ['aria-hidden' => 'true']);
            echo '</span>';
        }

        echo '</a>';

        // Price below button
        if ($show_price && $price_position === 'below') {
            echo '<div class="purchase-price price-below">' . $product->get_price_html() . '</div>';
        }

        echo '</div>';

        // Add inline styles
        ?>
        <style>
            .simple-lms-purchase-wrapper {
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            .purchase-button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                text-decoration: none;
                cursor: pointer;
            }
            .purchase-price {
                font-weight: 600;
            }
            .purchase-price del {
                opacity: 0.7;
            }
            .already-owned-message {
                padding: 15px 25px;
                background: #e8f5e9;
                color: #2e7d32;
                border-radius: 4px;
                font-weight: 500;
            }
        </style>
        <?php
    }
}
