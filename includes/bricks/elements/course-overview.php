<?php
namespace SimpleLMS\Bricks\Elements;
use SimpleLMS\Cache_Handler;
use SimpleLMS\Progress_Tracker;

if (!defined('ABSPATH')) exit;

class Course_Overview extends \Bricks\Element {
    public $category = 'simple-lms';
    public $name = 'simple-lms-course-overview';
    public $icon = 'ti-layout-accordion-merged';

    public function get_label() {
        return esc_html__('Course Overview', 'simple-lms');
    }

    public function set_controls() {
        $this->controls['courseId'] = ['tab'=>'content','label'=>esc_html__('Course ID','simple-lms'),'type'=>'number','default'=>0];
        $this->controls['showProgress'] = ['tab'=>'content','label'=>esc_html__('Show Progress','simple-lms'),'type'=>'checkbox','default'=>true];
    }

    public function render() {
        $settings = $this->settings;
        $course_id = !empty($settings['courseId']) ? absint($settings['courseId']) : \SimpleLMS\Bricks\Bricks_Integration::get_current_course_id();
        if (!$course_id) return;
        $modules = Cache_Handler::getCourseModules($course_id);
        if (empty($modules)) return;
        $user_id = is_user_logged_in() ? get_current_user_id() : 0;
        echo '<div class="simple-lms-course-overview">';
        foreach ($modules as $module) {
            $lessons = Cache_Handler::getModuleLessons($module->ID);
            echo '<div class="module-item"><div class="module-header" style="cursor:pointer;Padding:12px;background:#f5f5f5;font-weight:600">'.esc_html(get_the_title($module->ID)).'</div><div class="module-content" style="display:none;Padding:10px">';
            foreach ($lessons as $lesson) {
                $completed = $user_id && Progress_Tracker::isLessonCompleted($user_id, $lesson->ID);
                echo '<div class="lesson-item"><a href="'.esc_url(get_permalink($lesson->ID)).'">'.($completed?'✓ ':'').''.esc_html(get_the_title($lesson->ID)).'</a></div>';
            }
            echo '</div></div>';
        }
        echo '</div><script>document.querySelectorAll(".module-header").forEach(h=>h.addEventListener("click",()=>{const c=h.nextElementSibling;c.style.display=c.style.display==="none"?"block":"none"}))</script>';
    }
}
