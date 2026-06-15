import type { ResponseStruct } from '#/global'

export type ImportType = 'drama' | 'episode'

export interface ImportErrorItem {
  row: number
  message: string
}

export interface ImportResult {
  success_count: number
  failure_count: number
  errors: ImportErrorItem[]
  report_id?: string
}

function payload(file: File, type: ImportType): FormData {
  const data = new FormData()
  data.append('file', file)
  data.append('type', type)
  return data
}

export function validateFile(file: File, type: ImportType): Promise<ResponseStruct<ImportResult>> {
  return useHttp().post('/admin/shortdrama/imports/validate', payload(file, type))
}

export function execute(file: File, type: ImportType): Promise<ResponseStruct<ImportResult>> {
  return useHttp().post('/admin/shortdrama/imports/execute', payload(file, type))
}

export function report(id: string): Promise<ResponseStruct<ImportResult>> {
  return useHttp().get(`/admin/shortdrama/imports/${id}/report`)
}
