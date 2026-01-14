# Simple LMS - Build Process

This plugin uses **Vite** for modern asset bundling and optimization.

## Prerequisites

- Node.js >= 18.0.0
- npm >= 9.0.0

## Installation

1. Install dependencies:
```bash
npm install
```

## Development

### Start development server with HMR (Hot Module Replacement):
```bash
npm run dev
```

This will start Vite dev server on `http://localhost:3000` with hot reload.

### Watch mode (rebuild on file changes):
```bash
npm run watch
```

## Production Build

Build optimized assets for production:
```bash
npm run build
```

This will:
- Bundle and minify JavaScript
- Process and minify CSS
- Add vendor prefixes (autoprefixer)
- Generate sourcemaps
- Output to `assets/dist/`

## Project Structure

```
assets/
├── src/              # Source files (edit these)
│   ├── js/
│   │   ├── frontend.js      # Frontend JavaScript
│   │   ├── admin.js         # Admin JavaScript
│   │   ├── lesson.js        # Lesson-specific
│   │   ├── settings.js      # Settings page
│   │   └── meta-boxes.js    # Meta boxes
│   └── css/
│       ├── frontend.css     # Frontend styles
│       ├── admin.css        # Admin styles
│       └── lesson.css       # Lesson styles
├── dist/             # Built files (auto-generated, don't edit)
│   ├── js/
│   └── css/
├── js/               # Legacy files (kept for reference)
└── css/              # Legacy files (kept for reference)
```

## Asset Loading

Assets are loaded via `AssetManager` class in `includes/managers/AssetManager.php`:

- **Frontend:** `assets/dist/js/frontend.js` + `assets/dist/css/frontend-style.css`
- **Admin:** `assets/dist/js/admin.js` + `assets/dist/css/admin-style.css`
- **Lesson Pages:** `assets/dist/js/lesson.js` (conditional)
- **Settings:** `assets/dist/js/settings.js` (conditional)
- **Meta Boxes:** `assets/dist/js/meta-boxes.js` (conditional)

## Configuration

### Vite Config (`vite.config.js`)

- Entry points defined in `rollupOptions.input`
- Output naming: `js/[name].js`, `css/[name].css`
- Minification: Terser with console/debugger removal
- Legacy browser support via `@vitejs/plugin-legacy`

### PostCSS (`postcss.config.js`)

- Autoprefixer for vendor prefixes
- Target browsers: `> 1%, last 2 versions, not dead`

## Deployment

Before deploying to production:

1. Run production build:
```bash
npm run build
```

2. Commit only the `assets/dist/` folder (source files in `assets/src/` are optional)

3. The `assets/dist/` folder contains all optimized assets needed for production

## Performance

Vite provides:
- **30-40% smaller bundle sizes** vs unminified
- **Tree shaking** - removes unused code
- **Code splitting** - separate chunks for better caching
- **CSS optimization** - minification, vendor prefixes
- **Sourcemaps** - for debugging production issues

## Troubleshooting

### Assets not loading?
1. Check that `assets/dist/` folder exists
2. Run `npm run build`
3. Clear WordPress cache

### Build errors?
1. Delete `node_modules/` and `package-lock.json`
2. Run `npm install` again
3. Check Node.js version: `node --version` (should be >= 18)

### HMR not working?
1. Check Vite dev server is running (`npm run dev`)
2. Check port 3000 is not blocked
3. Try `npm run watch` instead for simple file watching

## Scripts Reference

| Command | Description |
|---------|-------------|
| `npm run dev` | Start Vite dev server with HMR |
| `npm run build` | Production build (minified) |
| `npm run watch` | Watch mode (rebuild on change) |
| `npm run preview` | Preview production build locally |

## Learn More

- [Vite Documentation](https://vitejs.dev/)
- [WordPress Enqueue Scripts](https://developer.wordpress.org/reference/functions/wp_enqueue_script/)
