<?php
namespace SimpleLMS\Bricks\Elements;
use SimpleLMS\Access_Control;

if (!defined('ABSPATH')) exit;

class Course_Purchase extends \Bricks\Element {
    public $category = 'simple-lms';
    public $name = 'simple-lms-course-purchase';
    public $icon = 'ti-shopping-cart';

    public function get_label() {
        return esc_html__('Course Purchase CTA', 'simple-lms');
    }

    public function set_controls() {
        $this->controls['courseId'] = ['tab'=>'content','label'=>esc_html__('Course ID','simple-lms'),'type'=>'number','default'=>0];
        $this->controls['buttonText'] = ['tab'=>'content','label'=>esc_html__('Button Text','simple-lms'),'type'=>'text','default'=>'Kup kurs'];
    }

    public function render() {
        // Require WooCommerce to be active
        if (!class_exists('WooCommerce')) {
            return;
        }

        $settings = $this->settings;
        $course_id = !empty($settings['courseId']) ? absint($settings['courseId']) : \SimpleLMS\Bricks\Bricks_Integration::get_current_course_id();
        if (!$course_id) return;
        if (is_user_logged_in() && Access_Control::userHasAccess(get_current_user_id(), $course_id)) return;
        $product_id = get_post_meta($course_id, 'course_product', true);
        if (!$product_id) return;
        if (!function_exists('wc_get_product')) {
            return;
        }
        $product = wc_get_product($product_id);
        if (!$product) return;
        echo '<div class="simple-lms-purchase-cta" style="padding:20px;border:2px solid #2196F3;border-radius:8px;text-align:center">';
        echo '<div class="price" style="font-size:32px;font-weight:700;color:#2196F3;margin-bottom:15px">'.$product->get_price_html().'</div>';
        echo '<a href="'.esc_url($product->add_to_cart_url()).'" class="purchase-btn" style="display:inline-block;padding:12px 32px;background:#2196F3;color:#fff;text-decoration:none;border-radius:4px;font-weight:600">'.esc_html($settings['buttonText']??'Kup kurs').'</a>';
        echo '</div>';
    }
}
