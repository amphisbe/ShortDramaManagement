import type { App } from 'vue'
import type { Plugin } from '#/global'

const pluginConfig: Plugin.PluginConfig = {
  install(_app: App) {},
  config: {
    enable: true,
    info: {
      name: 'shortdrama/admin',
      version: '1.0.0',
      author: 'ShortDrama Team',
      description: '短剧运营管理后台',
      order: 100,
    },
  },
}

export default pluginConfig
