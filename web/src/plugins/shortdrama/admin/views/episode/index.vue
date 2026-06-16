<script setup lang="tsx">
import type { MaProTableExpose, MaProTableOptions, MaProTableSchema } from '@mineadmin/pro-table'
import type { FormInstance, FormRules } from 'element-plus'
import type { EpisodeVo } from '../../api/episode'
import type { Ref } from 'vue'
import { ElMessage, ElOption, ElTag } from 'element-plus'
import { batchStatus, create, page, update } from '../../api/episode'

defineOptions({ name: 'shortdrama-episode' })

const tableRef = ref<MaProTableExpose>() as Ref<MaProTableExpose>
const selections = ref<EpisodeVo[]>([])
const dialogVisible = ref(false)
const saving = ref(false)
const editing = ref(false)
const formRef = ref<FormInstance>()

function emptyEpisode(): EpisodeVo {
  return {
    drama_id: 1, external_video_id: '', episode_no: 1, title: '', play_url: '', poster_url: '',
    duration_seconds: 0, sort_order: 0, status: 0, display_nickname: '', loop: 0,
    play_ing: 0, muted: 0, is_playing: 0, show_title_arrow: 1, show_look_all_btn: 1,
    look_all_btn_text: '查看全集', show_bottom_area: 1, bottom_area_btn_text: '立即观看', tool_info_json: '{}',
  }
}
const model = ref<EpisodeVo>(emptyEpisode())
const rules: FormRules = {
  drama_id: [{ required: true, message: '请输入短剧 ID', trigger: 'blur' }],
  external_video_id: [{ required: true, message: '请输入外部视频 ID', trigger: 'blur' }],
  episode_no: [{ required: true, message: '请输入集数', trigger: 'blur' }],
  title: [{ required: true, message: '请输入分集标题', trigger: 'blur' }],
  play_url: [{ required: true, message: '请输入播放地址', trigger: 'blur' }],
  poster_url: [{ required: true, message: '请输入海报地址', trigger: 'blur' }],
  display_nickname: [{ required: true, message: '请输入展示昵称', trigger: 'blur' }],
}

const options = ref<MaProTableOptions>({
  adaptionOffsetBottom: 150,
  header: { mainTitle: () => '分集管理', subTitle: () => '维护分集资料、播放配置与上下线状态' },
  tableOptions: { on: { onSelectionChange: (rows: EpisodeVo[]) => selections.value = rows } },
  searchOptions: { fold: false, text: { searchBtn: () => '查询', resetBtn: () => '重置', isFoldBtn: () => '收起', notFoldBtn: () => '展开' } },
  searchFormOptions: { labelWidth: '86px' },
  requestOptions: { api: page },
})

const columns: any[] = [
  { type: 'selection', width: 48 },
  { label: '视频 ID', prop: 'external_video_id', minWidth: 180 },
  { label: '短剧 ID', prop: 'drama_id', width: 100, align: 'center' },
  { label: '集数', prop: 'episode_no', width: 82, align: 'center', cellRender: ({ row }) => (
    <strong>
      第
      {row.episode_no}
      {' '}
      集
    </strong>
  ) },
  { label: '分集标题', prop: 'title', minWidth: 220 },
  { label: '时长', prop: 'duration_seconds', width: 100, align: 'right', cellRender: ({ row }) => `${row.duration_seconds || 0} 秒` },
  { label: '状态', prop: 'status', width: 90, cellRender: ({ row }) => <ElTag type={row.status === 1 ? 'success' : 'info'}>{row.status === 1 ? '已上线' : '已下线'}</ElTag> },
  { label: '更新时间', prop: 'updated_at', width: 170 },
  { type: 'operation', label: '操作', width: 100, operationConfigure: { actions: [{ name: 'edit', text: () => '编辑', icon: 'ri:edit-line', onClick: ({ row }) => openForm(row) }] } },
]

const schema = ref<MaProTableSchema>({
  searchItems: [
    { label: '关键词', prop: 'keyword', render: 'input', renderProps: { placeholder: '标题或视频 ID', clearable: true } },
    { label: '短剧 ID', prop: 'drama_id', render: 'inputNumber', renderProps: { min: 1, controlsPosition: 'right' } },
    { label: '状态', prop: 'status', render: 'Select', renderProps: { clearable: true }, renderSlots: { default: () => [h(ElOption, { label: '已下线', value: 0 }), h(ElOption, { label: '已上线', value: 1 })] } },
  ],
  tableColumns: columns,
})

function openForm(row?: EpisodeVo) {
  editing.value = Boolean(row?.id)
  model.value = row ? { ...row, tool_info_json: typeof row.tool_info_json === 'string' ? row.tool_info_json : JSON.stringify(row.tool_info_json ?? {}) } : emptyEpisode()
  dialogVisible.value = true
  nextTick(() => formRef.value?.clearValidate())
}

async function saveEpisode() {
  await formRef.value?.validate()
  saving.value = true
  try {
    if (editing.value && model.value.id) {
      await update(model.value.id, model.value)
    }
    else { await create(model.value) }
    ElMessage.success(editing.value ? '分集信息已更新' : '分集已创建')
    dialogVisible.value = false
    await tableRef.value.refresh()
  }
  finally { saving.value = false }
}

