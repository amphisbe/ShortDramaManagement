import type {
  BatchUploadItem,
  MediaCheckResult,
  MediaFileDescriptor,
  MediaPresignResult,
} from '../types/media'
import { reactive } from 'vue'
import { checkMedia, completeMedia, presignMedia } from '../api/media'

export interface BatchUploadDependencies {
  hash: (file: File, onProgress?: (percentage: number) => void) => Promise<string>
  check: (files: MediaFileDescriptor[]) => Promise<MediaCheckResult[]>
  presign: (item: BatchUploadItem) => Promise<MediaPresignResult>
  upload: (url: string, file: File, onProgress?: (percentage: number) => void) => Promise<void>
  complete: (assetId: number) => Promise<unknown>
}

export interface BatchUploadLimits {
  hash: number
  upload: number
}

const defaultLimits: BatchUploadLimits = { hash: 2, upload: 3 }

export function createBatchUploadQueue(
  dependencies: BatchUploadDependencies,
  limits: BatchUploadLimits = defaultLimits,
) {
  const items = reactive<BatchUploadItem[]>([])
  let sequence = 0

  function addFiles(files: File[]) {
    for (const file of files) {
      items.push({
        id: ++sequence,
        file,
        name: file.name,
        size: file.size,
        mimeType: file.type || 'video/mp4',
        status: 'waiting_hash',
        hashProgress: 0,
        uploadProgress: 0,
      })
    }
  }

  function remove(id: number) {
    const index = items.findIndex(item => item.id === id)
    if (index >= 0 && !['uploading', 'completing'].includes(items[index].status)) {
      items.splice(index, 1)
    }
  }

  async function prepare() {
    const candidates = items.filter(item => item.status === 'waiting_hash')
    await runPool(candidates, limits.hash, async (item) => {
      item.status = 'hashing'
      item.errorCode = undefined
      item.errorMessage = undefined
      try {
        item.sha256 = await dependencies.hash(item.file, percentage => item.hashProgress = percentage)
        item.hashProgress = 100
        item.status = 'checking'
      }
      catch (error) {
        fail(item, 'HASH_FAILED', error)
      }
    })

    const seen = new Set<string>()
    for (const item of items.filter(item => item.status === 'checking')) {
      if (item.sha256 && seen.has(item.sha256)) {
        fail(item, 'DUPLICATE_IN_BATCH', '本批次存在重复文件')
        continue
      }
      if (item.sha256) {
        seen.add(item.sha256)
      }
    }

    const checking = items.filter(item => item.status === 'checking' && item.sha256)
    if (!checking.length) {
      return
    }

    try {
      const results = await dependencies.check(checking.map(descriptor))
      checking.forEach((item, index) => applyCheck(item, results[index]))
    }
    catch (error) {
      checking.forEach(item => fail(item, 'CHECK_FAILED', error))
    }
  }

  async function uploadReady(targets = items.filter(item => item.status === 'ready')) {
    await runPool(targets, limits.upload, async (item) => {
      try {
        if (!item.assetId || !item.uploadUrl) {
          const signed = await dependencies.presign(item)
          item.assetId = signed.asset_id
          item.uploadUrl = signed.upload_url
          item.objectKey = signed.object_key
        }
        item.status = 'uploading'
        await dependencies.upload(item.uploadUrl, item.file, percentage => item.uploadProgress = percentage)
        item.uploadProgress = 100
        item.status = 'completing'
        await dependencies.complete(item.assetId)
        item.status = 'success'
      }
      catch (error) {
        fail(item, 'UPLOAD_FAILED', error)
      }
    })
  }

  async function retry(id: number) {
    const item = items.find(current => current.id === id)
    if (!item || item.status !== 'failed') {
      return
    }

    item.errorCode = undefined
    item.errorMessage = undefined
    if (item.assetId && item.uploadUrl) {
      item.status = 'ready'
      await uploadReady([item])
      return
    }

    item.status = 'waiting_hash'
    item.hashProgress = 0
    await prepare()
  }

  return { items, addFiles, remove, prepare, uploadReady, retry }
}

export function useBatchUpload() {
  return createBatchUploadQueue({
    hash: hashFile,
    check: async files => (await checkMedia(files)).data,
    presign: async item => (await presignMedia(descriptor(item))).data,
    upload: uploadFile,
    complete: async assetId => completeMedia(assetId),
  })
}

function descriptor(item: BatchUploadItem): MediaFileDescriptor {
  return {
    name: item.name,
    size: item.size,
    mime_type: item.mimeType,
    sha256: item.sha256 ?? '',
  }
}

function applyCheck(item: BatchUploadItem, result?: MediaCheckResult) {
  if (!result?.accepted) {
    fail(item, result?.code ?? 'CHECK_FAILED', result?.message ?? '文件校验失败')
    return
  }
  item.externalDramaId = result.external_drama_id
  item.externalVideoId = result.external_video_id
  item.episodeNo = result.episode_no
  item.objectKey = result.object_key
  item.status = 'ready'
}

function fail(item: BatchUploadItem, code: string, error: unknown) {
  item.status = 'failed'
  item.errorCode = code
  item.errorMessage = error instanceof Error ? error.message : String(error)
}

async function runPool<T>(items: T[], concurrency: number, worker: (item: T) => Promise<void>) {
  let cursor = 0
  const runners = Array.from({ length: Math.min(Math.max(1, concurrency), items.length) }, async () => {
    while (cursor < items.length) {
      const index = cursor++
      await worker(items[index])
    }
  })
  await Promise.all(runners)
}

function hashFile(file: File, onProgress?: (percentage: number) => void): Promise<string> {
  return new Promise((resolve, reject) => {
    const worker = new Worker(new URL('../workers/sha256.worker.ts', import.meta.url), { type: 'module' })
    worker.onmessage = (event) => {
      if (event.data.type === 'progress') {
        onProgress?.(Math.round(event.data.loaded / event.data.total * 100))
      }
      if (event.data.type === 'done') {
        worker.terminate()
        resolve(event.data.sha256)
      }
    }
    worker.onerror = (event) => {
      worker.terminate()
      reject(new Error(event.message || '文件哈希计算失败'))
    }
    worker.postMessage({ file })
  })
}

function uploadFile(url: string, file: File, onProgress?: (percentage: number) => void): Promise<void> {
  return new Promise((resolve, reject) => {
    const request = new XMLHttpRequest()
    request.open('PUT', url)
    request.setRequestHeader('Content-Type', file.type || 'video/mp4')
    request.upload.onprogress = event => event.lengthComputable && onProgress?.(Math.round(event.loaded / event.total * 100))
    request.onload = () => request.status >= 200 && request.status < 300
      ? resolve()
      : reject(new Error(`R2 上传失败（${request.status}）`))
    request.onerror = () => reject(new Error('网络中断，文件上传失败'))
    request.send(file)
  })
}
