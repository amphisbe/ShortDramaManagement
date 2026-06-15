import fs from 'node:fs'
import path from 'node:path'
import { describe, expect, it, vi } from 'vitest'
import { createBatchUploadQueue } from '$/shortdrama/admin/composables/useBatchUpload'

function file(name: string): File {
  return new File([name], name, { type: 'video/mp4' })
}

function wait(milliseconds = 5): Promise<void> {
  return new Promise(resolve => setTimeout(resolve, milliseconds))
}

describe('短剧批量上传队列', () => {
  it('哈希并发不超过 2', async () => {
    let active = 0
    let maximum = 0
    const queue = createBatchUploadQueue({
      hash: async (current) => {
        active += 1
        maximum = Math.max(maximum, active)
        await wait()
        active -= 1
        return current.name.padEnd(64, 'a').slice(0, 64)
      },
      check: async files => files.map(item => ({ name: item.name, accepted: true, code: null })),
      presign: vi.fn(),
      upload: vi.fn(),
      complete: vi.fn(),
    })

    queue.addFiles([file('A_ep01.mp4'), file('A_ep02.mp4'), file('A_ep03.mp4'), file('A_ep04.mp4')])
    await queue.prepare()

    expect(maximum).toBeLessThanOrEqual(2)
    expect(queue.items.every(item => item.status === 'ready')).toBe(true)
  })

  it('上传并发不超过 3 且单文件失败不取消其他任务', async () => {
    let active = 0
    let maximum = 0
    const completed: number[] = []
    const queue = createBatchUploadQueue({
      hash: async current => current.name.padEnd(64, 'b').slice(0, 64),
      check: async files => files.map(item => ({ name: item.name, accepted: true, code: null })),
      presign: async item => ({
        asset_id: Number(item.name.match(/\d+/)?.[0] ?? 0),
        upload_url: `https://upload.test/${item.name}`,
        object_key: `videos/${item.name}`,
        expires_in: 900,
      }),
      upload: async (_url, current) => {
        active += 1
        maximum = Math.max(maximum, active)
        await wait()
        active -= 1
        if (current.name === 'A_ep02.mp4') {
          throw new Error('网络中断')
        }
      },
      complete: async (assetId) => {
        completed.push(assetId)
      },
    })

    queue.addFiles([file('A_ep01.mp4'), file('A_ep02.mp4'), file('A_ep03.mp4'), file('A_ep04.mp4')])
    await queue.prepare()
    await queue.uploadReady()

    expect(maximum).toBeLessThanOrEqual(3)
    expect(queue.items.filter(item => item.status === 'success')).toHaveLength(3)
    expect(queue.items.find(item => item.name === 'A_ep02.mp4')?.status).toBe('failed')
    expect(completed).toHaveLength(3)
  })

  it('同批重复哈希在调用后端前标记失败', async () => {
    const check = vi.fn(async files => files.map(item => ({ name: item.name, accepted: true, code: null })))
    const queue = createBatchUploadQueue({
      hash: async current => current.name === 'A_ep03.mp4' ? 'unique'.padEnd(64, 'c') : 'same'.padEnd(64, 'd'),
      check,
      presign: vi.fn(),
      upload: vi.fn(),
      complete: vi.fn(),
    })

    queue.addFiles([file('A_ep01.mp4'), file('A_ep02.mp4'), file('A_ep03.mp4')])
    await queue.prepare()

    expect(check).toHaveBeenCalledTimes(1)
    expect(check.mock.calls[0][0]).toHaveLength(2)
    expect(queue.items.find(item => item.name === 'A_ep02.mp4')).toMatchObject({
      status: 'failed',
      errorCode: 'DUPLICATE_IN_BATCH',
    })
  })

  it('worker 使用 4 MB 分块增量计算 SHA-256', () => {
    const worker = fs.readFileSync(
      path.resolve(__dirname, '../../../plugin/shortdrama/web/workers/sha256.worker.ts'),
      'utf8',
    )

    expect(worker).toContain('4 * 1024 * 1024')
    expect(worker).toContain('createSHA256')
    expect(worker).toContain('type: \'progress\'')
    expect(worker).toContain('type: \'done\'')
  })
})
