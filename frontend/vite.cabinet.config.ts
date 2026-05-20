import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueJsx from '@vitejs/plugin-vue-jsx'

export default defineConfig({
  base: '/cabinet/',
  plugins: [vue(), vueJsx()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
      '@cabinet': fileURLToPath(new URL('./src/cabinet', import.meta.url)),
    },
  },
  build: {
    outDir: '../public/cabinet',
    emptyOutDir: true,
    rollupOptions: {
      input: {
        index: fileURLToPath(new URL('./cabinet.html', import.meta.url)),
      },
    },
  },
  server: {
    port: 5174,
    proxy: {
      '/api': {
        target: 'http://localhost:8080',
        changeOrigin: true,
      },
      '/sanctum': {
        target: 'http://localhost:8080',
        changeOrigin: true,
      },
    },
  },
})
