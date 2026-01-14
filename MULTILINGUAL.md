# Simple LMS - Multilingual Compatibility Guide

## Supported Translation Plugins

Simple LMS is fully compatible with the following multilingual WordPress plugins:

### 1. **WPML** (WordPress Multilingual Plugin)
- **Type**: Premium, separate posts per language
- **Configuration**: `wpml-config.xml` in plugin root
- **Features**:
  - Automatic CPT translation (course, module, lesson)
  - Meta field copying across translations
  - Post ID mapping via `wpml_object_id` filter

### 2. **Polylang**
- **Type**: Free/Pro, separate posts per language
- **Configuration**: Auto-registered via `pll_get_post_types` filter
- **Features**:
  - CPT and taxonomy translation
  - Post ID mapping via `pll_get_post()` function
  - Compatible with Polylang Pro's advanced features

### 3. **TranslatePress**
- **Type**: Freemium, visual front-end translator
- **Configuration**: Auto-registered via `trp_register_custom_post_types` filter
- **Features**:
  - In-place content translation (no duplicate posts)
  - WYSIWYG translation interface
  - Automatic detection of translatable content
  - Compatible with page builders (Elementor, Bricks)

### 4. **Weglot**
- **Type**: SaaS (paid), cloud-based translation
- **Configuration**: Auto-registered via `weglot_get_post_types` filter
- **Features**:
  - Dynamic translation (no duplicate posts)
  - Professional translation APIs (Google, DeepL)
  - Automatic meta field exclusion for technical data
  - REST API endpoint translation

### 5. **qTranslate-X / qTranslate-XT**
- **Type**: Free, single post with language tags
- **Configuration**: Auto-registered via `qtranslate_custom_post_types` filter
- **Features**:
  - Lightweight approach (one post stores all translations)
  - Language tags format: `[:en]English[:de]Deutsch`
  - No post duplication
  - qTranslate-XT is the actively maintained community fork
- **Note**: qTranslate-X is deprecated, use qTranslate-XT

### 6. **MultilingualPress**
- **Type**: Free/Pro, WordPress Multisite-based
- **Configuration**: Auto-registered via `multilingualpress.custom_post_types` filter
- **Features**:
  - Each language = separate WordPress site in network
  - Complete database isolation per language
  - Content relationship mapping across sites
  - Ideal for enterprise/large-scale multilingual sites
- **Requirement**: WordPress Multisite must be enabled

### 7. **GTranslate**
- **Type**: Freemium, Google Translate widget
- **Configuration**: Minimal (automatic page translation)
- **Features**:
  - Automatic translation via Google Translate
  - 100+ languages supported
  - JavaScript/widget-based translation
  - No manual translation needed
- **Note**: Machine translation quality, best for quick/informal multilingual support

---

## How It Works

### Post ID Mapping

Simple LMS automatically maps post IDs to the current language in:

#### Shortcodes:
- All lesson shortcodes (title, content, excerpt, permalink, video URL)
- All module shortcodes (title, content, excerpt)
- All course shortcodes (title, content, excerpt)
- Navigation shortcodes (previous/next lesson)
- WooCommerce purchase URLs

#### Bricks Elements:
- Lesson Content element
- Lesson Video element
- Lesson Navigation element

#### Internal Functions:
- `getCurrentCourseId()` - maps lesson → module → course chain
- `getPreviousLesson()` / `getNextLesson()` - respects language relationships
- Access control checks - per-language course access

### Translation Approaches

#### WPML & Polylang (Separate Posts):
```php
// Original post ID: 123 (Polish)
// Translated post ID: 456 (German)
$mapped = Multilingual_Compat::map_post_id(123, 'lesson');
// Returns: 456 when viewing German site
```

#### TranslatePress & Weglot (In-Place Translation):
```php
// Post ID: 123 (same across all languages)
$mapped = Multilingual_Compat::map_post_id(123, 'lesson');
// Returns: 123 (content translated dynamically)
```

---

## Setup Instructions

### WPML Setup

1. Install WPML
2. Go to WPML → Settings
3. Post Types Translation: Ensure `course`, `module`, `lesson` are set to "Translatable"
4. `wpml-config.xml` is automatically detected by WPML
5. Create course in default language
6. Translate via WPML → Translation Management

### Polylang Setup

1. Install Polylang
2. Go to Languages → Settings
3. Simple LMS CPTs are auto-registered as translatable
4. Create course in default language
5. Click "+ Add Translation" in post edit screen

### TranslatePress Setup

1. Install TranslatePress
2. Go to Settings → TranslatePress
3. Add destination languages
4. Simple LMS CPTs are auto-registered
5. Visit front-end, switch language, click "Translate Page"
6. Use visual editor to translate content

### Weglot Setup

1. Install Weglot
2. Enter API key in Settings → Weglot
3. Add destination languages
4. Simple LMS CPTs are auto-registered
5. Technical meta fields are auto-excluded from translation
6. Translation happens automatically

### qTranslate-X / qTranslate-XT Setup

1. Install qTranslate-XT (recommended) or qTranslate-X
2. Go to Settings → Languages
3. Enable desired languages
4. Simple LMS CPTs are auto-registered
5. Edit course/lesson: Use language switcher tabs to enter translations
6. All translations are stored in single post using `[:en]text[:de]text` format

### MultilingualPress Setup

