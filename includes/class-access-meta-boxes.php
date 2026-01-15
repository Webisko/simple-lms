<?php

declare(strict_types=1);

namespace SimpleLMS;

/**
 * Admin meta boxes for tag-based course access
 */

if (!defined('ABSPATH')) { exit; }

use function \add_action;
use function \add_meta_box;
use function \check_admin_referer;
use function \current_user_can;
use function \esc_attr;
use function \esc_html;
use function \esc_html__;
use function \get_post_meta;
use function \get_userdata;
use function \get_users;
use function \sanitize_text_field;
use function \update_user_meta;
use function \wp_nonce_field;
use function \__;

class Access_Meta_Boxes {
    public static function init(): void {
        // Course edit screen: show users with access
        \add_action('add_meta_boxes', [__CLASS__, 'registerCourseAccessBox']);
        
        // User profile screen: manage course tags
        \add_action('show_user_profile', [__CLASS__, 'renderUserCourseAccess']);
        \add_action('edit_user_profile', [__CLASS__, 'renderUserCourseAccess']);
        \add_action('personal_options_update', [__CLASS__, 'saveUserCourseAccess']);
        \add_action('edit_user_profile_update', [__CLASS__, 'saveUserCourseAccess']);
    }

    public static function registerCourseAccessBox(): void {
        \add_meta_box(
            'simple_lms_course_access',
            \__('Users with access (tags)', 'simple-lms'),
            [__CLASS__, 'renderCourseAccessBox'],
            'course',
            'side',
            'low'
        );
    }

    public static function renderCourseAccessBox($post): void {
        $course_id = (int) $post->ID;
        
        // Show access duration setting
        $duration_value = (int) \get_post_meta($course_id, '_access_duration_value', true);
        $duration_unit = \get_post_meta($course_id, '_access_duration_unit', true) ?: 'days';
        
        if ($duration_value > 0) {
            $unit_labels = [
                'days' => _n('%d dzień', '%d dni', $duration_value, 'simple-lms'),
                'weeks' => _n('%d tydzień', '%d tygodni', $duration_value, 'simple-lms'),
                'months' => _n('%d miesiąc', '%d miesięcy', $duration_value, 'simple-lms'),
                'years' => _n('%d rok', '%d lat', $duration_value, 'simple-lms')
            ];
            
            echo '<div style="Padding: 8px; background: #e7f5fe; border-left: 3px solid #0073aa; margin-bottom: 12px;">';
            echo '<strong>⏱️ ' . \esc_html__('Access duration:', 'simple-lms') . '</strong> ';
            echo sprintf(
                $unit_labels[$duration_unit] ?? $unit_labels['days'],
                $duration_value
            );
            echo '</div>';
        }
        
        // Get all users who have this course in their access tags
        $users_with_access = [];
        $all_users = \get_users(['fields' => ['ID', 'user_login', 'display_name']]);
        
        foreach ($all_users as $user) {
            if (\SimpleLMS\simple_lms_user_has_course_access((int)$user->ID, $course_id)) {
                $users_with_access[] = [
                    'user' => $user,
                    'expiration' => \SimpleLMS\simple_lms_get_course_access_expiration((int)$user->ID, $course_id),
                    'days_remaining' => \SimpleLMS\simple_lms_get_course_access_days_remaining((int)$user->ID, $course_id)
                ];
            }
        }

        if (empty($users_with_access)) {
            echo '<p style="color: #666;">' . \esc_html__('No users with access.', 'simple-lms') . '</p>';
            return;
        }

        echo '<div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; Padding: 8px; background: #f9f9f9;">';
        echo '<ul style="margin: 0; Padding: 0; list-style: none;">';
        foreach ($users_with_access as $data) {
            $user = $data['user'];
            $expiration = $data['expiration'];
            $days_remaining = $data['days_remaining'];
            $edit_link = \admin_url('user-edit.php?user_id=' . $user->ID);
            
            $warning_style = '';
            if ($days_remaining !== null && $days_remaining <= 7) {
                $warning_style = ' style="border-left: 3px solid #d63638; Padding-left: 8px; background: #fff;"';
            }
            
            echo '<li style="margin: 6px 0; Padding: 6px; background: white; border-radius: 3px;"' . $warning_style . '>';
            echo '<a href="' . \esc_attr($edit_link) . '" target="_blank" style="font-weight: 500;">' . \esc_html($user->display_name) . '</a>';
            echo ' <span style="color: #999; font-size: 0.9em;">(' . \esc_html($user->user_login) . ')</span>';
            
            if ($expiration !== null) {
                echo '<br><span style="font-size: 0.85em; color: #666;">';
                echo '📅 ' . date_i18n('Y-m-d H:i', $expiration);
                if ($days_remaining !== null) {
                    if ($days_remaining === 0) {
                        echo ' <span style="color: #d63638; font-weight: bold;">(' . \esc_html__('EXPIRED', 'simple-lms') . ')</span>';
                    } elseif ($days_remaining <= 7) {
                        echo ' <span style="color: #d63638;">(' . sprintf(
                            _n('pozostał %d dzień', 'pozostało %d dni', $days_remaining, 'simple-lms'),
                            $days_remaining
                        ) . ')</span>';
                    } else {
                        echo ' (' . sprintf(
                            _n('pozostał %d dzień', 'pozostało %d dni', $days_remaining, 'simple-lms'),
                            $days_remaining
                        ) . ')';
                    }
                }
                echo '</span>';
            } else {
                echo '<br><span style="font-size: 0.85em; color: #00a32a;">✓ ' . \esc_html__('Bezterminowy', 'simple-lms') . '</span>';
            }
            
            echo '</li>';
        }
        echo '</ul>';
        echo '</div>';
        
        echo '<p style="margin-top: 12px; font-size: 12px; color: #666;">';
        echo \esc_html(sprintf(\__('Total: %d users', 'simple-lms'), count($users_with_access)));
        echo '</p>';
    }

