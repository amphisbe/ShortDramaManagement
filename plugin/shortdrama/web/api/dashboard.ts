import type { ResponseStruct } from '#/global'

export interface DashboardOverview {
  drama_count: number
  online_episode_count: number
  user_count: number
  play_count: number
}

export interface RankingItem {
  id: number
  external_drama_id: string
  title: string
  cover_url: string
  play_count: number
}

export interface DistributionItem {
  status?: number
  category?: string
  count: number
}

export interface DashboardDistribution {
  status: DistributionItem[]
  category: DistributionItem[]
}

export function overview(): Promise<ResponseStruct<DashboardOverview>> {
  return useHttp().get('/admin/shortdrama/dashboard/overview')
}

export function ranking(): Promise<ResponseStruct<RankingItem[]>> {
  return useHttp().get('/admin/shortdrama/dashboard/ranking')
}

export function distribution(): Promise<ResponseStruct<DashboardDistribution>> {
  return useHttp().get('/admin/shortdrama/dashboard/distribution')
}
