import { describe, expect, it } from 'vitest'
import { formatEpisodeProgress } from '$/shortdrama/admin/utils/episode-progress'

describe('formatEpisodeProgress', () => {
  it('按已上传/总集数显示', () => {
    expect(formatEpisodeProgress(72, 80)).toBe('72/80')
  })

  it('缺少数值时按零处理', () => {
    expect(formatEpisodeProgress(undefined, undefined)).toBe('0/0')
  })
})
