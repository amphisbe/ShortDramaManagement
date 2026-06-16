import type { ResponseStruct } from '#/global'
import type { MediaCheckResult, MediaFileDescriptor, MediaPresignResult } from '../types/media'

export function checkMedia(files: MediaFileDescriptor[]): Promise<ResponseStruct<MediaCheckResult[]>> {
  return useHttp().post('/admin/shortdrama/media/check', { files })
}

export function presignMedia(file: MediaFileDescriptor): Promise<ResponseStruct<MediaPresignResult>> {
  return useHttp().post('/admin/shortdrama/media/presign', file)
}

export function completeMedia(assetId: number): Promise<ResponseStruct<Record<string, unknown>>> {
  return useHttp().post('/admin/shortdrama/media/complete', { asset_id: assetId })
}

export interface ImagePresignInput {
  external_drama_id: string
  size: number
  mime_type: 'image/jpeg' | 'image/png' | 'image/webp'
}

export interface ImagePresignResult {
  upload_url: string
  public_url: string
  expires_in: number
}

export function presignImage(input: ImagePresignInput): Promise<ResponseStruct<ImagePresignResult>> {
  return useHttp().post('/admin/shortdrama/images/upload/presign', input)
}
