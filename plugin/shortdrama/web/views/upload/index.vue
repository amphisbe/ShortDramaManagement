<script setup lang="ts">
import type { UploadFile } from 'element-plus'
import type { DramaVo } from '../../api/drama'
import { ElMessage } from 'element-plus'
import { computed, onMounted, ref } from 'vue'
import { page as dramaPage } from '../../api/drama'
import { useBatchUpload } from '../../composables/useBatchUpload'
import UploadSummary from './upload-summary.vue'
import UploadTable from './upload-table.vue'

defineOptions({ name: 'shortdrama-upload' })

const queue = useBatchUpload()
const loadingDramas = ref(false)
const preparing = ref(false)
const uploading = ref(false)
const selectedDramaId = ref<number>()
const dramas = ref<DramaVo[]>([])

const selectedDrama = computed(() => dramas.value.find(item => item.id === selectedDramaId.value))
const uploadedEpisodes = computed(() => Number(selectedDrama.value?.uploaded_episodes || 0))
const totalEpisodes = computed(() => Number(selectedDrama.value?.total_episodes || 0))
const waitingCount = computed(() => queue.items.filter(item => item.status === 'waiting_hash').length)
const readyCount = computed(() => queue.items.filter(item => item.status === 'ready').length)
const processing = computed(() => queue.items.some(item => ['hashing', 'checking', 'uploading', 'completing'].includes(item.status)))

async function loadDramas() {
  loadingDramas.value = true
  try {
    const response = await dramaPage({ page: 1, page_size: 200 })
    dramas.value = response.data.list
    if (!selectedDramaId.value && dramas.value[0]?.id) {
      selectedDramaId.value = Number(dramas.value[0].id)
    }
  }
  catch {
    ElMessage.error('短剧列表加载失败，请稍后重试')
  }
  finally {
    loadingDramas.value = false
  }
}

function handleFileChange(uploadFile: UploadFile) {
  if (!selectedDrama.value) {
    ElMessage.warning('请先选择短剧')
    return
  }
  if (!uploadFile.raw) {
    return
  }

  const prefix = `${selectedDrama.value.external_drama_id}_ep`
  if (!uploadFile.name.startsWith(prefix)) {
    ElMessage.error(`文件名必须以 ${prefix} 开头`)
    return
  }
  queue.addFiles([uploadFile.raw])
}

async function prepareFiles() {
  preparing.value = true
  try {
    await queue.prepare()
    const accepted = queue.items.filter(item => item.status === 'ready').length
    const failed = queue.items.filter(item => item.status === 'failed').length
    ElMessage.success(`校验完成：${accepted} 个可上传，${failed} 个需处理`)
  }
  finally {
    preparing.value = false
  }
}

async function uploadFiles() {
  uploading.value = true
  try {
    await queue.uploadReady()
    const success = queue.items.filter(item => item.status === 'success').length
    const failed = queue.items.filter(item => item.status === 'failed').length
    ElMessage.success(`本次完成 ${success} 个文件，失败 ${failed} 个`)
    await loadDramas()
  }
  finally {
    uploading.value = false
  }
}

async function retryFile(id: number) {
  await queue.retry(id)
  await loadDramas()
}

onMounted(loadDramas)
</script>

