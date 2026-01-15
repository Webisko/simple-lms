<?php
namespace SimpleLMS\Elementor\Widgets;

use SimpleLMS\Elementor\Elementor_Dynamic_Tags;
use SimpleLMS\Cache_Handler;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) { exit; }

class Module_Navigation_Widget extends Widget_Base {
    public function get_name(): string { return 'simple_lms_module_navigation'; }
    public function get_title(): string { return __('Module Navigation', 'simple-lms'); }
    public function get_icon(): string { return 'eicon-list'; }
    public function get_categories(): array { return ['simple-lms']; }

    protected function register_controls(): void {
        $this->start_controls_section('content', ['label'=>__('Settings','simple-lms'),'tab'=>Controls_Manager::TAB_CONTENT]);
        $this->add_control('course_id',['label'=>__('ID kursu','simple-lms'),'type'=>Controls_Manager::NUMBER,'default'=>0,'description'=>__('Leave 0 to detect automatically','simple-lms')]);
        $this->add_responsive_control('layout',['label'=>__('Layout','simple-lms'),'type'=>Controls_Manager::SELECT,'default'=>'list','options'=>['list'=>__('Lista','simple-lms'),'grid'=>__('Siatka','simple-lms')]]);
        $this->add_responsive_control('columns',['label'=>__('Kolumny (grid)','simple-lms'),'type'=>Controls_Manager::SELECT,'default'=>'2','options'=>['1'=>'1','2'=>'2','3'=>'3','4'=>'4'],'condition'=>['layout'=>'grid']]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $course_id = !empty($settings['course_id']) ? absint($settings['course_id']) : \SimpleLMS\Elementor\Elementor_Dynamic_Tags::get_current_course_id();
        if (!$course_id) { if (\Elementor\Plugin::$instance->editor->is_edit_mode()) { echo '<div class="elementor-alert elementor-alert-warning">'.esc_html__('Nie wykryto kursu.','simple-lms').'</div>'; } return; }
        $modules = Cache_Handler::getCourseModules($course_id);
        if (empty($modules)) { echo '<div class="elementor-alert elementor-alert-info">'.esc_html__('No modules in course.','simple-lms').'</div>'; return; }
        $layout = $settings['layout']; $columns = $settings['columns'] ?? '2';
        $container_style = $layout==='grid' ? 'display:grid' : 'display:flex; flex-direction:column';
        echo '<div class="simple-lms-module-navigation layout-'.esc_attr($layout).' columns-'.esc_attr($columns).'" style="'.$container_style.'">';
        foreach ($modules as $module) {
            $lessons = Cache_Handler::getModuleLessons($module->ID);
            $first_lesson_url = (!empty($lessons)) ? get_permalink($lessons[0]->ID) : get_permalink($module->ID);
            echo '<div class="module-item" style="border:1px solid #eee; border-radius:6px; Padding:12px">';
            echo '<div class="module-title" style="font-weight:600; margin-bottom:6px">'.esc_html(get_the_title($module->ID)).'</div>';
            echo '<div class="module-meta" style="font-size:0.9em; opacity:0.8; margin-bottom:10px">'.esc_html(sprintf(__('Lekcje: %d','simple-lms'), count($lessons))).'</div>';
            echo '<a class="module-start-btn" href="'.esc_url($first_lesson_url).'" style="display:inline-block; Padding:8px 12px; background:#2196F3; color:#fff; text-decoration:none; border-radius:4px">'.esc_html__('Start module','simple-lms').'</a>';
            echo '</div>';
        }
        echo '</div>';
        echo '<style>.simple-lms-module-navigation.layout-grid.columns-1{grid-template-columns:1fr}.simple-lms-module-navigation.layout-grid.columns-2{grid-template-columns:repeat(2,1fr)}.simple-lms-module-navigation.layout-grid.columns-3{grid-template-columns:repeat(3,1fr)}.simple-lms-module-navigation.layout-grid.columns-4{grid-template-columns:repeat(4,1fr)}@media(max-width:768px){.simple-lms-module-navigation.layout-grid{grid-template-columns:1fr}}</style>';
    }
}
