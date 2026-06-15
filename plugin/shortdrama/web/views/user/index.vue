<script setup lang="tsx">
import type { MaProTableExpose, MaProTableOptions, MaProTableSchema } from '@mineadmin/pro-table'
import type { AppUserVo } from '../../api/user'
import type { Ref } from 'vue'
import { ElAvatar, ElMessage, ElOption, ElTag } from 'element-plus'
import { detail as fetchDetail, page, updateStatus } from '../../api/user'

defineOptions({ name: 'shortdrama-user' })

const tableRef = ref<MaProTableExpose>() as Ref<MaProTableExpose>
const detailVisible = ref(false)
const currentUser = ref<AppUserVo>({})
const detailLoading = ref(false)

const options = ref<MaProTableOptions>({
  adaptionOffsetBottom: 150,
  header: { mainTitle: () => 'App 用户', subTitle: () => '用户资料由 App 端维护，后台仅查看资料和调整账号状态' },
  searchOptions: { fold: false, text: { searchBtn: () => '查询', resetBtn: () => '重置', isFoldBtn: () => '收起', notFoldBtn: () => '展开' } },
  searchFormOptions: { labelWidth: '86px' },
  requestOptions: { api: page },
})

const columns: any[] = [
  { label: '头像', prop: 'avatar_url', width: 76, showOverflowTooltip: false, cellRender: ({ row }) => <ElAvatar size={38} src={row.avatar_url}>{row.nickname?.slice(0, 1)}</ElAvatar> },
  { label: '用户信息', prop: 'nickname', minWidth: 210, cellRender: ({ row }) => (
    <div class="user-cell">
      <strong>{row.nickname || '未设置昵称'}</strong>
      <span>{row.external_user_id}</span>
    </div>
  ) },
  { label: '点赞', prop: 'like_count', width: 90, align: 'right' },
  { label: '收藏', prop: 'favorite_count', width: 90, align: 'right' },
  { label: '观看进度', prop: 'progress_count', width: 110, align: 'right' },
  { label: '账号状态', prop: 'status', width: 100, cellRender: ({ row }) => <ElTag type={row.status === 1 ? 'success' : 'danger'}>{row.status === 1 ? '正常' : '已禁用'}</ElTag> },
  { label: '注册时间', prop: 'created_at', width: 170 },
  { type: 'operation', label: '操作', width: 160, operationConfigure: { actions: [
    { name: 'detail', text: () => '查看详情', icon: 'ri:eye-line', onClick: ({ row }) => showDetail(row.id) },
    { name: 'status', text: ({ row }) => row.status === 1 ? '禁用账号' : '恢复账号', icon: 'ri:shield-user-line', onClick: ({ row }) => toggleStatus(row) },
  ] } },
]

const schema = ref<MaProTableSchema>({
  searchItems: [
    { label: '关键词', prop: 'keyword', render: 'input', renderProps: { placeholder: '昵称或外部用户 ID', clearable: true } },
    { label: '状态', prop: 'status', render: 'Select', renderProps: { clearable: true }, renderSlots: { default: () => [h(ElOption, { label: '已禁用', value: 0 }), h(ElOption, { label: '正常', value: 1 })] } },
  ],
  tableColumns: columns,
})

async function showDetail(id?: number) {
  if (!id) {
    return
  }
  detailVisible.value = true
  detailLoading.value = true
  try {
    currentUser.value = (await fetchDetail(id)).data
  }
  finally {
    detailLoading.value = false
  }
}

async function toggleStatus(row: AppUserVo) {
  if (!row.id) {
    return
  }
  const nextStatus = row.status === 1 ? 0 : 1
  await updateStatus(row.id, nextStatus)
  ElMessage.success(nextStatus === 1 ? '账号已恢复' : '账号已禁用')
  await tableRef.value.refresh()
}
</script>

<template>
  <div class="mine-layout pt-3">
    <MaProTable ref="tableRef" :options="options" :schema="schema">
      <template #empty>
        <el-empty description="暂无 App 用户" />
      </template>
    </MaProTable>
    <el-drawer v-model="detailVisible" title="App 用户详情" size="460px">
      <div v-loading="detailLoading" class="user-detail">
        <div class="profile-heading">
          <ElAvatar :size="72" :src="currentUser.avatar_url">
            {{ currentUser.nickname?.slice(0, 1) }}
          </ElAvatar><div><h2>{{ currentUser.nickname || '未设置昵称' }}</h2><p>{{ currentUser.external_user_id }}</p></div>
        </div>
        <el-alert title="用户资料由 App 端连接 Python 服务修改，管理后台仅提供只读查看。" type="info" :closable="false" show-icon />
        <el-descriptions :column="1" border>
          <el-descriptions-item label="用户编号">
            {{ currentUser.id }}
          </el-descriptions-item>
          <el-descriptions-item label="外部用户 ID">
            {{ currentUser.external_user_id }}
          </el-descriptions-item>
          <el-descriptions-item label="账号状态">
            {{ currentUser.status === 1 ? '正常' : '已禁用' }}
          </el-descriptions-item>
          <el-descriptions-item label="点赞数量">
            {{ currentUser.like_count || 0 }}
          </el-descriptions-item>
          <el-descriptions-item label="收藏数量">
            {{ currentUser.favorite_count || 0 }}
          </el-descriptions-item>
          <el-descriptions-item label="观看进度">
            {{ currentUser.progress_count || 0 }}
          </el-descriptions-item>
          <el-descriptions-item label="注册时间">
            {{ currentUser.created_at }}
          </el-descriptions-item>
          <el-descriptions-item label="更新时间">
            {{ currentUser.updated_at }}
          </el-descriptions-item>
        </el-descriptions>
      </div>
    </el-drawer>
  </div>
</template>

<style scoped lang="scss">
/* stylelint-disable declaration-block-single-line-max-declarations */
:deep(.user-cell strong),
:deep(.user-cell span) { display: block; }
:deep(.user-cell span) { margin-top: 5px; font-size: 12px; color: var(--el-text-color-secondary); }
.user-detail { display: grid; gap: 20px; }
.profile-heading { display: flex; gap: 16px; align-items: center; }
.profile-heading h2 { margin: 0 0 6px; font-size: 20px; }
.profile-heading p { margin: 0; color: var(--el-text-color-secondary); }
</style>