1. Enable WordPress Multisite (if not already)
2. Install MultilingualPress
3. Create separate site for each language in Network Admin
4. Go to Sites → MultilingualPress
5. Link sites as language versions of each other
6. Simple LMS CPTs are auto-registered
7. Create course in main site, then "Create Translation" to other sites
8. Meta fields are automatically copied across sites

### GTranslate Setup

1. Install GTranslate
## Compatibility Files

### Core Multilingual Support:
- `includes/compat/polylang-wpml-compat.php` - WPML & Polylang integration (+ universal map_post_id)
- `includes/compat/translatepress-compat.php` - TranslatePress integration
- `includes/compat/weglot-compat.php` - Weglot integration
- `includes/compat/qtranslate-compat.php` - qTranslate-X/XT integration
- `includes/compat/multilingualpress-compat.php` - MultilingualPress (Multisite) integration
- `includes/compat/gtranslate-compat.php` - GTranslate widget integration
- `wpml-config.xml` - WPML configuration (plugin root)
---

## Custom Fields Handling

### Fields Copied Across Translations (WPML/Polylang):
- `parent_course` - Module → Course relationship
- `parent_module` - Lesson → Module relationship
- `lesson_video_type` - Video type (youtube/vimeo/file/url)
- `lesson_video_url` - Video URL
- `lesson_video_file_id` - Attachment ID
- `lesson_duration` - Lesson duration
- `lesson_attachments` - File attachments
- `_access_duration_value` - Access duration value
- `_access_duration_unit` - Access duration unit
- `_selected_product_id` - WooCommerce product ID
- `_module_unlock_delay_value` - Module unlock delay value
- `_module_unlock_delay_unit` - Module unlock delay unit

### Fields Excluded from Translation (Weglot):
All technical fields above are excluded to prevent accidental translation of IDs and settings.

---

## Compatibility Files

### Core Multilingual Support:
- `includes/compat/polylang-wpml-compat.php` - WPML & Polylang integration
- `includes/compat/translatepress-compat.php` - TranslatePress integration
- `includes/compat/weglot-compat.php` - Weglot integration
- `wpml-config.xml` - WPML configuration (plugin root)

### Helper Class:
```php
// Usage in custom code:
use SimpleLMS\Compat\Multilingual_Compat;

$translated_id = Multilingual_Compat::map_post_id($lesson_id, 'lesson');
```

---

## Translation Files

Simple LMS includes full translations in:
- **Polish (pl_PL)**: `languages/simple-lms-pl_PL.po/mo`
- **English (en_US)**: `languages/simple-lms-en_US.po/mo`
- **German (de_DE)**: `languages/simple-lms-de_DE.po/mo`

### Plural Forms:
All languages include proper plural form support for access duration strings (days, weeks, months, years).

### Compiling Translations:
```bash
cd wp-content/plugins/simple-lms/languages
php compile-translations.php
```

Or use **Loco Translate** or **Poedit** for GUI-based compilation.

---

## Testing Multilingual Setup

### Checklist:
1. ✅ Create course in default language with modules and lessons
2. ✅ Translate course to target language
3. ✅ Verify navigation (prev/next) links to translated lessons
4. ✅ Verify shortcodes display correct language content
5. ✅ Verify Bricks elements render translated content
6. ✅ Verify WooCommerce purchase buttons link to correct product
7. ✅ Switch admin language and verify UI translations
8. ✅ Test access control in both languages

---

## Troubleshooting

### Issue: Wrong language content displayed
**Solution**: Check if translation plugin is properly configured and CPTs are set to translatable.

### Issue: Navigation links to wrong language
**Solution**: Ensure parent-child relationships are maintained in translations (module → course, lesson → module).

### Issue: WooCommerce product not found
**Solution**: Assign translated WooCommerce product in course meta for each language.

### Issue: Admin UI not translated
**Solution**: Go to Settings → Simple LMS → Language and select desired locale.

---
### Check Active Translation Plugin:
```php
// WPML
if (function_exists('apply_filters') && apply_filters('wpml_object_id', 1, 'post', true)) {
    // WPML is active
}

// Polylang
if (function_exists('pll_get_post')) {
    // Polylang is active
}

// TranslatePress
if (class_exists('TRP_Translate_Press')) {
    // TranslatePress is active
}

// Weglot
if (function_exists('weglot_init')) {
    // Weglot is active
}

// qTranslate-X/XT
if (function_exists('qtranxf_getLanguage') || function_exists('qtrans_getLanguage')) {
    // qTranslate is active
}

// MultilingualPress
if (is_multisite() && function_exists('mlp_get_linked_elements')) {
    // MultilingualPress is active
}

// GTranslate
if (function_exists('gtranslate_init') || class_exists('GTranslate')) {
    // GTranslate is active
}
```(function_exists('weglot_init')) {
    // Weglot is active
}
```

### Get Current Language:
```php
// Works with all supported plugins
$locale = determine_locale(); // WordPress core function

// Plugin-specific:
// WPML: apply_filters('wpml_current_language', null)
// Polylang: pll_current_language()
// TranslatePress: trp_get_current_language()
// Weglot: weglot_get_current_language()
```

---

## Support

For multilingual-specific issues, please ensure:
1. Your translation plugin is up-to-date
2. Simple LMS is version 1.3.4 or higher
3. PHP version is 8.0 or higher
4. WordPress version is 6.0 or higher

**Questions?** Check the compatibility files in `includes/compat/` for implementation details.
