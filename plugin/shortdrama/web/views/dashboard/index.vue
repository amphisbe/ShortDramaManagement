<script setup lang="ts">
import type { DashboardDistribution, DashboardOverview, RankingItem } from '../../api/dashboard'
import { distribution, overview, ranking } from '../../api/dashboard'
import { ElMessage } from 'element-plus'

defineOptions({ name: 'shortdrama-dashboard' })

const loading = ref(false)
const metrics = ref<DashboardOverview>({
  drama_count: 0,
  online_episode_count: 0,
  user_count: 0,
  play_count: 0,
})
const rankingList = ref<RankingItem[]>([])
const distributions = ref<DashboardDistribution>({ status: [], category: [] })

const metricCards = computed(() => [
  { label: '短剧总数', value: metrics.value.drama_count, icon: 'ri:film-line', tone: 'blue' },
  { label: '已上线分集', value: metrics.value.online_episode_count, icon: 'ri:play-circle-line', tone: 'cyan' },
  { label: 'App 用户', value: metrics.value.user_count, icon: 'ri:user-3-line', tone: 'violet' },
  { label: '累计播放', value: metrics.value.play_count, icon: 'ri:bar-chart-box-line', tone: 'amber' },
])

const maxCategoryCount = computed(() => Math.max(1, ...distributions.value.category.map(item => item.count)))

function statusName(status?: number): string {
  return ({ 0: '下线', 1: '连载中', 2: '已完结' } as Record<number, string>)[status ?? -1] ?? '未知'
}

async function loadDashboard() {
  loading.value = true
  try {
    const [overviewResponse, rankingResponse, distributionResponse] = await Promise.all([
      overview(),
      ranking(),
      distribution(),
    ])
    metrics.value = overviewResponse.data
    rankingList.value = rankingResponse.data
    distributions.value = distributionResponse.data
  }
  catch {
    ElMessage.error('数据看板加载失败，请稍后重试')
  }
  finally {
    loading.value = false
  }
}

onMounted(loadDashboard)
</script>

<template>
  <div v-loading="loading" class="mine-layout shortdrama-dashboard">
    <header class="page-heading">
      <div>
        <p class="page-eyebrow">
          实时运营概览
        </p>
        <h1>数据看板</h1>
        <p class="page-description">
          查看短剧内容规模、用户数量和播放表现。
        </p>
      </div>
      <el-button :loading="loading" @click="loadDashboard">
        <template #icon>
          <ma-svg-icon name="ri:refresh-line" />
        </template>
        刷新数据
      </el-button>
    </header>

    <section class="metric-grid">
      <article v-for="item in metricCards" :key="item.label" class="metric-card" :data-tone="item.tone">
        <div class="metric-icon">
          <ma-svg-icon :name="item.icon" />
        </div>
        <div class="metric-value">
          {{ item.value.toLocaleString('zh-CN') }}
        </div>
        <div class="metric-label">
          {{ item.label }}
        </div>
      </article>
    </section>

    <section class="content-grid">
      <article class="ranking-panel panel">
        <div class="panel-heading">
          <div>
            <h2>播放排行</h2>
            <p>按累计播放量展示前 10 部短剧</p>
          </div>
        </div>
        <el-table :data="rankingList" height="410" stripe>
          <el-table-column type="index" label="排名" width="64" align="center" />
          <el-table-column label="短剧" min-width="240">
            <template #default="{ row }">
              <div class="drama-cell">
                <el-image class="cover" :src="row.cover_url" fit="cover">
                  <template #error>
                    <div class="cover-fallback">
                      暂无封面
                    </div>
                  </template>
                </el-image>
                <div>
                  <strong>{{ row.title }}</strong>
                  <span>{{ row.external_drama_id }}</span>
                </div>
              </div>
            </template>
          </el-table-column>
          <el-table-column prop="play_count" label="播放量" width="140" align="right">
            <template #default="{ row }">
              {{ Number(row.play_count || 0).toLocaleString('zh-CN') }}
            </template>
          </el-table-column>
        </el-table>
      </article>

      <div class="distribution-column">
        <article class="panel">
          <div class="panel-heading">
            <div><h2>内容状态</h2><p>当前短剧状态分布</p></div>
          </div>
          <div class="status-list">
            <div v-for="item in distributions.status" :key="item.status" class="status-item">
              <span>{{ statusName(item.status) }}</span>
              <strong>{{ item.count }}</strong>
            </div>
          </div>
        </article>
        <article class="panel category-panel">
          <div class="panel-heading">
            <div><h2>题材分布</h2><p>按短剧分类统计</p></div>
          </div>
          <div v-if="distributions.category.length" class="category-list">
            <div v-for="item in distributions.category" :key="item.category" class="category-item">
              <div><span>{{ item.category || '未分类' }}</span><strong>{{ item.count }}</strong></div>
              <el-progress :percentage="Math.round(item.count / maxCategoryCount * 100)" :show-text="false" />
            </div>
          </div>
          <el-empty v-else description="暂无分类数据" :image-size="72" />
        </article>
      </div>
    </section>
  </div>
