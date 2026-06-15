import fs from 'node:fs'
import path from 'node:path'
import { describe, expect, it } from 'vitest'

const pluginRoot = path.resolve(__dirname, '../../../plugin/shortdrama/web')

function source(relativePath: string): string {
  return fs.readFileSync(path.join(pluginRoot, relativePath), 'utf8')
}

describe('短剧后台前端契约', () => {
  it('提供全部 MVP API 模块', () => {
    for (const name of ['drama', 'episode', 'user', 'dashboard', 'import']) {
      expect(fs.existsSync(path.join(pluginRoot, `api/${name}.ts`))).toBe(true)
    }
  })

  it('短剧、分集和 App 用户页面不提供删除入口', () => {
    for (const page of ['drama/index.vue', 'episode/index.vue', 'user/index.vue']) {
      const content = source(`views/${page}`)
      expect(content).not.toContain('delete')
      expect(content).not.toContain('删除')
    }
  })

  it('核心页面使用中文标题', () => {
    expect(source('views/dashboard/index.vue')).toContain('数据看板')
    expect(source('views/drama/index.vue')).toContain('短剧管理')
    expect(source('views/episode/index.vue')).toContain('分集管理')
    expect(source('views/user/index.vue')).toContain('App 用户')
    expect(source('views/import/index.vue')).toContain('数据导入')
  })

  it('看板统计数字水平垂直居中', () => {
    const dashboard = source('views/dashboard/index.vue')
    expect(dashboard).toContain('align-items: center')
    expect(dashboard).toContain('justify-content: center')
    expect(dashboard).toContain('font-variant-numeric: tabular-nums')
  })
})
