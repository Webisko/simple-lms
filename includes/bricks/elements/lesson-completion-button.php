<?php
namespace SimpleLMS\Bricks\Elements;
use SimpleLMS\Progress_Tracker;

if (!defined('ABSPATH')) exit;

class Lesson_Completion_Button extends \Bricks\Element {
    public $category = 'simple-lms';
    public $name = 'simple-lms-lesson-completion';
    public $icon = 'ti-check-box';

    public function get_label() {
        return esc_html__('Lesson Completion Button', 'simple-lms');
    }

    public function set_controls() {
        $this->controls['lessonId'] = ['tab'=>'content','label'=>esc_html__('Lesson ID','simple-lms'),'type'=>'number','default'=>0];
        $this->controls['completedText'] = ['tab'=>'content','label'=>esc_html__('Completed Text','simple-lms'),'type'=>'text','default'=>'Lesson completed'];
        $this->controls['incompleteText'] = ['tab'=>'content','label'=>esc_html__('Incomplete Text','simple-lms'),'type'=>'text','default'=>'Oznacz jako completed'];
    }

    public function render() {
        if (!is_user_logged_in()) return;
        $settings = $this->settings;
        $lesson_id = !empty($settings['lessonId']) ? absint($settings['lessonId']) : get_the_ID();
        if (get_post_type($lesson_id) !== 'lesson') return;
        $user_id = get_current_user_id();
        $completed = Progress_Tracker::isLessonCompleted($user_id, $lesson_id);
        $text = $completed ? ($settings['completedText'] ?? 'Lesson completed') : ($settings['incompleteText'] ?? 'Oznacz jako completed');
        $class = $completed ? 'completed' : 'incomplete';
        echo '<button class="simple-lms-completion-btn '.$class.'" data-lesson="'.esc_attr($lesson_id).'" style="Padding:12px 24px;background:'.($completed?'#4CAF50':'#2196F3').';color:#fff;border:none;border-radius:4px;cursor:pointer">'.esc_html($text).'</button>';
        if (!$completed) {
            echo '<script>document.querySelector(".simple-lms-completion-btn").addEventListener("click",function(){fetch("'.admin_url('admin-ajax.php').'",{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:"action=simple_lms_mark_lesson_complete&lesson_id='.esc_js($lesson_id).'&nonce='.wp_create_nonce('simple_lms_lesson_complete').'"}).then(()=>location.reload())})</script>';
        }
    }
}
