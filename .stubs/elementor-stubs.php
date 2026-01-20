<?php
// Lightweight Elementor stubs for static analysis only.
// Do NOT load in runtime. Used by phpstan/intelephense to silence undefined class errors.

namespace Elementor {
    class Widget_Base {
        public function start_controls_section(string $section_id, array $args = []): void {}
        public function end_controls_section(): void {}
        public function start_controls_tabs(string $tabs_id): void {}
        public function end_controls_tabs(): void {}
        public function start_controls_tab(string $tab_id, array $args = []): void {}
        public function end_controls_tab(): void {}
        public function add_control(string $id, array $args): void {}
        public function add_responsive_control(string $id, array $args): void {}
        public function add_group_control(string $group_name, array $args = []): void {}
        public function get_settings_for_display(string $setting_key = null) { return []; }
        public function get_id(): string { return ''; }
    }

    class Controls_Manager {
        public const TAB_CONTENT = 'content';
        public const TAB_STYLE = 'style';
        public const NUMBER = 'number';
        public const TEXT = 'text';
        public const TEXTAREA = 'textarea';
        public const WYSIWYG = 'wysiwyg';
        public const CODE = 'code';
        public const SWITCHER = 'switcher';
        public const SELECT = 'select';
        public const MEDIA = 'media';
        public const URL = 'url';
        public const CHOOSE = 'choose';
        public const SLIDER = 'slider';
        public const DIMENSIONS = 'dimensions';
        public const COLOR = 'color';
        public const ICONS = 'icons';
        public const ICON = 'icon';
        public const HEADING = 'heading';
        public const DIVIDER = 'divider';
    }

    class Group_Control_Image_Size {
        public static function get_type(): string { return 'image-size'; }
    }

    class Group_Control_Typography {
        public static function get_type(): string { return 'typography'; }
    }

    class Group_Control_Text_Shadow {
        public static function get_type(): string { return 'text-shadow'; }
    }

    class Group_Control_Border {
        public static function get_type(): string { return 'border'; }
    }

    class Group_Control_Box_Shadow {
        public static function get_type(): string { return 'box-shadow'; }
    }

    class Icons_Manager {
        public static function render_icon(array $settings, array $attributes = [], string $tag = 'i'): string { return ''; }
    }

    class Documents_Manager_STUB {
        public function get_current() { return null; }
    }

    class Editor_STUB {
        public function is_edit_mode(): bool { return false; }
    }

    class Plugin {
        public static $instance;
        public $editor;
        public $documents;

        public function __construct()
        {
            $this->editor = new Editor_STUB();
            $this->documents = new Documents_Manager_STUB();
            self::$instance = $this;
        }

        public static function instance(): self {
            if (!isset(self::$instance)) {
                self::$instance = new self();
            }
            return self::$instance;
        }
    }
}

namespace Elementor\Core\DynamicTags {
    class Manager {
        public function register_group(...$args): void {}
        public function register(...$args): void {}
    }
}

namespace Bricks {
    class Element {
        public $controls = [];
        public $settings = [];
    }

    class Elements {
        public static function register_element(string $file): void {}
    }
}
