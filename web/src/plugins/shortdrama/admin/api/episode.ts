import type { PageList, ResponseStruct } from '#/global'

export interface EpisodeVo {
  id?: number
  drama_id?: number
  external_video_id?: string
  episode_no?: number
  title?: string
  play_url?: string
  poster_url?: string
  duration_seconds?: number
  sort_order?: number
  status?: 0 | 1
  display_nickname?: string
  loop?: 0 | 1
  play_ing?: 0 | 1
  muted?: 0 | 1
  is_playing?: 0 | 1
  show_title_arrow?: 0 | 1
  show_look_all_btn?: 0 | 1
  look_all_btn_text?: string
  show_bottom_area?: 0 | 1
  bottom_area_btn_text?: string
  tool_info_json?: string
  created_at?: string
  updated_at?: string
}

export interface EpisodeSearch {
  keyword?: string
  drama_id?: number
  status?: number
  created_at?: string[]
  page?: number
  page_size?: number
}

export function page(params: EpisodeSearch): Promise<ResponseStruct<PageList<EpisodeVo>>> {
  return useHttp().get('/admin/shortdrama/episodes', { params })
}

export function create(data: EpisodeVo): Promise<ResponseStruct<EpisodeVo>> {
  return useHttp().post('/admin/shortdrama/episodes', data)
}

export function update(id: number, data: EpisodeVo): Promise<ResponseStruct<null>> {
  return useHttp().put(`/admin/shortdrama/episodes/${id}`, data)
}

export function batchStatus(ids: number[], status: number): Promise<ResponseStruct<null>> {
  return useHttp().post('/admin/shortdrama/episodes/batch-status', { ids, status })
}
