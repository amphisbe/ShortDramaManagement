import fs from 'node:fs'
import path from 'node:path'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import UploadSummary from '$/shortdrama/admin/views/upload/upload-summary.vue'

const pluginRoot = path.resolve(__dirname, '../../../plugin/shortdrama/web')

function source(relativePath: string): string {
  return fs.readFileSync(path.join(pluginRoot, relativePath), 'utf8')
}

describe('批量上传页面', () => {
  it('统计卡显示已上传/总集数、本次文件和并发数', () => {
    const wrapper = mount(UploadSummary, {
      props: { uploaded: 72, total: 80, fileCount: 8, concurrency: 3 },
      global: { stubs: { MaSvgIcon: true } },
    })

    expect(wrapper.text()).toContain('72/80')
    expect(wrapper.text()).toContain('本次文件')
    expect(wrapper.text()).toContain('8')
    expect(wrapper.text()).toContain('上传并发')
    expect(wrapper.text()).toContain('3')
  })

  it('统计数字水平和垂直居中', () => {
    const summary = source('views/upload/upload-summary.vue')

    expect(summary).toContain('align-items: center')
    expect(summary).toContain('justify-content: center')
    expect(summary).toContain('font-variant-numeric: tabular-nums')
  })

  it('使用 Element Plus 手动多文件上传并执行完整流水线', () => {
    const view = source('views/upload/index.vue')

    expect(view).toContain('multiple')
    expect(view).toContain(':auto-upload="false"')
    expect(view).toContain('accept=".mp4,video/mp4"')
    expect(view).toContain('queue.prepare()')
    expect(view).toContain('queue.uploadReady()')
    expect(view).toContain('已上传/总集数')
    expect(view).toContain('selectedDramaId.value = Number(dramas.value[0].id)')
  })

  it('文件表格支持移除等待项和重试失败项', () => {
    const table = source('views/upload/upload-table.vue')

    expect(table).toContain('emit(\'remove\'')
    expect(table).toContain('emit(\'retry\'')
    expect(table).toContain('失败原因')
  })
})
