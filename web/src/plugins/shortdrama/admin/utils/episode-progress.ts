export function formatEpisodeProgress(uploaded?: number | null, total?: number | null): string {
  return `${uploaded ?? 0}/${total ?? 0}`
}