</template>

<style scoped lang="scss">
/* stylelint-disable declaration-block-single-line-max-declarations */
.shortdrama-dashboard { padding: 20px; background: var(--el-bg-color-page); }
.page-heading { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 18px; }
.page-heading h1 { margin: 2px 0 6px; font-size: 24px; color: var(--el-text-color-primary); }
.page-eyebrow { margin: 0; font-size: 12px; font-weight: 700; color: var(--el-color-primary); letter-spacing: 0.12em; }

.page-description,
.panel-heading p { margin: 0; font-size: 13px; color: var(--el-text-color-secondary); }
.metric-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
.metric-card { position: relative; min-height: 132px; padding: 18px; overflow: hidden; background: var(--el-bg-color); border: 1px solid var(--el-border-color-lighter); border-radius: 10px; }
.metric-card::after { position: absolute; right: -28px; bottom: -36px; width: 108px; height: 108px; content: ""; background: color-mix(in srgb, var(--el-color-primary) 9%, transparent); border-radius: 50%; }
.metric-icon { position: absolute; top: 16px; right: 16px; display: grid; place-items: center; width: 34px; height: 34px; font-size: 19px; color: var(--el-color-primary); background: var(--el-color-primary-light-9); border-radius: 8px; }
.metric-value { display: flex; align-items: center; justify-content: center; min-height: 58px; font-size: 30px; font-weight: 750; font-variant-numeric: tabular-nums; color: var(--el-text-color-primary); }
.metric-label { font-size: 14px; color: var(--el-text-color-secondary); text-align: center; }
.content-grid { display: grid; grid-template-columns: minmax(0, 1.7fr) minmax(300px, 0.8fr); gap: 14px; margin-top: 14px; }
.distribution-column { display: grid; gap: 14px; }
.panel { padding: 18px; background: var(--el-bg-color); border: 1px solid var(--el-border-color-lighter); border-radius: 10px; }
.panel-heading { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
.panel-heading h2 { margin: 0 0 4px; font-size: 16px; }
.drama-cell { display: flex; gap: 10px; align-items: center; }
.drama-cell .cover { width: 42px; height: 58px; background: var(--el-fill-color-light); border-radius: 5px; }
.cover-fallback { display: grid; place-items: center; height: 100%; font-size: 10px; color: var(--el-text-color-placeholder); }

.drama-cell strong,
.drama-cell span { display: block; }
.drama-cell span { margin-top: 4px; font-size: 12px; color: var(--el-text-color-secondary); }
.status-list { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
.status-item { padding: 14px 8px; text-align: center; background: var(--el-fill-color-light); border-radius: 8px; }

.status-item span,
.status-item strong { display: block; }
.status-item strong { margin-top: 6px; font-size: 22px; font-variant-numeric: tabular-nums; }
.category-panel { min-height: 235px; }
.category-list { display: grid; gap: 15px; }
.category-item > div { display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 13px; }

@media (width <= 1100px) {
  .metric-grid { grid-template-columns: repeat(2, 1fr); }
  .content-grid { grid-template-columns: 1fr; }
}

@media (width <= 640px) {
  .shortdrama-dashboard { padding: 12px; }
  .metric-grid { grid-template-columns: 1fr; }
  .page-heading { gap: 12px; }
}
</style>
