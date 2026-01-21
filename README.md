# Simple LMS v1.0.0

Simple LMS is a WordPress plugin for selling and delivering online courses.

It provides a course structure (courses → modules → lessons), access control (typically via WooCommerce purchases), and builder-friendly integrations for Elementor and Bricks.

## Requirements

- PHP 8.0+
- WordPress 6.0+
- WooCommerce 7.0+ (optional, required if you want to sell courses)
- Elementor 3.5+ (optional)
- Bricks Builder 1.5+ (optional)

## Installation

1. Upload the `simple-lms` plugin folder to `wp-content/plugins/`.
2. Activate **Simple LMS** in WordPress.
3. (Optional) Install and activate **WooCommerce**.

## Getting Started

1. Create a **Course**.
2. Create **Modules** and assign them to the course.
3. Create **Lessons** and assign them to a module.
4. Build the frontend using Elementor/Bricks widgets.

## REST API

Endpoints are exposed under the `simple-lms/v1` namespace.

See [docs/REST-API.md](docs/REST-API.md) and [API-REFERENCE.md](API-REFERENCE.md).

## Privacy

See [PRIVACY.md](PRIVACY.md) for data handling details.

## For Developers

- [BUILD.md](BUILD.md) – build assets
- [HOOKS.md](HOOKS.md) – hooks & filters
- [SECURITY.md](SECURITY.md) – security notes
- [TESTING.md](TESTING.md) – test guide
- [CONTRIBUTING.md](CONTRIBUTING.md) – contribution workflow
