import { createSHA256 } from 'hash-wasm'

interface HashRequest {
  file: File
}

globalThis.onmessage = async (event: MessageEvent<HashRequest>) => {
  const { file } = event.data
  const chunkSize = 4 * 1024 * 1024
  const hasher = await createSHA256()
  hasher.init()

  for (let offset = 0; offset < file.size; offset += chunkSize) {
    const loaded = Math.min(offset + chunkSize, file.size)
    hasher.update(new Uint8Array(await file.slice(offset, loaded).arrayBuffer()))
    globalThis.postMessage({ type: 'progress', loaded, total: file.size })
  }

  globalThis.postMessage({ type: 'done', sha256: hasher.digest('hex') })
}
