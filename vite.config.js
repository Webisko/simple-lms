import { defineConfig } from 'vite';
import { resolve } from 'path';
import autoprefixer from 'autoprefixer';

export default defineConfig({
  plugins: [],
  
  build: {
    // Output directory
    outDir: 'assets/dist',
    
    // Empty output dir before build
    emptyOutDir: true,
    
    // Disable sourcemaps for production release
    sourcemap: false,
    
    // Target ES5 for WordPress compatibility
    target: 'es2015',
    
    // Rollup options
    rollupOptions: {
      input: {
        // Frontend assets
        'frontend': resolve(__dirname, 'assets/src/js/frontend.js'),
        'frontend-style': resolve(__dirname, 'assets/src/css/frontend.css'),
        
        // Admin assets
        'admin': resolve(__dirname, 'assets/src/js/admin.js'),
        'admin-style': resolve(__dirname, 'assets/src/css/admin.css'),
        
        // Lesson-specific
        'lesson': resolve(__dirname, 'assets/src/js/lesson.js'),
        
        // Settings page
        'settings': resolve(__dirname, 'assets/src/js/settings.js'),
        
        // Meta boxes
        'meta-boxes': resolve(__dirname, 'assets/src/js/meta-boxes.js'),
      },
      
      output: {
        // Output file naming
        entryFileNames: 'js/[name].js',
        chunkFileNames: 'js/[name]-[hash].js',
        assetFileNames: (assetInfo) => {
          const info = assetInfo.name.split('.');
          const ext = info[info.length - 1];
          if (/css/i.test(ext)) {
            return 'css/[name][extname]';
          }
          return 'assets/[name]-[hash][extname]';
        },
      },
    },
    
    // Minification options
    minify: 'terser',
    terserOptions: {
      compress: {
        drop_console: false,
        drop_debugger: false,
      },
      format: {
        comments: false,
      },
    },
    
    // Chunk size warnings
    chunkSizeWarningLimit: 500,
  },
  
  // CSS options
  css: {
    devSourcemap: true,
    postcss: {
      plugins: [
        autoprefixer,
      ],
    },
  },
  
  // Server options (for development)
  server: {
    port: 3000,
    strictPort: false,
    cors: true,
    hmr: {
      host: 'localhost',
    },
  },
  
  // Define global constants
  define: {
    __VERSION__: JSON.stringify(process.env.npm_package_version),
  },
});
