<script setup lang="tsx">
import type { MaProTableExpose, MaProTableOptions, MaProTableSchema } from '@mineadmin/pro-table'
import type { DramaVo } from '../../api/drama'
import type { Ref } from 'vue'
import { ElImage, ElMessage, ElOption, ElTag } from 'element-plus'
import { batchStatus, page } from '../../api/drama'
import { formatEpisodeProgress } from '../../utils/episode-progress'
import DramaForm from './form.vue'

defineOptions({ name: 'shortdrama-drama' })

const tableRef = ref<MaProTableExpose>() as Ref<MaProTableExpose>
const formRef = ref<InstanceType<typeof DramaForm>>()
const selections = ref<DramaVo[]>([])

const statusMap: Record<number, { label: string, type: 'info' | 'warning' | 'success' }> = {
  0: { label: '下线', type: 'info' },
  1: { label: '连载中', type: 'warning' },
  2: { label: '已完结', type: 'success' },
}

const options = ref<MaProTableOptions>({
  adaptionOffsetBottom: 150,
  header: { mainTitle: () => '短剧管理', subTitle: () => '维护短剧资料、上下线状态与分集完整度' },
  tableOptions: { on: { onSelectionChange: (rows: DramaVo[]) => selections.value = rows } },
  searchOptions: { fold: false, text: { searchBtn: () => '查询', resetBtn: () => '重置', isFoldBtn: () => '收起', notFoldBtn: () => '展开' } },
  searchFormOptions: { labelWidth: '86px' },
  requestOptions: { api: page },
})

const columns: any[] = [
  { type: 'selection', width: 48 },
  { label: '封面', prop: 'cover_url', width: 82, showOverflowTooltip: false, cellRender: ({ row }) => (
    <ElImage class="drama-cover" src={row.cover_url} fit="cover" />
  ) },
  { label: '短剧信息', prop: 'title', minWidth: 220, cellRender: ({ row }) => (
    <div class="title-cell">
      <strong>{row.title}</strong>
      <span>{row.external_drama_id}</span>
    </div>
  ) },
  { label: '分类', prop: 'category', width: 110 },
  { label: '状态', prop: 'status', width: 100, cellRender: ({ row }) => <ElTag type={statusMap[row.status]?.type ?? 'info'}>{statusMap[row.status]?.label ?? '未知'}</ElTag> },
  { label: '已上传/总集数', prop: 'episode_progress', width: 150, align: 'center', cellRender: ({ row }) => <strong class="episode-progress">{formatEpisodeProgress(row.uploaded_episodes, row.total_episodes)}</strong> },
  { label: '播放量', prop: 'play_count', width: 110, align: 'right', cellRender: ({ row }) => Number(row.play_count || 0).toLocaleString('zh-CN') },
  { label: '更新时间', prop: 'updated_at', width: 170 },
  { type: 'operation', label: '操作', width: 100, operationConfigure: { actions: [{ name: 'edit', text: () => '编辑', icon: 'ri:edit-line', onClick: ({ row }) => formRef.value?.open(row) }] } },
]

const schema = ref<MaProTableSchema>({
  searchItems: [
    { label: '关键词', prop: 'keyword', render: 'input', renderProps: { placeholder: '短剧名称或外部 ID', clearable: true } },
    { label: '分类', prop: 'category', render: 'input', renderProps: { clearable: true } },
    { label: '状态', prop: 'status', render: 'Select', renderProps: { clearable: true }, renderSlots: { default: () => [h(ElOption, { label: '下线', value: 0 }), h(ElOption, { label: '连载中', value: 1 }), h(ElOption, { label: '已完结', value: 2 })] } },
  ],
  tableColumns: columns,
})

async function changeStatus(status: number) {
  const ids = selections.value.map(item => item.id).filter(Boolean) as number[]
  if (!ids.length) {
    return
  }
  await batchStatus(ids, status)
  ElMessage.success('批量状态已更新')
  selections.value = []
  await tableRef.value.refresh()
}
</script>

<template>
  <div class="mine-layout pt-3">
    <MaProTable ref="tableRef" :options="options" :schema="schema">
      <template #actions>
        <el-button v-auth="['shortdrama:drama:create']" type="primary" @click="formRef?.open()">
          新增短剧
        </el-button>
      </template>
      <template #toolbarLeft>
        <el-button v-auth="['shortdrama:drama:update']" plain :disabled="!selections.length" @click="changeStatus(1)">
          设为连载中
        </el-button>
        <el-button v-auth="['shortdrama:drama:update']" plain :disabled="!selections.length" @click="changeStatus(2)">
          设为已完结
        </el-button>
        <el-button v-auth="['shortdrama:drama:update']" plain :disabled="!selections.length" @click="changeStatus(0)">
          设为下线
        </el-button>
      </template>
      <template #empty>
        <el-empty description="暂无短剧，请先新增短剧" />
      </template>
    </MaProTable>
    <DramaForm ref="formRef" @saved="tableRef?.refresh()" />
  </div>
</template>

<style scoped lang="scss">
/* stylelint-disable declaration-block-single-line-max-declarations */
:deep(.drama-cover) { width: 46px; height: 64px; background: var(--el-fill-color-light); border-radius: 5px; }
:deep(.cover-empty) { display: grid; place-items: center; height: 100%; font-size: 10px; color: var(--el-text-color-placeholder); }

:deep(.title-cell strong),
:deep(.title-cell span) { display: block; }
:deep(.title-cell span) { margin-top: 5px; font-size: 12px; color: var(--el-text-color-secondary); }
:deep(.episode-progress) { display: inline-flex; align-items: center; justify-content: center; min-width: 72px; min-height: 34px; font-size: 16px; font-variant-numeric: tabular-nums; color: var(--el-color-primary); background: var(--el-color-primary-light-9); border-radius: 6px; }
</style>
