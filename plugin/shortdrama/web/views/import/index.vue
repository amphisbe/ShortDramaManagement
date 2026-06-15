<script setup lang="ts">
import type { UploadFile, UploadInstance } from 'element-plus'
import type { ImportResult, ImportType } from '../../api/import'
import { execute, validateFile } from '../../api/import'
import { ElMessage } from 'element-plus'

defineOptions({ name: 'shortdrama-import' })

const importType = ref<ImportType>('drama')
const selectedFile = ref<File>()
const uploadRef = ref<UploadInstance>()
const loadingAction = ref<'validate' | 'execute' | ''>('')
const result = ref<ImportResult>()

const headers: Record<ImportType, string[]> = {
  drama: ['external_drama_id', 'title', 'display_author_name', 'author_user_id', 'total_episodes', 'cover_url', 'vip_free', 'status', 'description', 'category', 'tags'],
  episode: ['drama_id', 'external_video_id', 'episode_no', 'title', 'play_url', 'poster_url', 'duration_seconds', 'sort_order', 'status', 'display_nickname', 'loop', 'play_ing', 'muted', 'is_playing', 'show_title_arrow', 'show_look_all_btn', 'look_all_btn_text', 'show_bottom_area', 'bottom_area_btn_text', 'tool_info_json'],
}

function onFileChange(file: UploadFile) {
  selectedFile.value = file.raw
  result.value = undefined
}

function downloadTemplate() {
  const table = `<table><tr>${headers[importType.value].map(item => `<th>${item}</th>`).join('')}</tr></table>`
  const blob = new Blob([`\uFEFF${table}`], { type: 'application/vnd.ms-excel;charset=utf-8' })
  const url = URL.createObjectURL(blob)
  const anchor = document.createElement('a')
  anchor.href = url
  anchor.download = importType.value === 'drama' ? '短剧导入模板.xls' : '分集导入模板.xls'
  anchor.click()
  URL.revokeObjectURL(url)
}

async function run(action: 'validate' | 'execute') {
  if (!selectedFile.value) {
    ElMessage.warning('请先选择 Excel 文件')
    return
  }
  loadingAction.value = action
  try {
    const response = action === 'validate'
      ? await validateFile(selectedFile.value, importType.value)
      : await execute(selectedFile.value, importType.value)
    result.value = response.data
    ElMessage.success(action === 'validate' ? '文件校验完成' : '数据导入完成')
  }
  finally { loadingAction.value = '' }
}
</script>

<template>
  <div class="mine-layout import-page">
    <header class="page-heading">
      <div><p>Excel 批量处理</p><h1>数据导入</h1><span>先下载对应模板，填写后进行校验；校验通过的行可部分成功导入。</span></div>
    </header>
    <div class="import-grid">
      <section class="panel setup-panel">
        <div class="step-title">
          <span>1</span><div><h2>选择导入类型</h2><p>短剧资料和分集资料使用不同模板</p></div>
        </div>
        <el-radio-group v-model="importType" size="large" @change="result = undefined">
          <el-radio-button value="drama">
            短剧资料
          </el-radio-button>
          <el-radio-button value="episode">
            分集资料
          </el-radio-button>
        </el-radio-group>
        <el-button class="template-button" plain @click="downloadTemplate">
          <template #icon>
            <ma-svg-icon name="ri:file-excel-2-line" />
          </template>下载当前模板
        </el-button>

        <el-divider />
        <div class="step-title">
          <span>2</span><div><h2>选择 Excel 文件</h2><p>支持 .xlsx、.xls 文件，单次仅处理一个文件</p></div>
        </div>
        <el-upload ref="uploadRef" drag action="#" :auto-upload="false" :limit="1" accept=".xlsx,.xls" :on-change="onFileChange">
          <ma-svg-icon name="ri:upload-cloud-2-line" class="upload-icon" />
          <div class="el-upload__text">
            将文件拖到此处，或<em>点击选择</em>
          </div>
          <template #tip>
            <div class="el-upload__tip">
              系统会按模板表头和每行字段规则进行校验
            </div>
          </template>
        </el-upload>

        <div class="action-row">
          <el-button :loading="loadingAction === 'validate'" @click="run('validate')">
            仅校验
          </el-button>
          <el-button type="primary" :loading="loadingAction === 'execute'" @click="run('execute')">
            校验并导入
          </el-button>
        </div>
      </section>

      <section class="panel result-panel">
        <div class="step-title">
          <span>3</span><div><h2>处理结果</h2><p>失败行不会影响其他合法数据</p></div>
        </div>
        <template v-if="result">
          <div class="result-summary">
            <div class="success">
              <strong>{{ result.success_count }}</strong><span>成功行数</span>
            </div>
            <div class="failure">
              <strong>{{ result.failure_count }}</strong><span>失败行数</span>
            </div>
          </div>
          <el-table :data="result.errors" height="360" stripe>
            <el-table-column prop="row" label="Excel 行号" width="110" align="center" />
            <el-table-column prop="message" label="失败原因" min-width="260" />
          </el-table>
          <el-empty v-if="!result.errors.length" description="全部数据校验通过" :image-size="88" />
        </template>
        <el-empty v-else description="处理结果将在这里显示" />
      </section>
    </div>
  </div>
</template>

<style scoped lang="scss">
/* stylelint-disable declaration-block-single-line-max-declarations */
.import-page { padding: 20px; background: var(--el-bg-color-page); }
.page-heading { margin-bottom: 16px; }
.page-heading p { margin: 0; font-size: 12px; font-weight: 700; color: var(--el-color-primary); letter-spacing: 0.12em; }
.page-heading h1 { margin: 4px 0 6px; font-size: 24px; }

.page-heading span,
.step-title p { font-size: 13px; color: var(--el-text-color-secondary); }
.import-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(360px, 0.9fr); gap: 16px; }
.panel { padding: 22px; background: var(--el-bg-color); border: 1px solid var(--el-border-color-lighter); border-radius: 10px; }
.step-title { display: flex; gap: 12px; align-items: center; margin-bottom: 16px; }
.step-title > span { display: grid; place-items: center; width: 28px; height: 28px; font-weight: 700; color: #fff; background: var(--el-color-primary); border-radius: 50%; }

.step-title h2,
.step-title p { margin: 0; }
.step-title h2 { margin-bottom: 3px; font-size: 16px; }
.template-button { margin-left: 10px; }
.upload-icon { margin-bottom: 10px; font-size: 44px; color: var(--el-color-primary); }
.action-row { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
.result-summary { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px; }
.result-summary > div { padding: 18px; text-align: center; border-radius: 8px; }

.result-summary strong,
.result-summary span { display: block; }
.result-summary strong { font-size: 28px; font-variant-numeric: tabular-nums; }
.result-summary span { margin-top: 5px; font-size: 13px; color: var(--el-text-color-secondary); }
.success { background: var(--el-color-success-light-9); }
.success strong { color: var(--el-color-success); }
.failure { background: var(--el-color-danger-light-9); }
.failure strong { color: var(--el-color-danger); }

@media (width <= 900px) { .import-grid { grid-template-columns: 1fr; } }
</style>