    public static function renderUserCourseAccess($user): void {
        if (!\current_user_can('edit_users')) { return; }

        $user_id = (int) $user->ID;
        $key = \SimpleLMS\simple_lms_get_course_access_meta_key();
        $access_tags = (array) \get_user_meta($user_id, $key, true);
        
        // Get all courses
        $courses = \get_posts([
            'post_type' => 'course',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        echo '<h2>' . \esc_html__('Course Access (Simple LMS)', 'simple-lms') . '</h2>';
        echo '<table class="form-table">';
        echo '<tr>';
        echo '<th>' . \esc_html__('Przypisane kursy', 'simple-lms') . '</th>';
        echo '<td>';
        
        \wp_nonce_field('simple_lms_user_access', 'simple_lms_user_access_nonce');
        
        if (empty($courses)) {
            echo '<p>' . \esc_html__('No courses in the system.', 'simple-lms') . '</p>';
        } else {
            echo '<fieldset style="border: 1px solid #ddd; Padding: 12px; background: #f9f9f9; max-height: 300px; overflow-y: auto;">';
            echo '<legend style="Padding: 0 8px; font-weight: bold;">' . \esc_html__('Select courses the user has access to:', 'simple-lms') . '</legend>';
            
            foreach ($courses as $course) {
                $checked = in_array((int)$course->ID, array_map('intval', $access_tags), true);
                $expiration = \SimpleLMS\simple_lms_get_course_access_expiration($user_id, (int)$course->ID);
                $days_remaining = \SimpleLMS\simple_lms_get_course_access_days_remaining($user_id, (int)$course->ID);
                
                echo '<div style="display: flex; align-items: center; margin: 8px 0; Padding: 8px; background: white; border-left: 3px solid ' . ($checked ? '#00a32a' : '#ddd') . ';">';
                echo '<label style="flex: 1; margin: 0;">';
                echo '<input type="checkbox" name="simple_lms_course_access[]" value="' . \esc_attr((string)$course->ID) . '"' . ($checked ? ' checked' : '') . '> ';
                echo '<strong>' . \esc_html($course->post_title) . '</strong>';
                echo ' <span style="color: #999; font-size: 0.9em;">(ID: ' . $course->ID . ')</span>';
                
                if ($checked && $expiration !== null) {
                    $expiration_date = date_i18n('Y-m-d H:i', $expiration);
                    $warning_style = ($days_remaining !== null && $days_remaining <= 7) ? 'color: #d63638; font-weight: bold;' : 'color: #666;';
                    echo '<br><span style="font-size: 0.85em; ' . $warning_style . '">';
                    echo '📅 ' . \esc_html__('Wygasa:', 'simple-lms') . ' ' . $expiration_date;
                    if ($days_remaining !== null) {
                        if ($days_remaining === 0) {
                            echo ' <strong>(' . \esc_html__('EXPIRED', 'simple-lms') . ')</strong>';
                        } else {
                            echo ' (' . sprintf(
                                _n('pozostał %d dzień', 'pozostało %d dni', $days_remaining, 'simple-lms'),
                                $days_remaining
                            ) . ')';
                        }
                    }
                    echo '</span>';
                    
                    // Field to modify expiration date
                    echo '<br><label style="font-size: 0.85em; margin-top: 4px;">';
                    echo \esc_html__('Change expiration date:', 'simple-lms') . ' ';
                    echo '<input type="datetime-local" name="simple_lms_course_expiration[' . $course->ID . ']" ';
                    echo 'value="' . date('Y-m-d\TH:i', $expiration) . '" ';
                    echo 'style="font-size: 0.9em;">';
                    echo ' <button type="button" class="button button-small" onclick="this.previousElementSibling.value=\'\'">';
                    echo \esc_html__('Remove limit', 'simple-lms');
                    echo '</button>';
                    echo '</label>';
                } elseif ($checked) {
                    echo '<br><span style="font-size: 0.85em; color: #00a32a;">✓ ' . \esc_html__('Lifetime access', 'simple-lms') . '</span>';
                    
                    // Field to add expiration date
                    echo '<br><label style="font-size: 0.85em; margin-top: 4px;">';
                    echo \esc_html__('Set expiration date:', 'simple-lms') . ' ';
                    echo '<input type="datetime-local" name="simple_lms_course_expiration[' . $course->ID . ']" ';
                    echo 'style="font-size: 0.9em;">';
                    echo '</label>';
                }
                
                echo '</label>';
                echo '</div>';
            }
            
            echo '</fieldset>';
        }
        
        echo '<p class="description">' . \esc_html__('Use the checkboxes above to assign or remove course access. Changes will be saved with the user profile.', 'simple-lms') . '</p>';
        echo '</td>';
        echo '</tr>';
        echo '</table>';
    }

    public static function saveUserCourseAccess($user_id): void {
        if (!\current_user_can('edit_users')) { return; }
        if (!isset($_POST['simple_lms_user_access_nonce']) || !\wp_verify_nonce(\sanitize_text_field(\wp_unslash($_POST['simple_lms_user_access_nonce'])), 'simple_lms_user_access')) {
            return;
        }

        $key = \SimpleLMS\simple_lms_get_course_access_meta_key();
        $new_tags = isset($_POST['simple_lms_course_access']) && is_array($_POST['simple_lms_course_access'])
            ? array_map('intval', $_POST['simple_lms_course_access'])
            : [];

        \update_user_meta($user_id, $key, array_values(array_unique($new_tags)));
        
        // Handle expiration dates
        if (isset($_POST['simple_lms_course_expiration']) && is_array($_POST['simple_lms_course_expiration'])) {
            foreach ($_POST['simple_lms_course_expiration'] as $course_id => $expiration_date) {
                $course_id = intval($course_id);
                if ($course_id <= 0) continue;
                
                // Only process if user has access to this course
                if (!in_array($course_id, $new_tags, true)) continue;
                
                $expiration_date = \sanitize_text_field($expiration_date);
                
                if (empty($expiration_date)) {
                    // Remove expiration (unlimited access)
                    \delete_user_meta($user_id, 'simple_lms_course_access_expiration_' . $course_id);
                } else {
                    // Convert datetime-local format to timestamp
                    $timestamp = strtotime($expiration_date);
                    if ($timestamp !== false && $timestamp > 0) {
                        \update_user_meta($user_id, 'simple_lms_course_access_expiration_' . $course_id, $timestamp);
                        
                        // Invalidate cache
                        \delete_transient('slms_access_' . $user_id . '_' . $course_id);
                    }
                }
            }
        }
    }
}

// Access_Meta_Boxes is now managed by ServiceContainer
// and instantiated in Plugin::registerLateServices()
