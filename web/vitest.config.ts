import path from 'node:path'
import { defineConfig } from 'vitest/config'

export default defineConfig({
  resolve: {
    alias: [
      { find: 'vue', replacement: path.resolve(__dirname, 'node_modules/vue/dist/vue.runtime.esm-bundler.js') },
      { find: '$/shortdrama/admin', replacement: path.resolve(__dirname, '../plugin/shortdrama/web') },
      { find: '@', replacement: path.resolve(__dirname, 'src') },
      { find: '$', replacement: path.resolve(__dirname, 'src/plugins') },
      { find: '~', replacement: path.resolve(__dirname, 'src/modules') },
    ],
  },
  test: {
    environment: 'jsdom',
    include: ['tests/**/*.spec.ts'],
  },
})
