import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './resources/js'),
    },
  },
  server: {
    port: 5173,
    host: true,
    proxy: {
      '/api': {
        target: process.env.VITE_PROXY_TARGET || 'http://nginx',
        changeOrigin: true,
      },
      '/storage': {
        target: process.env.VITE_PROXY_TARGET || 'http://nginx',
        changeOrigin: true,
      },
    },
    hmr: {
      host: 'localhost',
      port: 5173,
    },
  },
  build: {
    outDir: 'public/dist',
    emptyOutDir: true,
  },
});
