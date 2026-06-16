<script setup lang="ts">
import type { FormInstance, FormRules } from 'element-plus'
import type { DramaVo } from '../../api/drama'
import { create, update } from '../../api/drama'
import { ElMessage } from 'element-plus'

const emit = defineEmits<{ saved: [] }>()
const visible = ref(false)
const saving = ref(false)
const formRef = ref<FormInstance>()
const editing = ref(false)

function emptyForm(): DramaVo {
  return {
    external_drama_id: '', title: '', display_author_name: '', author_user_id: 1,
    total_episodes: 1, cover_url: '', vip_free: 0, status: 0,
    description: '', category: '', tags: '',
  }
}
const model = ref<DramaVo>(emptyForm())
const rules: FormRules = {
  external_drama_id: [{ required: true, message: '请输入外部短剧 ID', trigger: 'blur' }],
  title: [{ required: true, message: '请输入短剧名称', trigger: 'blur' }],
  display_author_name: [{ required: true, message: '请输入展示作者', trigger: 'blur' }],
  author_user_id: [{ required: true, message: '请输入作者用户 ID', trigger: 'blur' }],
  total_episodes: [{ required: true, message: '请输入总集数', trigger: 'blur' }],
  cover_url: [{ required: true, message: '请输入封面地址', trigger: 'blur' }],
  category: [{ required: true, message: '请输入分类', trigger: 'blur' }],
}

function open(row?: DramaVo) {
  editing.value = Boolean(row?.id)
  model.value = row ? { ...row } : emptyForm()
  visible.value = true
  nextTick(() => formRef.value?.clearValidate())
}

async function save() {
  await formRef.value?.validate()
  saving.value = true
  try {
    if (editing.value && model.value.id) {
      await update(model.value.id, model.value)
    }
    else {
      await create(model.value)
    }
    ElMessage.success(editing.value ? '短剧信息已更新' : '短剧已创建')
    visible.value = false
    emit('saved')
  }
  finally {
    saving.value = false
  }
}

defineExpose({ open })
</script>

<template>
  <el-dialog v-model="visible" :title="editing ? '编辑短剧' : '新增短剧'" width="760px" destroy-on-close>
    <el-form ref="formRef" :model="model" :rules="rules" label-width="108px">
      <div class="form-grid">
        <el-form-item label="外部短剧 ID" prop="external_drama_id">
          <el-input v-model="model.external_drama_id" maxlength="24" />
        </el-form-item>
        <el-form-item label="短剧名称" prop="title">
          <el-input v-model="model.title" maxlength="255" />
        </el-form-item>
        <el-form-item label="展示作者" prop="display_author_name">
          <el-input v-model="model.display_author_name" maxlength="100" />
        </el-form-item>
        <el-form-item label="作者用户 ID" prop="author_user_id">
          <el-input-number v-model="model.author_user_id" :min="1" controls-position="right" />
        </el-form-item>
        <el-form-item label="总集数" prop="total_episodes">
          <el-input-number v-model="model.total_episodes" :min="1" controls-position="right" />
        </el-form-item>
        <el-form-item label="分类" prop="category">
          <el-input v-model="model.category" maxlength="50" />
        </el-form-item>
        <el-form-item label="内容状态" prop="status">
          <el-select v-model="model.status">
            <el-option label="下线" :value="0" /><el-option label="连载中" :value="1" /><el-option label="已完结" :value="2" />
          </el-select>
        </el-form-item>
        <el-form-item label="免费观看">
          <el-switch v-model="model.vip_free" :active-value="1" :inactive-value="0" active-text="是" inactive-text="否" />
        </el-form-item>
      </div>
      <el-form-item label="封面地址" prop="cover_url">
        <el-input v-model="model.cover_url" />
      </el-form-item>
      <el-form-item label="标签">
        <el-input v-model="model.tags" placeholder="多个标签可用英文逗号分隔" />
      </el-form-item>
      <el-form-item label="短剧简介">
        <el-input v-model="model.description" type="textarea" :rows="4" maxlength="2000" show-word-limit />
      </el-form-item>
    </el-form>
    <template #footer>
      <el-button @click="visible = false">
        取消
      </el-button><el-button type="primary" :loading="saving" @click="save">
        保存
      </el-button>
    </template>
  </el-dialog>
</template>

<style scoped lang="scss">
/* stylelint-disable declaration-block-single-line-max-declarations */
.form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 18px; }

.form-grid :deep(.el-input-number),
.form-grid :deep(.el-select) { width: 100%; }

@media (width <= 720px) { .form-grid { grid-template-columns: 1fr; } }
</style>
