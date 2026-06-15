<script setup lang="ts">
import type { BatchUploadItem, UploadStatus } from '../../types/media'

defineProps<{ items: BatchUploadItem[] }>()

const emit = defineEmits<{
  remove: [id: number]
  retry: [id: number]
}>()

const statusMap: Record<UploadStatus, { label: string, type: 'info' | 'primary' | 'warning' | 'success' | 'danger' }> = {
  waiting_hash: { label: '待计算', type: 'info' },
  hashing: { label: '计算哈希', type: 'primary' },
  checking: { label: '校验中', type: 'warning' },
  ready: { label: '待上传', type: 'primary' },
  uploading: { label: '上传中', type: 'warning' },
  completing: { label: '入库中', type: 'warning' },
  success: { label: '已完成', type: 'success' },
  failed: { label: '失败', type: 'danger' },
}

function formatSize(bytes: number): string {
  if (bytes < 1024 * 1024) {
    return `${(bytes / 1024).toFixed(1)} KB`
  }
  return `${(bytes / 1024 / 1024).toFixed(1)} MB`
}

function canRemove(status: UploadStatus): boolean {
  return ['waiting_hash', 'ready', 'failed'].includes(status)
}
</script>

<template>
  <div class="upload-table-wrap">
    <el-table :data="items" row-key="id" stripe>
      <el-table-column label="文件" min-width="250">
        <template #default="{ row }">
          <div class="file-cell">
            <span class="file-icon"><ma-svg-icon name="ri:file-video-line" /></span>
            <div>
              <strong>{{ row.name }}</strong>
              <span>{{ formatSize(row.size) }}</span>
            </div>
          </div>
        </template>
      </el-table-column>
      <el-table-column label="视频编号" min-width="170">
        <template #default="{ row }">
          <span class="video-id">{{ row.externalVideoId || '等待解析' }}</span>
        </template>
      </el-table-column>
      <el-table-column label="文件哈希" min-width="150">
        <template #default="{ row }">
          <el-tooltip v-if="row.sha256" :content="row.sha256" placement="top">
            <code>{{ row.sha256.slice(0, 12) }}…</code>
          </el-tooltip>
          <span v-else class="muted">尚未计算</span>
        </template>
      </el-table-column>
      <el-table-column label="状态" width="110" align="center">
        <template #default="{ row }">
          <el-tag :type="statusMap[row.status].type" effect="light">
            {{ statusMap[row.status].label }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="进度" width="180">
        <template #default="{ row }">
          <el-progress
            :percentage="['uploading', 'completing', 'success'].includes(row.status) ? row.uploadProgress : row.hashProgress"
            :status="row.status === 'failed' ? 'exception' : row.status === 'success' ? 'success' : undefined"
          />
        </template>
      </el-table-column>
      <el-table-column label="失败原因" min-width="210">
        <template #default="{ row }">
          <span v-if="row.errorMessage" class="error-text">{{ row.errorMessage }}</span>
          <span v-else-if="row.status === 'success'" class="success-text">上传并入库完成</span>
          <span v-else class="muted">-</span>
        </template>
      </el-table-column>
      <el-table-column label="操作" width="130" fixed="right" align="center">
        <template #default="{ row }">
          <el-button v-if="row.status === 'failed'" link type="primary" @click="emit('retry', row.id)">
            重试
          </el-button>
          <el-button v-if="canRemove(row.status)" link type="danger" @click="emit('remove', row.id)">
            移除
          </el-button>
        </template>
      </el-table-column>
    </el-table>
  </div>
</template>

<style scoped lang="scss">
.upload-table-wrap {
  overflow: hidden;
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 10px;
}

.file-cell {
  display: flex;
  gap: 10px;
  align-items: center;
}

.file-cell strong,
.file-cell span {
  display: block;
}

.file-cell span:not(.file-icon) {
  margin-top: 4px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}

.file-icon {
  display: grid;
  flex: 0 0 auto;
  place-items: center;
  width: 36px;
  height: 36px;
  font-size: 18px;
  color: var(--el-color-primary);
  background: var(--el-color-primary-light-9);
  border-radius: 8px;
}

.video-id,
code {
  font-family: SFMono-Regular, Consolas, monospace;
  font-size: 12px;
}

.muted {
  color: var(--el-text-color-placeholder);
}

.error-text {
  color: var(--el-color-danger);
}

.success-text {
  color: var(--el-color-success);
}
</style>
