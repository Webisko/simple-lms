<?php
namespace SimpleLMS\Bricks\Elements;
use SimpleLMS\LmsShortcodes;

if (!defined('ABSPATH')) exit;

class Lesson_Navigation extends \Bricks\Element {
    public $category = 'simple-lms';
    public $name = 'simple-lms-lesson-navigation';
    public $icon = 'ti-arrow-left';

    public function get_label() {
        return esc_html__('Lesson Navigation', 'simple-lms');
    }

    public function set_controls() {
        $this->controls['prevText'] = ['tab'=>'content','label'=>esc_html__('Previous Text','simple-lms'),'type'=>'text','default'=>'← Poprzednia'];
        $this->controls['nextText'] = ['tab'=>'content','label'=>esc_html__('Next Text','simple-lms'),'type'=>'text','default'=>'Następna →'];
    }

    public function render() {
        $lesson_id = get_the_ID();
        if (function_exists('SimpleLMS\\Compat\\Multilingual_Compat::map_post_id')) {
            $lesson_id = \SimpleLMS\Compat\Multilingual_Compat::map_post_id($lesson_id, 'lesson');
        }
        if (get_post_type($lesson_id) !== 'lesson') return;
        $prev = LmsShortcodes::getPreviousLesson($lesson_id);
        $next = LmsShortcodes::getNextLesson($lesson_id);
        echo '<div class="simple-lms-lesson-nav" style="display:flex;justify-content:space-between;gap:10px">';
        if ($prev) echo '<a href="'.esc_url(get_permalink($prev->ID)).'" class="nav-prev" style="padding:10px 16px;background:#2196F3;color:#fff;text-decoration:none;border-radius:4px">'.esc_html($this->settings['prevText']??'← Poprzednia').'</a>'; else echo '<span></span>';
        if ($next) echo '<a href="'.esc_url(get_permalink($next->ID)).'" class="nav-next" style="padding:10px 16px;background:#2196F3;color:#fff;text-decoration:none;border-radius:4px">'.esc_html($this->settings['nextText']??'Następna →').'</a>';
        echo '</div>';
    }
}
