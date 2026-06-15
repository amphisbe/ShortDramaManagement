import type { PageList, ResponseStruct } from '#/global'

export interface DramaVo {
  id?: number
  external_drama_id?: string
  title?: string
  display_author_name?: string
  author_user_id?: number
  total_episodes?: number
  uploaded_episodes?: number
  episode_progress?: string
  cover_url?: string
  vip_free?: 0 | 1
  status?: 0 | 1 | 2
  description?: string
  category?: string
  tags?: string
  play_count?: number
  follow_count?: number
  created_at?: string
  updated_at?: string
}

export interface DramaSearch {
  keyword?: string
  category?: string
  status?: number
  created_at?: string[]
  page?: number
  page_size?: number
}

export function page(params: DramaSearch): Promise<ResponseStruct<PageList<DramaVo>>> {
  return useHttp().get('/admin/shortdrama/dramas', { params })
}

export function create(data: DramaVo): Promise<ResponseStruct<DramaVo>> {
  return useHttp().post('/admin/shortdrama/dramas', data)
}

export function update(id: number, data: DramaVo): Promise<ResponseStruct<null>> {
  return useHttp().put(`/admin/shortdrama/dramas/${id}`, data)
}

export function batchStatus(ids: number[], status: number): Promise<ResponseStruct<null>> {
  return useHttp().post('/admin/shortdrama/dramas/batch-status', { ids, status })
}
