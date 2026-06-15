<script setup lang="ts">
defineProps<{
  uploaded: number
  total: number
  fileCount: number
  concurrency: number
}>()
</script>

<template>
  <section class="upload-summary" aria-label="上传统计">
    <article class="summary-card summary-card--primary">
      <div class="summary-icon">
        <ma-svg-icon name="ri:play-list-add-line" />
      </div>
      <strong class="summary-value">{{ uploaded }}/{{ total }}</strong>
      <span class="summary-label">已上传/总集数</span>
    </article>
    <article class="summary-card">
      <div class="summary-icon">
        <ma-svg-icon name="ri:file-video-line" />
      </div>
      <strong class="summary-value">{{ fileCount }}</strong>
      <span class="summary-label">本次文件</span>
    </article>
    <article class="summary-card">
      <div class="summary-icon">
        <ma-svg-icon name="ri:speed-up-line" />
      </div>
      <strong class="summary-value">{{ concurrency }}</strong>
      <span class="summary-label">上传并发</span>
    </article>
  </section>
</template>

<style scoped lang="scss">
.upload-summary {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
}

.summary-card {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 132px;
  padding: 18px;
  overflow: hidden;
  text-align: center;
  background: var(--el-bg-color);
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 10px;
}

.summary-card--primary {
  background: var(--el-color-primary-light-9);
  border-color: var(--el-color-primary-light-7);
}

.summary-icon {
  position: absolute;
  top: 14px;
  right: 14px;
  display: grid;
  place-items: center;
  width: 34px;
  height: 34px;
  font-size: 18px;
  color: var(--el-color-primary);
  background: color-mix(in srgb, var(--el-color-primary) 10%, transparent);
  border-radius: 8px;
}

.summary-value {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 54px;
  font-size: 30px;
  font-weight: 750;
  font-variant-numeric: tabular-nums;
  color: var(--el-text-color-primary);
}

.summary-card--primary .summary-value {
  color: var(--el-color-primary);
}

.summary-label {
  font-size: 14px;
  color: var(--el-text-color-secondary);
}

@media (width <= 720px) {
  .upload-summary {
    grid-template-columns: 1fr;
  }
}
</style>