async function changeStatus(status: number) {
  const ids = selections.value.map(item => item.id).filter(Boolean) as number[]
  if (!ids.length) {
    return
  }
  await batchStatus(ids, status)
  const message = status === 1 ? '选中分集已上线' : '选中分集已下线'
  ElMessage.success(message)
  await tableRef.value.refresh()
}
</script>

<template>
  <div class="mine-layout pt-3">
    <MaProTable ref="tableRef" :options="options" :schema="schema">
      <template #actions>
        <el-button v-auth="['shortdrama:episode:update']" type="primary" @click="openForm()">
          新增分集
        </el-button>
      </template>
      <template #toolbarLeft>
        <el-button v-auth="['shortdrama:episode:update']" plain :disabled="!selections.length" @click="changeStatus(1)">
          批量上线
        </el-button>
        <el-button v-auth="['shortdrama:episode:update']" plain :disabled="!selections.length" @click="changeStatus(0)">
          批量下线
        </el-button>
      </template>
      <template #empty>
        <el-empty description="暂无分集数据" />
      </template>
    </MaProTable>

    <el-dialog v-model="dialogVisible" :title="editing ? '编辑分集' : '新增分集'" width="860px" destroy-on-close>
      <el-form ref="formRef" :model="model" :rules="rules" label-width="110px">
        <el-tabs>
          <el-tab-pane label="基础信息">
            <div class="form-grid">
              <el-form-item label="短剧 ID" prop="drama_id">
                <el-input-number v-model="model.drama_id" :min="1" />
              </el-form-item>
              <el-form-item label="外部视频 ID" prop="external_video_id">
                <el-input v-model="model.external_video_id" maxlength="24" />
              </el-form-item>
              <el-form-item label="集数" prop="episode_no">
                <el-input-number v-model="model.episode_no" :min="1" />
              </el-form-item>
              <el-form-item label="排序">
                <el-input-number v-model="model.sort_order" :min="0" />
              </el-form-item>
              <el-form-item label="分集标题" prop="title">
                <el-input v-model="model.title" maxlength="500" />
              </el-form-item>
              <el-form-item label="展示昵称" prop="display_nickname">
                <el-input v-model="model.display_nickname" maxlength="100" />
              </el-form-item>
              <el-form-item label="视频时长">
                <el-input-number v-model="model.duration_seconds" :min="0" /><span class="unit">秒</span>
              </el-form-item>
              <el-form-item label="状态">
                <el-radio-group v-model="model.status">
                  <el-radio-button :value="0">
                    下线
                  </el-radio-button><el-radio-button :value="1">
                    上线
                  </el-radio-button>
                </el-radio-group>
              </el-form-item>
            </div>
            <el-form-item label="播放地址" prop="play_url">
              <el-input v-model="model.play_url" />
            </el-form-item>
            <el-form-item label="海报地址" prop="poster_url">
              <el-input v-model="model.poster_url" />
            </el-form-item>
          </el-tab-pane>
          <el-tab-pane label="播放配置">
            <div class="switch-grid">
              <el-form-item label="循环播放">
                <el-switch v-model="model.loop" :active-value="1" :inactive-value="0" />
              </el-form-item>
              <el-form-item label="自动播放">
                <el-switch v-model="model.play_ing" :active-value="1" :inactive-value="0" />
              </el-form-item>
              <el-form-item label="默认静音">
                <el-switch v-model="model.muted" :active-value="1" :inactive-value="0" />
              </el-form-item>
              <el-form-item label="播放状态">
                <el-switch v-model="model.is_playing" :active-value="1" :inactive-value="0" />
              </el-form-item>
              <el-form-item label="标题箭头">
                <el-switch v-model="model.show_title_arrow" :active-value="1" :inactive-value="0" />
              </el-form-item>
              <el-form-item label="全集按钮">
                <el-switch v-model="model.show_look_all_btn" :active-value="1" :inactive-value="0" />
              </el-form-item>
              <el-form-item label="底部区域">
                <el-switch v-model="model.show_bottom_area" :active-value="1" :inactive-value="0" />
              </el-form-item>
            </div>
            <el-form-item label="全集按钮文字">
              <el-input v-model="model.look_all_btn_text" />
            </el-form-item>
            <el-form-item label="底部按钮文字">
              <el-input v-model="model.bottom_area_btn_text" />
            </el-form-item>
            <el-form-item label="工具栏配置">
              <el-input v-model="model.tool_info_json" type="textarea" :rows="5" placeholder="请输入合法的 JSON 文本" />
            </el-form-item>
          </el-tab-pane>
        </el-tabs>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">
          取消
        </el-button><el-button type="primary" :loading="saving" @click="saveEpisode">
          保存
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped lang="scss">
/* stylelint-disable declaration-block-single-line-max-declarations */
.form-grid,
.switch-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 18px; }
.form-grid :deep(.el-input-number) { width: 100%; }
.unit { margin-left: 8px; color: var(--el-text-color-secondary); }

@media (width <= 720px) {
  .form-grid,
  .switch-grid { grid-template-columns: 1fr; }
}
</style>
