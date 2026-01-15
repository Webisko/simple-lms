<?php
namespace SimpleLMS\Bricks\Elements;
use SimpleLMS\Access_Control;

if (!defined('ABSPATH')) exit;

class Access_Status extends \Bricks\Element {
    public $category = 'simple-lms';
    public $name = 'simple-lms-access-status';
    public $icon = 'ti-lock';

    public function get_label() {
        return esc_html__('Access Status', 'simple-lms');
    }

    public function set_controls() {
        $this->controls['courseId'] = ['tab'=>'content','label'=>esc_html__('Course ID','simple-lms'),'type'=>'number','default'=>0];
        $this->controls['grantedText'] = ['tab'=>'content','label'=>esc_html__('Access Granted Text','simple-lms'),'type'=>'text','default'=>'You have access'];
        $this->controls['deniedText'] = ['tab'=>'content','label'=>esc_html__('Access Denied Text','simple-lms'),'type'=>'text','default'=>'No access'];
    }

    public function render() {
        if (!is_user_logged_in()) return;
        $settings = $this->settings;
        $course_id = !empty($settings['courseId']) ? absint($settings['courseId']) : \SimpleLMS\Bricks\Bricks_Integration::get_current_course_id();
        if (!$course_id) return;
        $has_access = Access_Control::userHasAccess(get_current_user_id(), $course_id);
        $text = $has_access ? ($settings['grantedText']??'Masz dostęp') : ($settings['deniedText']??'Brak dostępu');
        $color = $has_access ? '#4CAF50' : '#f44336';
        echo '<div class="simple-lms-access-status" style="Padding:10px 16px;background:'.esc_attr($color).';color:#fff;border-radius:4px;display:inline-block">'.esc_html($text).'</div>';
    }
}
