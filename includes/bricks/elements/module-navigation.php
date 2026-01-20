<?php
namespace SimpleLMS\Bricks\Elements;
use SimpleLMS\Cache_Handler;
use SimpleLMS\Progress_Tracker;

if (!defined('ABSPATH')) exit;

class Module_Navigation extends \Bricks\Element {
    public $category = 'simple-lms';
    public $name = 'simple-lms-module-navigation';
    public $icon = 'ti-view-list';

    public function get_label() {
        return esc_html__('Module Navigation', 'simple-lms');
    }

    public function set_controls() {
        $this->controls['courseId'] = ['tab'=>'content','label'=>esc_html__('Course ID','simple-lms'),'type'=>'number','default'=>0];
        $this->controls['layout'] = ['tab'=>'content','label'=>esc_html__('Layout','simple-lms'),'type'=>'select','options'=>['list'=>'List','grid'=>'Grid'],'default'=>'list'];
    }

    public function render() {
        $settings = $this->settings;
        $course_id = !empty($settings['courseId']) ? absint($settings['courseId']) : \SimpleLMS\Bricks\Bricks_Integration::get_current_course_id();
        if (!$course_id) return;
        $modules = Cache_Handler::getCourseModules($course_id);
        if (empty($modules)) return;
        $layout = $settings['layout'] ?? 'list';
        echo '<div class="simple-lms-module-nav layout-'.$layout.'" style="display:'.($layout==='grid'?'grid':'flex').';'.($layout==='grid'?'grid-template-columns:repeat(auto-fill,minmax(250px,1fr));':'flex-direction:column;').'gap:15px">';
        foreach ($modules as $module) {
            $lessons = Cache_Handler::getModuleLessons($module->ID);
            echo '<div class="module-item" style="Padding:15px;border:1px solid #eee;border-radius:6px">';
            echo '<h4 style="margin:0 0 10px"><a href="'.esc_url(get_permalink($module)).'" style="color:#333;text-decoration:none">'.esc_html($module->post_title).'</a></h4>';
            echo '<div style="font-size:0.9em;color:#666">Lekcji: '.count($lessons).'</div>';
            echo '</div>';
        }
        echo '</div>';
    }
}
