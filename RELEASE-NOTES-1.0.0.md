# Simple LMS 1.0.0 – Release Notes

## Highlights
- Course structure: Courses → Modules → Lessons
- Access control (optional WooCommerce purchasing flow)
- Builder support: Elementor widgets and Bricks elements
- Progress tracking (lesson completion)
- REST API under `simple-lms/v1`
- Multilingual compatibility and translatable UI
- Privacy tools integration and uninstall handling

## Compatibility
- Requires WordPress: 6.0+
- Tested up to: 6.8
- Requires PHP: 8.0+

## Performance & Security
- Frontend assets load only on LMS pages (filterable)
- Nonce verification and capability checks for AJAX
- Production build without sourcemaps

## Tests
- PHPStan: OK
- PHPCS (WPCS): OK
- PHPUnit (unit): OK
- wp-env (WP 6.8) integration: OK

## Packaging
- Release archive: simple-lms-1.0.0.zip
- `.distignore` excludes dev tooling, docs, and tests
