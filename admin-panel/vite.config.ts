import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [vue()],
  build: {
    // Generate manifest for better caching
    manifest: true,
    // Output as ES module
    target: 'es2015',
    // Create sourcemaps for debugging
    sourcemap: true,
    // Set base path for production
    assetsDir: 'assets',
  },
  server: {
    // Configure CORS for development
    cors: true
  }
})
