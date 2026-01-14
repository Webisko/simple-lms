/**
 * Simple LMS Frontend JavaScript
 * 
 * @package SimpleLMS
 * @since 1.1.0
 */

(function($) {
    'use strict';

    // Progress tracking functionality
    const SimpleLmsProgress = {
        
        init: function() {
            this.bindEvents();
            this.trackTimeSpent();
            this.initBuilderToggleTexts();
        },

        bindEvents: function() {
            // Lesson completion button (plugin default)
            $(document).on('click', '.btn-lesson-complete', this.handleLessonComplete);
            // Builder button: any element linking to #simple-lms-toggle
            $(document).on('click', 'a[href="#simple-lms-toggle"], button[data-target="#simple-lms-toggle"]', this.handleBuilderToggle);
            // Builder button: class-based (no shortcode anchor needed)
            $(document).on('click', '.simple-lms-toggle', this.handleClassToggle);
            
            // Progress widget refresh
            $(document).on('click', '.refresh-progress', this.refreshProgress);
            
            // Auto-save progress on scroll
            $(window).on('scroll', this.trackScrollProgress);
        },

        initBuilderToggleTexts: function() {
            // Do NOT override initial HTML. Optionally set aria-label for a11y.
            const completed = simpleLms.lessonState && !!simpleLms.lessonState.completed;
            $('.simple-lms-toggle').each(function(){
                const $btn = jQuery(this);
                // Initialize state class for builder-driven styles
                $btn.toggleClass('simple-lms-completed', completed);
                const txtIncomplete = $btn.data('text-incomplete');
                const txtComplete = $btn.data('text-complete');
                const label = completed ? (txtComplete || $btn.attr('aria-label')) : (txtIncomplete || $btn.attr('aria-label'));
                if (label) $btn.attr('aria-label', label);

                // If lesson is already completed on load, align text and colors immediately
                const $textTarget = (function(){
                    const tSel = $btn.data('text-target');
                    if (tSel) {
                        const $t = $btn.find(String(tSel));
                        if ($t.length) return $t.eq(0);
                    }
                    return null;
                })();

                if (completed) {
                    // Update text to completed, if target and text provided
                    if ($textTarget && txtComplete) {
                        $textTarget.text(txtComplete);
                    }
                    // Swap colors on the actual clickable inner element
                    const $colorTarget = SimpleLmsProgress.resolveColorTarget($btn);
                    const currentBg = SimpleLmsProgress.getComputedColor($colorTarget, 'background-color');
                    const currentText = SimpleLmsProgress.getComputedColor($colorTarget, 'color');
                    const currentBorder = SimpleLmsProgress.getComputedColor($colorTarget, 'border-color');
                    if (!SimpleLmsProgress.isTransparent(currentText)) {
                        SimpleLmsProgress.setImportant($colorTarget, 'background-color', currentText);
                    }
                    if (!SimpleLmsProgress.isTransparent(currentBg)) {
                        SimpleLmsProgress.setImportant($colorTarget, 'color', currentBg);
                    }
                    if (!SimpleLmsProgress.isTransparent(currentBorder)) {
                        SimpleLmsProgress.setImportant($colorTarget, 'border-color', currentBorder);
                    }
                    if ($colorTarget.get(0) !== $btn.get(0)) {
                        $btn.css({ 'background-color': '', 'color': '', 'border-color': '' });
                    }
                } else {
                    // Ensure text reflects incomplete state if provided
                    if ($textTarget && txtIncomplete) {
                        $textTarget.text(txtIncomplete);
                    }
                }
            });
        },

        // For builder buttons pointing to the hidden anchor
        handleBuilderToggle: function(e) {
            e.preventDefault();
            const $trigger = $(this);
            const $anchor = $('#simple-lms-toggle[data-lesson-toggle]');
            if (!$anchor.length) return;
            // Determine element to read/apply colors (prefer inner <a>/<button>)
            const $colorTarget = SimpleLmsProgress.resolveColorTarget($trigger);
            const currentBg = SimpleLmsProgress.getComputedColor($colorTarget, 'background-color');
            const currentText = SimpleLmsProgress.getComputedColor($colorTarget, 'color');
            const currentBorder = SimpleLmsProgress.getComputedColor($colorTarget, 'border-color');
            // Perform AJAX using anchor data
            const lessonId = $anchor.data('lesson-id');
            const action = $anchor.data('action');
            const nonce = $anchor.data('nonce');
            if (!lessonId || !action || !nonce) return;
            // Avoid disabled on non-form controls to prevent theme grey-out
            const isFormControl = $trigger.is('button, input, select, textarea');
            if (isFormControl) {
                $trigger.prop('disabled', true);
            } else {
                $trigger.addClass('simple-lms-busy').css('pointer-events', 'none');
            }
            $.ajax({
                url: simpleLms.ajax_url,
                type: 'POST',
                data: { action, nonce, lesson_id: lessonId },
                success: function(response){
                    if (response && response.success) {
                        // Swap current bg/text colors unless explicit data-swap-* provided
                        const nowCompleted = (action === 'simple_lms_complete_lesson');
                        const swapBg = $trigger.data('swap-bg') || currentText;
                        const swapTx = $trigger.data('swap-text') || currentBg;
                        if (!SimpleLmsProgress.isTransparent(swapBg)) { SimpleLmsProgress.setImportant($colorTarget, 'background-color', swapBg); }
                        if (!SimpleLmsProgress.isTransparent(swapTx)) { SimpleLmsProgress.setImportant($colorTarget, 'color', swapTx); }
                        if (!SimpleLmsProgress.isTransparent(currentBorder)) { SimpleLmsProgress.setImportant($colorTarget, 'border-color', currentBorder); }
                        // Clean up any previously set wrapper inline styles (avoid dark backgrounds)
                        if ($colorTarget.get(0) !== $trigger.get(0)) {
                            $trigger.css({ 'background-color': '', 'color': '', 'border-color': '' });
                        }
                        // Update text if data attributes provided
                        const txtIncomplete = $trigger.data('text-incomplete');
                        const txtComplete = $trigger.data('text-complete');
                        
                        const textTarget = $trigger.data('text-target');
                        if (textTarget) {
                            const $t = $trigger.find(textTarget);
                            if ($t.length) {
                                if (nowCompleted && txtComplete) $t.text(txtComplete);
                                else if (!nowCompleted && txtIncomplete) $t.text(txtIncomplete);
                            }
                        } else if ($trigger.children().length === 0) {
                            // Only if no children (plain text button) to avoid nuking builder markup
                            if (nowCompleted && txtComplete) $trigger.text(txtComplete);
                            else if (!nowCompleted && txtIncomplete) $trigger.text(txtIncomplete);
                        } else {
                            // As a fallback, set aria-label only
                            const lbl = nowCompleted ? (txtComplete || '') : (txtIncomplete || '');
                            if (lbl) $trigger.attr('aria-label', lbl);
                        }
                        // Toggle state class for builder styling
                        $trigger.toggleClass('simple-lms-completed', nowCompleted);
                        // Flip anchor action for next click
                        const nextAction = (action === 'simple_lms_complete_lesson') ? 'simple_lms_uncomplete_lesson' : 'simple_lms_complete_lesson';
                        $anchor.data('action', nextAction);
                        // Optionally, flip text if the builder wants it: update text node if has data attributes
                        // Not enforced to keep full control in builder.
                        SimpleLmsProgress.updateNavigationStatus(response.data.lesson_id || lessonId, nextAction === 'simple_lms_uncomplete_lesson');
                        SimpleLmsProgress.showMessage(response.data.message || 'Status lekcji zaktualizowany', 'success');
                        SimpleLmsProgress.updateProgressBars();
                    } else {
                        SimpleLmsProgress.showMessage((response && response.data && response.data.message) || 'Wystąpił błąd', 'error');
                    }
                    if (isFormControl) {
                        $trigger.prop('disabled', false);
                    } else {
                        $trigger.removeClass('simple-lms-busy').css('pointer-events', '');
                    }
                },
                error: function(){
                    SimpleLmsProgress.showMessage('Wystąpił błąd podczas komunikacji z serwerem', 'error');
                    if (isFormControl) {
                        $trigger.prop('disabled', false);
                    } else {
                        $trigger.removeClass('simple-lms-busy').css('pointer-events', '');
                    }
                }
            });
        },

        // For builder buttons using class .simple-lms-toggle (no anchor element required)
        handleClassToggle: function(e) {
            e.preventDefault();
            const $btn = jQuery(this);
            const lessonId = $btn.data('lesson-id') || simpleLms.currentLessonId || 0;
            if (!lessonId) return;
            const completed = !!(simpleLms.lessonState && simpleLms.lessonState.completed);
            const action = completed ? 'simple_lms_uncomplete_lesson' : 'simple_lms_complete_lesson';
            // Read colors from the most relevant element (inner anchor/button if present)
            const $colorTarget = SimpleLmsProgress.resolveColorTarget($btn);
            const currentBg = SimpleLmsProgress.getComputedColor($colorTarget, 'background-color');
            const currentText = SimpleLmsProgress.getComputedColor($colorTarget, 'color');
            const currentBorder = SimpleLmsProgress.getComputedColor($colorTarget, 'border-color');

            // Avoid disabled on non-form controls to prevent theme grey-out
            const isFormControl = $btn.is('button, input, select, textarea');
            if (isFormControl) {
                $btn.prop('disabled', true);
            } else {
                $btn.addClass('simple-lms-busy').css('pointer-events', 'none');
            }
            jQuery.ajax({
                url: simpleLms.ajax_url,
                type: 'POST',
                data: { action, nonce: simpleLms.nonce, lesson_id: lessonId },
                success: function(response){
                    if (response && response.success) {
                        // Swap current bg/text colors (unless explicit data-swap-* provided)
                        const nowCompleted = !completed;
                        const swapBg = $btn.data('swap-bg') || currentText;
                        const swapTx = $btn.data('swap-text') || currentBg;
                        if (!SimpleLmsProgress.isTransparent(swapBg)) { SimpleLmsProgress.setImportant($colorTarget, 'background-color', swapBg); }
                        if (!SimpleLmsProgress.isTransparent(swapTx)) { SimpleLmsProgress.setImportant($colorTarget, 'color', swapTx); }
                        if (!SimpleLmsProgress.isTransparent(currentBorder)) { SimpleLmsProgress.setImportant($colorTarget, 'border-color', currentBorder); }
                        // Clean wrapper inline styles if target is inner element
                        if ($colorTarget.get(0) !== $btn.get(0)) {
                            $btn.css({ 'background-color': '', 'color': '', 'border-color': '' });
                        }
                        // Toggle internal state
                        if (!simpleLms.lessonState) simpleLms.lessonState = {};
                        simpleLms.lessonState.completed = nowCompleted;
                        // Toggle state class for builder styling
                        $btn.toggleClass('simple-lms-completed', nowCompleted);
                        // Update text from data attributes if provided
                        const txtIncomplete = $btn.data('text-incomplete');
                        const txtComplete = $btn.data('text-complete');
                        const textTarget = $btn.data('text-target');
                        if (textTarget) {
                            const $t = $btn.find(textTarget);
                            if ($t.length) {
                                if (nowCompleted && txtComplete) $t.text(txtComplete);
                                else if (!nowCompleted && txtIncomplete) $t.text(txtIncomplete);
                            }
                        } else if ($btn.children().length === 0) {
                            if (nowCompleted && txtComplete) $btn.text(txtComplete);
                            else if (!nowCompleted && txtIncomplete) $btn.text(txtIncomplete);
                        } else {
                            const lbl = nowCompleted ? (txtComplete || '') : (txtIncomplete || '');
                            if (lbl) $btn.attr('aria-label', lbl);
                        }
                        SimpleLmsProgress.updateNavigationStatus(response.data.lesson_id || lessonId, nowCompleted);
                        SimpleLmsProgress.showMessage(response.data.message || 'Status lekcji zaktualizowany', 'success');
                        SimpleLmsProgress.updateProgressBars();
                    } else {
                        SimpleLmsProgress.showMessage((response && response.data && response.data.message) || 'Wystąpił błąd', 'error');
                    }
                    if (isFormControl) {
                        $btn.prop('disabled', false);
                    } else {
                        $btn.removeClass('simple-lms-busy').css('pointer-events', '');
                    }
                },
                error: function(){
                    SimpleLmsProgress.showMessage('Wystąpił błąd podczas komunikacji z serwerem', 'error');
                    if (isFormControl) {
                        $btn.prop('disabled', false);
                    } else {
                        $btn.removeClass('simple-lms-busy').css('pointer-events', '');
                    }
                }
            });
        },

        getComputedColor: function($el, prop) {
            const el = $el.get(0);
            if (!el) return '';
            const styles = window.getComputedStyle(el);
            return styles ? styles.getPropertyValue(prop) : '';
        },

        resolveColorTarget: function($root) {
            // 1) explicit data-color-target selector inside root
            const selector = $root.data('color-target');
            if (selector) {
                const $found = $root.find(String(selector));
                if ($found.length) return $found.eq(0);
            }
            // 2) if root itself is a link/button
            if ($root.is('a,button')) return $root;
            // 3) common builder patterns
            const $auto = $root.find('a.elementor-button, a.bricks-button, a, button, .elementor-button, .bricks-button').eq(0);
            if ($auto.length) return $auto;
            // 4) fallback to root
            return $root;
        },

        setImportant: function($el, prop, value) {
            const el = $el.get(0);
            if (!el) return;
            try {
                el.style.setProperty(prop, value, 'important');
            } catch(e) {
                $el.css(prop, value);
            }
        },

        isTransparent: function(color) {
            if (!color) return true;
            const c = String(color).trim().toLowerCase();
            return c === 'transparent' || c === 'rgba(0, 0, 0, 0)' || c === 'rgba(0,0,0,0)';
        },

        handleLessonComplete: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const lessonId = $button.data('lesson-id');
            const action = $button.data('action');
            const nonce = $button.data('nonce');
            
            if ($button.prop('disabled')) {
                return;
            }
            
            $button.prop('disabled', true);
            
            // Show spinner
            const $spinner = $button.find('.spinner');
            const $text = $button.find('.button-text');
            $spinner.show();
            
            $.ajax({
                url: simpleLms.ajax_url,
                type: 'POST',
                data: {
                    action: action,
                    nonce: nonce,
                    lesson_id: lessonId
                },
                success: function(response) {
                    if (response.success) {
                        // Get lesson ID from response for better reliability
                        const responseLessonId = response.data.lesson_id || lessonId;
                        
                        // Toggle button state
                        if (action === 'simple_lms_complete_lesson') {
                            $button.addClass('completed');
                            $button.data('action', 'simple_lms_uncomplete_lesson');
                            const txt = (simpleLms.strings && simpleLms.strings.completedToggleOff) || 'Oznacz jako nieukończoną';
                            $text.text(txt);
                            // Colors/fonts now fully controlled by theme/builder
                            
                            // Update navigation status to completed
                            SimpleLmsProgress.updateNavigationStatus(responseLessonId, true);
                        } else {
                            $button.removeClass('completed');
                            $button.data('action', 'simple_lms_complete_lesson');
                            const txi = (simpleLms.strings && simpleLms.strings.markComplete) || 'Oznacz jako ukończoną';
                            $text.text(txi);
                            // Colors/fonts now fully controlled by theme/builder
                            
                            // Update navigation status to incomplete
                            SimpleLmsProgress.updateNavigationStatus(responseLessonId, false);
                        }
                        
                        SimpleLmsProgress.showMessage(response.data.message || 'Status lekcji zaktualizowany', 'success');
                        SimpleLmsProgress.updateProgressBars();
                    } else {
                        SimpleLmsProgress.showMessage(response.data.message || 'Wystąpił błąd', 'error');
                    }
                    
                    $button.prop('disabled', false);
                    $spinner.hide();
                },
                error: function() {
                    SimpleLmsProgress.showMessage('Wystąpił błąd podczas komunikacji z serwerem', 'error');
                    $button.prop('disabled', false);
                    $spinner.hide();
                }
            });
        },

        refreshProgress: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const courseId = $button.data('course-id') || 0;
            
            $button.prop('disabled', true);
            $button.html('<span class="simple-lms-loading"></span>');
            
            $.ajax({
                url: simpleLms.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_user_progress',
                    nonce: simpleLms.nonce,
                    course_id: courseId
                },
                success: function(response) {
                    if (response.success) {
                        SimpleLmsProgress.updateProgressDisplay(response.data);
                        SimpleLmsProgress.showMessage('Postęp zaktualizowany', 'success');
                    } else {
                        SimpleLmsProgress.showMessage(response.data.message, 'error');
                    }
                    $button.prop('disabled', false);
                    $button.html('🔄');
                },
                error: function() {
                    SimpleLmsProgress.showMessage(simpleLms.strings.error, 'error');
                    $button.prop('disabled', false);
                    $button.html('🔄');
                }
            });
        },

        updateProgressBars: function() {
            $('.progress-fill').each(function() {
                const $fill = $(this);
                const $bar = $fill.closest('.progress-bar');
                const $text = $bar.siblings('.progress-text');
                
                // Animate progress bar update
                setTimeout(function() {
                    // This would be updated with real data from server
                    // For now, just add visual feedback
                    $fill.css('background', 'linear-gradient(90deg, #46b450 0%, #3a9b3f 100%)');
                    $bar.closest('.course-progress').addClass('updated');
                }, 500);
            });
        },

        updateNavigationStatus: function(lessonId, isCompleted) {
            // Find all completion status elements for this lesson in navigation
            const $statusElements = $('.completion-status[data-lesson-id="' + lessonId + '"]');
            const $lessonItems = $('.lesson-item[data-lesson-id="' + lessonId + '"]');
            
            if ($statusElements.length > 0) {
                $statusElements.each(function() {
                    const $status = $(this);
                    
                    if (isCompleted) {
                        // Update to completed state
                        $status.removeClass('incomplete').addClass('completed');
                        $status.text('✓');
                        
                        // Add completed class to lesson item
                        $status.closest('.lesson-item').addClass('completed-lesson');
                        
                        // Add visual feedback animation - scale effect
                        $status.css('transform', 'scale(1.15)');
                        setTimeout(function() {
                            $status.css('transform', 'scale(1)');
                        }, 150);
                        
                    } else {
                        // Update to incomplete state
                        $status.removeClass('completed').addClass('incomplete');
                        $status.text('');
                        
                        // Remove completed class from lesson item
                        $status.closest('.lesson-item').removeClass('completed-lesson');
                        
                        // Small scale animation for visual feedback
                        $status.css('transform', 'scale(0.9)');
                        setTimeout(function() {
                            $status.css('transform', 'scale(1)');
                        }, 150);
                    }
                });
            }
        },

        updateProgressDisplay: function(progressData) {
            if (!progressData.overall_progress) {
                return;
            }
            
            progressData.overall_progress.forEach(function(courseProgress) {
                const courseId = courseProgress.course_id;
                const percentage = Math.round(courseProgress.completion_percentage);
                
                // Update progress bars for this course
                $(`.course-progress[data-course-id="${courseId}"] .progress-fill`).css('width', percentage + '%');
                $(`.course-progress[data-course-id="${courseId}"] .progress-text`).text(percentage + '%');
                
                // Update detailed stats if visible
                const $stats = $(`.progress-stats[data-course-id="${courseId}"]`);
                if ($stats.length) {
                    $stats.find('.completed-lessons').text(courseProgress.completed_lessons);
                    $stats.find('.total-lessons').text(courseProgress.total_lessons);
                    $stats.find('.percentage').text(percentage + '%');
                }
            });
        },

        trackTimeSpent: function() {
            if (!$('.simple-lms-lesson-widget').length) {
                return;
            }
            
            let startTime = Date.now();
            let isActive = true;
            
            // Track when user becomes inactive
            $(document).on('visibilitychange', function() {
                if (document.hidden) {
                    isActive = false;
                    SimpleLmsProgress.saveTimeSpent(Date.now() - startTime);
                } else {
                    isActive = true;
                    startTime = Date.now();
                }
            });
            
            // Save time spent every 30 seconds
            setInterval(function() {
                if (isActive) {
                    SimpleLmsProgress.saveTimeSpent(Date.now() - startTime);
                    startTime = Date.now();
                }
            }, 30000);
            
            // Save on page unload
            $(window).on('beforeunload', function() {
                if (isActive) {
                    SimpleLmsProgress.saveTimeSpent(Date.now() - startTime);
                }
            });
        },

        saveTimeSpent: function(timeSpent) {
            const lessonId = $('.btn-lesson-complete').data('lesson-id');
            
            if (!lessonId || timeSpent < 1000) {
                return;
            }
            
            // Save to localStorage as backup
            const stored = localStorage.getItem('simple_lms_time_' + lessonId) || 0;
            const total = parseInt(stored) + Math.floor(timeSpent / 1000);
            localStorage.setItem('simple_lms_time_' + lessonId, total);
            
            // Send to server (non-blocking)
            navigator.sendBeacon(simpleLms.ajax_url, new URLSearchParams({
                action: 'update_lesson_time',
                nonce: simpleLms.nonce,
                lesson_id: lessonId,
                time_spent: Math.floor(timeSpent / 1000)
            }));
        },

        getTimeSpent: function() {
            const lessonId = $('.btn-lesson-complete').data('lesson-id');
            return localStorage.getItem('simple_lms_time_' + lessonId) || 0;
        },

        trackScrollProgress: function() {
            if (!$('.simple-lms-lesson-widget').length) {
                return;
            }
            
            const $content = $('.lesson-content');
            if (!$content.length) {
                return;
            }
            
            const contentTop = $content.offset().top;
            const contentHeight = $content.outerHeight();
            const windowHeight = $(window).height();
            const scrollTop = $(window).scrollTop();
            
            const scrolled = Math.max(0, scrollTop + windowHeight - contentTop);
            const percentage = Math.min(100, (scrolled / contentHeight) * 100);
            
            // Save scroll progress to localStorage
            const lessonId = $('.btn-lesson-complete').data('lesson-id');
            if (lessonId) {
                localStorage.setItem('simple_lms_scroll_' + lessonId, percentage);
            }
            
            // Auto-complete if scrolled to bottom and spent enough time
            if (percentage >= 95) {
                const timeSpent = SimpleLmsProgress.getTimeSpent();
                if (timeSpent >= 30) { // At least 30 seconds
                    const $button = $('.btn-lesson-complete');
                    if (!$button.hasClass('completed') && !$button.prop('disabled')) {
                        // Show auto-complete notification
                        SimpleLmsProgress.showAutoCompleteOption();
                    }
                }
            }
        },

        showAutoCompleteOption: function() {
            if ($('.auto-complete-notification').length) {
                return;
            }
            
            const notification = $('<div class="auto-complete-notification simple-lms-success">')
                .html('Przeczytałeś całą lekcję. <button type="button" class="btn-auto-complete">Oznacz jako ukończoną</button>')
                .css({
                    position: 'fixed',
                    bottom: '20px',
                    right: '20px',
                    zIndex: 9999,
                    maxWidth: '300px'
                });
            
            $('body').append(notification);
            
            notification.find('.btn-auto-complete').on('click', function() {
                $('.btn-lesson-complete').trigger('click');
                notification.fadeOut(function() {
                    notification.remove();
                });
            });
            
            // Auto-hide after 10 seconds
            setTimeout(function() {
                notification.fadeOut(function() {
                    notification.remove();
                });
            }, 10000);
        },

        showMessage: function(message, type) {
            const $existing = $('.simple-lms-message');
            if ($existing.length) {
                $existing.remove();
            }
            
            const $message = $('<div class="simple-lms-message simple-lms-' + type + '">')
                .text(message)
                .css({
                    position: 'fixed',
                    top: '20px',
                    right: '20px',
                    zIndex: 9999,
                    minWidth: '200px'
                });
            
            $('body').append($message);
            
            setTimeout(function() {
                $message.fadeOut(function() {
                    $message.remove();
                });
            }, 5000);
        }
    };

    // Course grid functionality
    const SimpleLmsCourses = {
        
        init: function() {
            this.initLazyLoading();
            this.initFilterSort();
        },

        initLazyLoading: function() {
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver(function(entries, observer) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.classList.remove('lazy');
                            imageObserver.unobserve(img);
                        }
                    });
                });

                document.querySelectorAll('img[data-src]').forEach(function(img) {
                    imageObserver.observe(img);
                });
            }
        },

        initFilterSort: function() {
            // Add filter/sort controls if they exist
            $('.course-filter').on('change', this.filterCourses);
            $('.course-sort').on('change', this.sortCourses);
        },

        filterCourses: function() {
            const filterValue = $(this).val();
            const $courses = $('.course-card');
            
            if (!filterValue) {
                $courses.show();
                return;
            }
            
            $courses.each(function() {
                const $course = $(this);
                const categories = $course.data('categories') || '';
                
                if (categories.includes(filterValue)) {
                    $course.show();
                } else {
                    $course.hide();
                }
            });
        },

        sortCourses: function() {
            const sortValue = $(this).val();
            const $container = $('.simple-lms-courses-grid');
            const $courses = $container.find('.course-card').detach();
            
            let sortedCourses;
            
            switch (sortValue) {
                case 'title':
                    sortedCourses = $courses.sort(function(a, b) {
                        const titleA = $(a).find('h3').text().toLowerCase();
                        const titleB = $(b).find('h3').text().toLowerCase();
                        return titleA.localeCompare(titleB);
                    });
                    break;
                    
                case 'progress':
                    sortedCourses = $courses.sort(function(a, b) {
                        const progressA = parseFloat($(a).find('.progress-text').text()) || 0;
                        const progressB = parseFloat($(b).find('.progress-text').text()) || 0;
                        return progressB - progressA;
                    });
                    break;
                    
                default:
                    sortedCourses = $courses;
            }
            
            $container.append(sortedCourses);
        }
    };

    // Accessibility enhancements
    const SimpleLmsA11y = {
        
        init: function() {
            this.enhanceKeyboardNavigation();
            this.addAriaLabels();
            this.manageFocus();
        },

        enhanceKeyboardNavigation: function() {
            // Make progress bars focusable with keyboard
            $('.progress-bar').attr('tabindex', '0').attr('role', 'progressbar');
            
            // Add keyboard support for lesson completion
            $('.btn-lesson-complete').on('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(this).trigger('click');
                }
            });
        },

        addAriaLabels: function() {
            // Add aria-labels to progress bars
            $('.progress-bar').each(function() {
                const $bar = $(this);
                const percentage = $bar.find('.progress-fill').attr('style')?.match(/width:\s*(\d+)%/)?.[1] || '0';
                $bar.attr('aria-valuenow', percentage);
                $bar.attr('aria-valuemin', '0');
                $bar.attr('aria-valuemax', '100');
                $bar.attr('aria-label', `Postęp kursu: ${percentage}%`);
            });
            
            // Add aria-labels to navigation buttons
            $('.btn-prev').attr('aria-label', 'Przejdź do poprzedniej lekcji');
            $('.btn-next').attr('aria-label', 'Przejdź do następnej lekcji');
        },

        manageFocus: function() {
            // Manage focus after AJAX updates
            $(document).on('SimpleLmsProgressUpdated', function(e, data) {
                if (data.success) {
                    // Announce to screen readers
                    SimpleLmsA11y.announce(data.message);
                }
            });
        },

        announce: function(message) {
            const $announcer = $('#simple-lms-announcer');
            if ($announcer.length) {
                $announcer.text(message);
            } else {
                $('<div id="simple-lms-announcer" aria-live="polite" aria-atomic="true" class="sr-only">')
                    .text(message)
                    .appendTo('body');
            }
        }
    };

    // Course Navigation Accordion
    const SimpleLmsAccordion = {
        
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $(document).on('click', '.simple-lms-course-navigation .accordion-header', this.toggleAccordion);
        },

        toggleAccordion: function(e) {
            e.preventDefault();
            
            const $header = $(this);
            const $module = $header.closest('.accordion-module');
            const $content = $module.find('.accordion-content');
            
            // Toggle current panel
            if ($content.hasClass('expanded')) {
                $content.removeClass('expanded').slideUp(300);
                $header.removeClass('active');
            } else {
                $content.addClass('expanded').slideDown(300);
                $header.addClass('active');
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        SimpleLmsProgress.init();
        SimpleLmsCourses.init();
        SimpleLmsA11y.init();
        SimpleLmsAccordion.init();
        // Access control visibility: toggle elements based on course access
        try {
            if (window.simpleLmsAccess && typeof window.simpleLmsAccess.hasAccess !== 'undefined') {
                if (window.simpleLmsAccess.hasAccess) {
                    $('body').addClass('simple-lms-has-access').removeClass('simple-lms-no-access');
                    $('.simple-lms-with-access').show();
                    $('.simple-lms-without-access').hide();
                } else {
                    $('body').addClass('simple-lms-no-access').removeClass('simple-lms-has-access');
                    $('.simple-lms-with-access').hide();
                    $('.simple-lms-without-access').show();
                }
            }
        } catch(e) { /* no-op */ }
        
        // Trigger custom event for other plugins
        $(document).trigger('SimpleLmsReady');
    });

})(jQuery);