<template>
  <div class="mine-layout upload-page">
    <header class="page-heading">
      <div>
        <p class="page-eyebrow">
          R2 私有视频桶
        </p>
        <h1>批量上传</h1>
        <p>按“短剧ID_ep01.mp4”命名，可一次选择多个小视频并自动完成哈希去重与分集入库。</p>
      </div>
      <el-button :loading="loadingDramas" @click="loadDramas">
        <template #icon>
          <ma-svg-icon name="ri:refresh-line" />
        </template>
        刷新短剧
      </el-button>
    </header>

    <section class="control-panel">
      <div class="control-main">
        <label class="control-label">选择短剧</label>
        <el-select v-model="selectedDramaId" filterable placeholder="请选择需要上传分集的短剧" class="drama-select" :loading="loadingDramas" :disabled="processing">
          <el-option v-for="item in dramas" :key="item.id" :label="`${item.title}（${item.external_drama_id}）`" :value="Number(item.id)" />
        </el-select>
        <span v-if="selectedDrama" class="naming-hint">
          文件名示例：<code>{{ selectedDrama.external_drama_id }}_ep01.mp4</code>
        </span>
      </div>
      <el-upload
        multiple
        :auto-upload="false"
        :show-file-list="false"
        accept=".mp4,video/mp4"
        :disabled="!selectedDrama || processing"
        :on-change="handleFileChange"
      >
        <el-button type="primary" plain>
          <template #icon>
            <ma-svg-icon name="ri:add-line" />
          </template>
          选择多个视频
        </el-button>
      </el-upload>
    </section>

    <UploadSummary
      aria-label="已上传/总集数"
      :uploaded="uploadedEpisodes"
      :total="totalEpisodes"
      :file-count="queue.items.length"
      :concurrency="3"
    />

    <section class="queue-panel">
      <div class="queue-heading">
        <div>
          <h2>上传队列</h2>
          <p>单个文件失败不会中断其他文件，可在失败原因列查看并单独重试。</p>
        </div>
        <div class="queue-actions">
          <el-button :loading="preparing" :disabled="!waitingCount || processing" @click="prepareFiles">
            计算哈希并校验（{{ waitingCount }}）
          </el-button>
          <el-button type="primary" :loading="uploading" :disabled="!readyCount || processing" @click="uploadFiles">
            上传合法文件（{{ readyCount }}）
          </el-button>
        </div>
      </div>

      <UploadTable v-if="queue.items.length" :items="queue.items" @remove="queue.remove" @retry="retryFile" />
      <el-empty v-else description="请选择短剧并添加 MP4 文件" :image-size="84" />
    </section>
  </div>
</template>

<style scoped lang="scss">
.upload-page {
  min-height: 100%;
  padding: 20px;
  background: var(--el-bg-color-page);
}

.page-heading,
.control-panel,
.queue-heading {
  display: flex;
  gap: 18px;
  align-items: flex-start;
  justify-content: space-between;
}

.page-heading {
  margin-bottom: 18px;
}

.page-heading h1 {
  margin: 2px 0 6px;
  font-size: 24px;
}

.page-heading p,
.queue-heading p {
  margin: 0;
  font-size: 13px;
  color: var(--el-text-color-secondary);
}

.page-eyebrow {
  font-size: 12px !important;
  font-weight: 700;
  color: var(--el-color-primary) !important;
  letter-spacing: 0.12em;
}

.control-panel,
.queue-panel {
  padding: 18px;
  background: var(--el-bg-color);
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 10px;
}

.control-panel {
  align-items: flex-end;
  margin-bottom: 14px;
}

.control-main {
  display: flex;
  flex: 1;
  gap: 12px;
  align-items: center;
  min-width: 0;
}

.control-label {
  flex: 0 0 auto;
  font-size: 14px;
  font-weight: 600;
}

.drama-select {
  width: min(440px, 45vw);
}

.naming-hint {
  overflow: hidden;
  font-size: 12px;
  color: var(--el-text-color-secondary);
  text-overflow: ellipsis;
  white-space: nowrap;
}

.naming-hint code {
  color: var(--el-color-primary);
}

.queue-panel {
  margin-top: 14px;
}

.queue-heading {
  align-items: center;
  margin-bottom: 14px;
}

.queue-heading h2 {
  margin: 0 0 4px;
  font-size: 16px;
}

.queue-actions {
  display: flex;
  gap: 10px;
}

@media (width <= 900px) {
  .control-panel,
  .control-main,
  .queue-heading {
    flex-direction: column;
    align-items: stretch;
  }

  .drama-select {
    width: 100%;
  }

  .queue-actions {
    justify-content: flex-end;
  }
}

@media (width <= 640px) {
  .upload-page {
    padding: 12px;
  }

  .page-heading {
    flex-direction: column;
    align-items: stretch;
  }

  .queue-actions {
    flex-direction: column;
  }
}
</style>
