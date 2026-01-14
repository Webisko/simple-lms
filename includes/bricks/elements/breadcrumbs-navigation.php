<?php
namespace SimpleLMS\Bricks\Elements;

if (!defined('ABSPATH')) exit;

class Breadcrumbs_Navigation extends \Bricks\Element {
    public $category = 'simple-lms';
    public $name = 'simple-lms-breadcrumbs';
    public $icon = 'ti-layout-list-thumb';

    public function get_label() {
        return esc_html__('Breadcrumbs Navigation', 'simple-lms');
    }

    public function set_controls() {
        $this->controls['separator'] = ['tab'=>'content','label'=>esc_html__('Separator','simple-lms'),'type'=>'text','default'=>'/'];
        $this->controls['homeText'] = ['tab'=>'content','label'=>esc_html__('Home Text','simple-lms'),'type'=>'text','default'=>'Start'];
    }

    public function render() {
        $settings = $this->settings;
        $sep = ' '.esc_html($settings['separator']??'/').' ';
        echo '<div class="simple-lms-breadcrumbs" style="padding:10px 0;color:#666">';
        echo '<a href="'.esc_url(home_url()).'" style="color:#2196F3;text-decoration:none">'.esc_html($settings['homeText']??'Start').'</a>'.$sep;
        if (is_singular('lesson')) {
            $lesson = get_post();
            $module_id = wp_get_post_parent_id($lesson->ID);
            if ($module_id) {
                $module = get_post($module_id);
                $course_id = wp_get_post_parent_id($module->ID);
                if ($course_id) {
                    $course = get_post($course_id);
                    echo '<a href="'.esc_url(get_permalink($course)).'" style="color:#2196F3;text-decoration:none">'.esc_html($course->post_title).'</a>'.$sep;
                }
                echo '<a href="'.esc_url(get_permalink($module)).'" style="color:#2196F3;text-decoration:none">'.esc_html($module->post_title).'</a>'.$sep;
            }
            echo '<span>'.esc_html($lesson->post_title).'</span>';
        } elseif (is_singular('module')) {
            $module = get_post();
            $course_id = wp_get_post_parent_id($module->ID);
            if ($course_id) {
                $course = get_post($course_id);
                echo '<a href="'.esc_url(get_permalink($course)).'" style="color:#2196F3;text-decoration:none">'.esc_html($course->post_title).'</a>'.$sep;
            }
            echo '<span>'.esc_html($module->post_title).'</span>';
        } elseif (is_singular('course')) {
            $course = get_post();
            echo '<span>'.esc_html($course->post_title).'</span>';
        }
        echo '</div>';
    }
}
