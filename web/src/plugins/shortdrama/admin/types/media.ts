export type UploadStatus =
  | 'waiting_hash'
  | 'hashing'
  | 'checking'
  | 'ready'
  | 'uploading'
  | 'completing'
  | 'success'
  | 'failed'

export interface MediaFileDescriptor {
  name: string
  size: number
  mime_type: string
  sha256: string
}

export interface MediaCheckResult {
  name: string
  accepted: boolean
  code: string | null
  message?: string | null
  external_drama_id?: string
  external_video_id?: string
  episode_no?: number
  object_key?: string
}

export interface MediaPresignResult {
  asset_id: number
  upload_url: string
  object_key: string
  expires_in: number
}

export interface BatchUploadItem {
  id: number
  file: File
  name: string
  size: number
  mimeType: string
  status: UploadStatus
  hashProgress: number
  uploadProgress: number
  sha256?: string
  assetId?: number
  uploadUrl?: string
  objectKey?: string
  externalDramaId?: string
  externalVideoId?: string
  episodeNo?: number
  errorCode?: string
  errorMessage?: string
}
