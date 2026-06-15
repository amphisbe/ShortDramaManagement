import type { PageList, ResponseStruct } from '#/global'

export interface AppUserVo {
  id?: number
  external_user_id?: string
  nickname?: string
  avatar_url?: string
  status?: 0 | 1
  like_count?: number
  favorite_count?: number
  progress_count?: number
  created_at?: string
  updated_at?: string
}

export interface AppUserSearch {
  keyword?: string
  status?: number
  created_at?: string[]
  page?: number
  page_size?: number
}

export function page(params: AppUserSearch): Promise<ResponseStruct<PageList<AppUserVo>>> {
  return useHttp().get('/admin/shortdrama/users', { params })
}

export function detail(id: number): Promise<ResponseStruct<AppUserVo>> {
  return useHttp().get(`/admin/shortdrama/users/${id}`)
}

export function updateStatus(id: number, status: number): Promise<ResponseStruct<null>> {
  return useHttp().put(`/admin/shortdrama/users/${id}/status`, { status })
}
