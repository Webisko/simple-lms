// SimpleLMS Admin JavaScript
(function($) {
    'use strict';

    const SimpleLMS = {
        init: function() {
            this.bindEvents();
            this.initSortable();
            this.initToggleLessons();
        },

        bindEvents: function() {
            // Unbind all existing events first
            $(document).off('click', '.delete-lesson');
            $(document).off('click', '.duplicate-lesson');
            $('.add-lessons-btn').off('click');

            // Module actions
            $('#add-module-btn').on('click', this.handleAddModule);
            $(document).on('click', '.duplicate-module', this.handleDuplicateModule);
            $(document).on('click', '.delete-module', this.handleDeleteModule);
            
            // Lesson actions
            $(document).on('click', '.delete-lesson', this.handleDeleteLesson);
            $(document).on('click', '.duplicate-lesson', this.handleDuplicateLesson);
            $('.add-lessons-btn').on('click', this.handleAddLesson);
            
            // Handle enter key in input fields
            $('input[name="new_module_title"]').on('keypress', this.handleModuleEnterKey);
            $('input[name^="new_lesson_title_"]').on('keypress', this.handleLessonEnterKey);

            // Handle status toggle for lessons and modules
            $(document).on('change', '.toggle-input', function(e) {
                e.preventDefault();
                const $toggle = $(this);
                const id = $toggle.data('id');
                const type = $toggle.data('type');
                const isChecked = $toggle.prop('checked');
                const newStatus = isChecked ? 'publish' : 'draft';
                const action = type === 'lesson' ? 'update_lesson_status' : 'update_module_status';
                
                const ajaxUrl = (typeof ajaxurl !== 'undefined' && ajaxurl) ? ajaxurl : (simpleLMS && simpleLMS.ajaxurl);
                const nonce = (simpleLMS && simpleLMS.nonce) ? simpleLMS.nonce : ($('#course_nonce_field').val() || $('#module_nonce_field').val() || '');
                
                console.log('Toggle clicked:', {type, id, newStatus, action, ajaxUrl, noncePresent: !!nonce});

                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: action,
                        security: nonce,
                        nonce: nonce,
                        [type + '_id']: id,
                        status: newStatus
                    },
                    success: function(response) {
                        console.log('AJAX response:', response);
                        if (response.success) {
                            const statusLabel = $toggle.closest('.lesson-status-toggle, .module-status-toggle')
                                .find('.status-label');
                            
                            console.log('Updating label, found:', statusLabel.length);
                            
                            // Zawsze używaj 'Opublikowano' i 'Szkic', niezależnie od typu
                            statusLabel.text(isChecked ? 'Opublikowano' : 'Szkic');
                            statusLabel.attr('data-status', isChecked ? 'published' : 'draft');
                            
                            // Jeśli moduł zmieniony na szkic, zmień wszystkie lekcje na szkic
                            if (type === 'module' && !isChecked) {
                                console.log('Module changed to draft, updating lessons...');
                                console.log('response.data:', response.data);
                                console.log('response.data.lessons:', response.data.lessons);
                                
                                if (response.data.lessons && response.data.lessons.length > 0) {
                                    console.log('Found ' + response.data.lessons.length + ' lessons to update');
                                    response.data.lessons.forEach(function(lesson) {
                                        console.log('Updating lesson UI for lesson ID:', lesson.id);
                                        const $lessonToggle = $(`.lesson-item[data-lesson-id="${lesson.id}"]`).find('.toggle-input');
                                        console.log('Found toggle for lesson ' + lesson.id + ':', $lessonToggle.length);
                                        $lessonToggle.prop('checked', false);
                                        const $lessonLabel = $lessonToggle.closest('.lesson-status-toggle').find('.status-label');
                                        console.log('Found label for lesson ' + lesson.id + ':', $lessonLabel.length);
                                        $lessonLabel.text('Szkic');
                                        $lessonLabel.attr('data-status', 'draft');
                                    });
                                } else {
                                    console.log('No lessons found in response or lessons array is empty');
                                }
                            }
                        } else {
                            console.error('AJAX failed:', response);
                            const errorMsg = response.data ? response.data.message : (simpleLMS.i18n.error_generic || 'Błąd');
                            console.error('Error message:', errorMsg);
                            console.error('Full response:', JSON.stringify(response));
                            alert(errorMsg);
                            $toggle.prop('checked', !isChecked); // Revert toggle state
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', {xhr, status, error});
                        console.error('Response text:', xhr.responseText);
                        const errorMsg = (simpleLMS.i18n && simpleLMS.i18n.error_generic) || 'Wystąpił błąd';
                        console.error('Displayed error:', errorMsg);
                        alert(errorMsg + '\n\nDEBUG: ' + status + ' - ' + error);
                        $toggle.prop('checked', !isChecked); // Revert toggle state
                    }
                });
            });
        },

        handleModuleEnterKey: function(e) {
            if (e.which === 13) {
                $('#add-module-btn').click();
                return false;
            }
        },

        handleLessonEnterKey: function(e) {
            if (e.which === 13) {
                $(this).closest('.add-lesson-form, .module-add-lessons').find('.add-lessons-btn').click();
                return false;
            }
        },

        handleAddModule: function() {
            const courseId = $(this).data('course-id');
            const moduleTitle = $('input[name="new_module_title"]').val().trim();
            if (!moduleTitle) {
                SimpleLMS.showError(simpleLMS.i18n.enter_module_title || 'Please enter a module title.');
                return;
            }
            const $btn = $(this);
            $btn.prop('disabled', true);
            SimpleLMS.ajaxRequest({
                action: 'add_new_module',
                course_id: courseId,
                module_title: moduleTitle,
                nonce: simpleLMS.nonce
            }, function(response) {
                $btn.prop('disabled', false);
                $('input[name="new_module_title"]').val('');
                if (response && response.success && response.data && response.data.module_id) {
                    const newId = response.data.module_id;
                    const html = `<li class="module-item" id="module-item-${newId}" data-module-id="${newId}">
                        <div class="module-header">
                            <span class="module-drag-handle"></span>
                            <span class="module-title">
                                <span class="module-toggle-container"><span class="module-toggle chevron-down" data-module-id="${newId}"></span></span>
                                <a href="${simpleLMS.editModuleUrl.replace('MODULE_ID', newId)}" class="module-title-link">${$('<div>').text(moduleTitle).html()}</a>
                                <span class="module-lesson-count"> (0)</span>
                            </span>
                            <div class="module-actions">
                                <div class="module-status-toggle">
                                    <label class="switch"><input type="checkbox" class="toggle-input" data-id="${newId}" data-type="module"><span class="slider round"></span></label>
                                </div>
                                <a href="#" class="duplicate-module" data-module-id="${newId}">${simpleLMS.i18n.duplicate_module || 'Duplikuj'}</a>
                                <a href="#" class="delete-module delete-button" data-module-id="${newId}">${simpleLMS.i18n.delete_module || 'Usuń'}</a>
                            </div>
                        </div>
                        <ul class="module-lessons-list"></ul>
                        <div class="add-lesson-form"><h3 class="add-lesson-heading">${simpleLMS.i18n.add_lesson || 'Dodaj lekcję'}</h3><input type="text" name="new_lesson_title_${newId}" placeholder="${simpleLMS.i18n.lesson_title || 'Tytuł lekcji'}" class="widefat" /><button type="button" class="button button-primary add-lessons-btn" data-module-id="${newId}">${simpleLMS.i18n.add_lesson || 'Dodaj lekcję'}</button></div>
                    </li>`;
                    var $list = $('#add-module-btn').closest('.course-add-module').siblings('.course-modules-list');
                    if (!$list.length) $list = $('.course-modules-list');
                    $list.append(html);
                    // Re-init events and sortable for new elements
                    SimpleLMS.bindEvents();
                    SimpleLMS.initSortable();
                }
            });
        },

        handleAddLesson: function() {
            const moduleId = $(this).data('module-id');
            const $input = $('input[name="new_lesson_title_' + moduleId + '"]');
            const lessonTitle = $input.val().trim();
            if (!lessonTitle) {
                SimpleLMS.showError(simpleLMS.i18n.enter_lesson_title || 'Please enter a lesson title.');
                return;
            }
            const $btn = $(this);
            $btn.prop('disabled', true);
            SimpleLMS.ajaxRequest({
                action: 'add_new_lesson_from_module',
                module_id: moduleId,
                lesson_title: lessonTitle,
                nonce: simpleLMS.nonce
            }, function(response) {
                $btn.prop('disabled', false);
                $input.val('');
                if (response && response.success && response.data && response.data.lesson_id) {
                    const newId = response.data.lesson_id;
                    const html = `<li class="lesson-item" id="lesson-item-${newId}" data-lesson-id="${newId}"><span class="lesson-drag-handle"></span><a href="${simpleLMS.editLessonUrl.replace('LESSON_ID', newId)}" class="lesson-title-link">${$('<div>').text(lessonTitle).html()}</a></li>`;
                    // Zawsze dodawaj na koniec listy lekcji danego modułu
                    var $lessonsList = $(this).closest('.add-lesson-form').siblings('.module-lessons-list');
                    if (!$lessonsList.length) {
                        $lessonsList = $(this).closest('.module-item').find('.module-lessons-list').first();
                    }
                    if (!$lessonsList.length) {
                        $lessonsList = $("#module-item-"+moduleId+" .module-lessons-list");
                    }
                    $lessonsList.append(html);
                    // Re-init events and sortable for new elements
                    SimpleLMS.bindEvents();
                    SimpleLMS.initSortable();
                }
            }.bind(this));
        },

        handleDuplicateModule: function(e) {
            e.preventDefault();
            const moduleId = $(this).data('module-id');

            SimpleLMS.ajaxRequest({
                action: 'duplicate_module',
                module_id: moduleId
            }, function() {
                location.reload();
            });
        },

        handleDeleteModule: function(e) {
            e.preventDefault();
            if (!confirm(simpleLMS.i18n.confirm_delete_module)) {
                return;
            }

            const moduleId = $(this).data('module-id');
            SimpleLMS.ajaxRequest({
                action: 'delete_module',
                module_id: moduleId,
                security: simpleLMS.nonce
            }, function(response) {
                if (response && response.success) {
                    $("#module-item-"+moduleId).remove();
                } else {
                    alert((response && response.data && response.data.message) || simpleLMS.i18n.error_generic);
                }
            });
        },

        handleDeleteLesson: function(e) {
            e.preventDefault();
            if (!confirm(simpleLMS.i18n.confirm_delete_lesson)) {
                return;
            }

            const lessonId = $(this).data('lesson-id');
            SimpleLMS.ajaxRequest({
                action: 'delete_lesson',
                lesson_id: lessonId,
                security: simpleLMS.nonce
            }, function(response) {
                if (response && response.success) {
                    $("#lesson-item-"+lessonId).remove();
                } else {
                    alert((response && response.data && response.data.message) || simpleLMS.i18n.error_generic);
                }
            });
        },

        handleDuplicateLesson: function(e) {
            e.preventDefault();
            const lessonId = $(this).data('lesson-id');
            const nonceField = $('#module_nonce_field').length ? '#module_nonce_field' : '#course_nonce_field';

            SimpleLMS.ajaxRequest({
                action: 'duplicate_lesson',
                lesson_id: lessonId,
                security: $(nonceField).val()
            }, function() {
                location.reload();
            });
        },

        ajaxRequest: function(data, successCallback) {
            data.security = data.security || simpleLMS.nonce;

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        if (typeof successCallback === 'function') {
                            successCallback(response.data);
                        }
                    } else {
                        SimpleLMS.showError(response.data ? response.data.message : simpleLMS.i18n.error_generic);
                    }
                },
                error: function(xhr, status, error) {
                    SimpleLMS.showError(simpleLMS.i18n.error_generic + ': ' + error);
                }
            });
        },

        showError: function(message, target) {
            if (target) {
                $(target).addClass('error').removeClass('success').text(message);
            } else {
                alert(message);
            }
        },

        showSuccess: function(message, target) {
            if (target) {
                $(target).addClass('success').removeClass('error').text(message);
            }
        },

        initSortable: function() {
            // Add drag handles to modules if they don't exist
            $('.module-item').each(function() {
                if (!$(this).find('.module-drag-handle').length) {
                    $(this).find('.module-header').prepend('<span class="module-drag-handle"></span>');
                }
            });

            // Add drag handles to lessons if they don't exist
            $('.lesson-item').each(function() {
                if (!$(this).find('.lesson-drag-handle').length) {
                    $(this).prepend('<span class="lesson-drag-handle"></span>');
                }
            });

            // Sortowanie modułów
            $('.course-modules-list').sortable({
                handle: '.module-drag-handle',
                items: '> .module-item',
                placeholder: 'module-item ui-sortable-placeholder',
                forcePlaceholderSize: true,
                opacity: 0.8,
                update: function(event, ui) {
                    const moduleOrder = $(this).sortable('toArray', { attribute: 'id' }).map(id => id.replace('module-item-', ''));
                    
                    $.ajax({
                        url: simpleLMS.ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'update_modules_order',
                            module_order: moduleOrder,
                            course_id: $('#post_ID').val(),
                            security: simpleLMS.nonce
                        },
                        success: function(response) {
                            if (!response.success) {
                                alert(simpleLMS.i18n.error_generic);
                            }
                        },
                        error: function() {
                            alert(simpleLMS.i18n.error_generic);
                        }
                    });
                }
            }).disableSelection();

            // Sortowanie lekcji w modułach
            $('.module-lessons-list').sortable({
                handle: '.lesson-drag-handle',
                items: '> .lesson-item',
                placeholder: 'lesson-item ui-sortable-placeholder',
                connectWith: '.module-lessons-list',
                forcePlaceholderSize: true,
                opacity: 0.8,
                helper: 'clone',
                start: function(e, ui) {
                    ui.placeholder.height(ui.item.outerHeight());
                },
                update: function(event, ui) {
                    // First try to get module ID from module-item parent (on course page)
                    const moduleItem = $(this).closest('.module-item');
                    // If not found, try to get it from module-structure (on module edit page)
                    const moduleStructure = $(this).closest('.module-structure');
                    const moduleId = moduleItem.length ? moduleItem.data('module-id') : moduleStructure.data('module-id');
                    const lessonOrder = $(this).sortable('toArray', { attribute: 'id' }).map(id => id.replace('lesson-item-', ''));

                    if (!moduleId) return;

                    $.ajax({
                        url: simpleLMS.ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'update_lessons_order',
                            lesson_id: ui.item.attr('id').replace('lesson-item-', ''),
                            module_id: moduleId,
                            lesson_order: lessonOrder,
                            security: simpleLMS.nonce
                        },
                        success: function(response) {
                            if (!response.success) {
                                alert(simpleLMS.i18n.error_generic);
                            }
                        },
                        error: function(xhr, status, error) {
                            alert(simpleLMS.i18n.error_generic);
                        }
                    });
                }
            }).disableSelection();
        },

        initToggleLessons: function() {
            $(document).on('click', '.module-toggle', function() {
                const moduleId = $(this).data('module-id');
                const lessonsList = $(`#module-lessons-${moduleId}`);

                if (lessonsList.hasClass('visible')) {
                    lessonsList.removeClass('visible');
                    $(this).removeClass('chevron-down').addClass('chevron-right');
                } else {
                    lessonsList.addClass('visible');
                    $(this).removeClass('chevron-right').addClass('chevron-down');
                }
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        SimpleLMS.init();
    });

    // Ensure initSortable is called on the module edit page
    $(document).ready(function() {
        if ($('body').hasClass('post-type-module')) {
            SimpleLMS.initSortable();
        }
    });

    // ===== Module drip mode toggle (from custom-meta-boxes.php line 356) =====
    $(function(){
        function toggleModuleMode(){
            var val = $('input[name="_module_drip_mode"]:checked').val();
            $('#simple-lms-module-days').toggle(val === 'days');
            $('#simple-lms-module-manual').toggle(val === 'manual');
        }
        $(document).on('change','input[name="_module_drip_mode"]', toggleModuleMode);
        toggleModuleMode();
    });

    // ===== Lesson video type toggle (from custom-meta-boxes.php line 1045) =====
    $(document).ready(function($) {
        $("input[name=lesson_video_type]").change(function() {
            var type = $(this).val();
            $(".video-url-section, .video-file-section").hide();
            if (type === "youtube" || type === "vimeo" || type === "url") {
                $(".video-url-section").show();
                // Update placeholder based on type
                var placeholder = "https://example.com/video.mp4";
                if (type === "youtube") {
                    placeholder = "https://www.youtube.com/...";
                } else if (type === "vimeo") {
                    placeholder = "https://vimeo.com/...";
                }
                $("#lesson_video_url").attr("placeholder", placeholder);
            } else if (type === "file") {
                $(".video-file-section").show();
            }
        });
        
        $("#select-video-file").click(function(e) {
            e.preventDefault();
            var custom_uploader = wp.media({
                title: simpleLMS.i18n.selectVideoFile || "Wybierz plik wideo",
                library: { type: "video" },
                button: { text: simpleLMS.i18n.selectFile || "Wybierz plik" },
                multiple: false
            });
            
            custom_uploader.on("select", function() {
                var attachment = custom_uploader.state().get("selection").first().toJSON();
                $("#lesson_video_file_id").val(attachment.id);
                $(".video-file-preview p").html((simpleLMS.i18n.selectedFile || "Wybrany plik:") + ' <a href="' + attachment.url + '" target="_blank">' + attachment.filename + "</a>");
                $("#remove-video-file").show();
            });
            
            custom_uploader.open();
        });
        
        $("#remove-video-file").click(function(e) {
            e.preventDefault();
            $("#lesson_video_file_id").val("");
            $(".video-file-preview p").html(simpleLMS.i18n.noFileSelected || "Brak wybranego pliku");
            $(this).hide();
        });
    });

    // ===== Course schedule mode toggle (from custom-meta-boxes.php line 1266) =====
    $(function(){
        function toggleScheduleFields(){
            var mode = $('input[name="_access_schedule_mode"]:checked').val();
            $('#simple-lms-fixed-date-wrap').toggle(mode === 'fixed_date');
            $('#simple-lms-drip-wrap').toggle(mode === 'drip');
        }
        function toggleDripFields(){
            var strat = $('input[name="_drip_strategy"]:checked').val();
            $('#simple-lms-drip-interval').toggle(strat === 'interval');
        }
        $(document).on('change','input[name="_access_schedule_mode"]', toggleScheduleFields);
        $(document).on('change','input[name="_drip_strategy"]', toggleDripFields);
        toggleScheduleFields(); 
        toggleDripFields();
    });

    // ===== Force course metabox order (from custom-meta-boxes.php line 1640) =====
    $(document).ready(function($) {
        // Force metabox order in sidebar
        setTimeout(function() {
            var $sidebar = $('#side-sortables');
            if ($sidebar.length) {
                var $submitdiv = $('#submitdiv');
                var $courseBasicInfo = $('#course_basic_info');
                var $courseWoocommerce = $('#course_woocommerce_product');
                
                if ($submitdiv.length && $courseBasicInfo.length) {
                    // Move submitdiv to top
                    $sidebar.prepend($submitdiv);
                    
                    // Move course_basic_info after submitdiv
                    $submitdiv.after($courseBasicInfo);
                    
                    // Move woocommerce to end if exists
                    if ($courseWoocommerce.length) {
                        $sidebar.append($courseWoocommerce);
                    }
                }
            }
        }, 100);
    });

    // ===== WooCommerce course product toggle (from class-woocommerce-integration.php line 145) =====
    $(document).ready(function($) {
        // Localized in PHP: simpleLMSWoo = { currentCourseId, editCourseText, editCourseUrl }
        if (typeof simpleLMSWoo === 'undefined') {
            return; // Not on product page
        }

        function toggleCourseField() {
            var isChecked = $('#_is_course_product').is(':checked');
            var courseField = $('#_course_id_field');
            var editButtonField = $('#edit-course-button-field');
            var editButtonContainer = $('#edit-course-button-container');
            
            if (isChecked) {
                courseField.show();
                // Show button field if course is selected and saved
                if ($('#_course_id').val() && $('#_course_id').val() === simpleLMSWoo.currentCourseId) {
                    editButtonField.show();
                    updateEditButton();
                }
            } else {
                courseField.hide();
                editButtonField.hide();
                editButtonContainer.empty();
                $('#_course_id').val('');
            }
        }
        
        function updateEditButton() {
            var selectedCourseId = $('#_course_id').val();
            var editButtonContainer = $('#edit-course-button-container');
            var editButtonField = $('#edit-course-button-field');
            var isChecked = $('#_is_course_product').is(':checked');
            
            // Clear existing button
            editButtonContainer.empty();
            editButtonField.hide();
            
            if (selectedCourseId && isChecked) {
                // Check if this is after save (product has been saved with course)
                var currentCourseFromDB = simpleLMSWoo.currentCourseId;
                if (currentCourseFromDB && selectedCourseId === currentCourseFromDB) {
                    var editUrl = simpleLMSWoo.editCourseUrl + selectedCourseId;
                    var buttonHtml = '<a href="' + editUrl + '" target="_blank" class="button" style="border-color: #0073aa; color: #0073aa;">' + 
                                   simpleLMSWoo.editCourseText + '</a>';
                    editButtonContainer.html(buttonHtml);
                    editButtonField.show();
                }
            }
        }
        
        // Check if virtual product is selected
        function checkVirtualProduct() {
            var isVirtual = $('#_virtual').is(':checked');
            var courseGroup = $('#_is_course_product').closest('.options_group');
            
            if (isVirtual) {
                courseGroup.show();
                // Initialize course field visibility
                toggleCourseField();
            } else {
                courseGroup.hide();
                // Reset fields if changing from virtual to non-virtual
                $('#_is_course_product').prop('checked', false);
                toggleCourseField();
            }
        }
        
        // Event bindings
        $('#_is_course_product').on('change', toggleCourseField);
        $('#_course_id').on('change', function() {
            // Note: edit button appears only after save, not on selection change
        });
        $('#_virtual').on('change', checkVirtualProduct);
        
        // Initial state
        checkVirtualProduct();
        toggleCourseField();
    });

    // ===== Featured image handler (consolidated from custom-meta-boxes.php) =====
    window.simpleLMSFeaturedImage = {
        /**
         * Initialize featured image handler for a specific context
         * @param {string} prefix - Context prefix ('course' or 'lesson')
         * @param {object} config - Configuration with mediaTitle, buttonText, confirmText
         */
        init: function(prefix, config) {
            const self = this;
            const containerId = `#${prefix}-featured-image-container`;
            let mediaUploader;
            
            // Set/Change image button
            $(document).on('click', `#set-${prefix}-featured-image, #change-${prefix}-featured-image`, function(e) {
                e.preventDefault();
                
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                mediaUploader = wp.media({
                    title: config.mediaTitle,
                    button: { text: config.buttonText },
                    multiple: false
                });
                
                mediaUploader.on('select', function() {
                    const attachment = mediaUploader.state().get('selection').first().toJSON();
                    self.renderImage(prefix, attachment, config);
                });
                
                mediaUploader.open();
            });
            
            // Remove image button
            $(document).on('click', `#remove-${prefix}-featured-image`, function(e) {
                e.preventDefault();
                
                if (confirm(config.confirmText)) {
                    self.renderEmpty(prefix, config);
                }
            });
        },
        
        /**
         * Render image with controls
         */
        renderImage: function(prefix, attachment, config) {
            const containerId = `#${prefix}-featured-image-container`;
            const imageSize = prefix === 'lesson' ? '150px' : '200px';
            const buttonSpacing = prefix === 'lesson' ? '10px' : '15px';
            
            const imageHtml = `
                <div style="text-align: center;">
                    <img src="${attachment.sizes.medium.url}" style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.15);">
                    <div style="margin-top: ${buttonSpacing};">
                        <button type="button" id="change-${prefix}-featured-image" class="button">${config.changeText}</button> 
                        <button type="button" id="remove-${prefix}-featured-image" class="button" style="color: #a00;">${config.removeText}</button>
                    </div>
                </div>
            `;
            
            $(containerId).html(imageHtml);
            $('#_thumbnail_id').val(attachment.id);
        },
        
        /**
         * Render empty state
         */
        renderEmpty: function(prefix, config) {
            const containerId = `#${prefix}-featured-image-container`;
            const minHeight = prefix === 'lesson' ? '150px' : '200px';
            const padding = prefix === 'lesson' ? '30px 15px' : '40px 20px';
            const fontSize = prefix === 'lesson' ? '14px' : '16px';
            const margin = prefix === 'lesson' ? '0 0 10px 0' : '0 0 15px 0';
            
            const emptyHtml = `
                <div style="text-align: center; padding: ${padding}; border: 2px dashed #ddd; border-radius: 8px; background: #fafafa; min-height: ${minHeight}; box-sizing: border-box; display: flex; flex-direction: column; justify-content: center;">
                    <p style="color: #666; margin: ${margin}; font-size: ${fontSize};">${config.emptyText}</p>
                    <button type="button" id="set-${prefix}-featured-image" class="button button-primary">${config.addText}</button>
                </div>
            `;
            
            $(containerId).html(emptyHtml);
            $('#_thumbnail_id').val('');
        }
    };

})(jQuery);