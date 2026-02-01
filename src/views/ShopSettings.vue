<script setup>
import { onMounted, reactive, ref } from 'vue'
import { Check, Refresh } from '@element-plus/icons-vue'

// UI状態管理用変数
const loading = ref(false)
const saving = ref(false)
const message = ref('')
const error = ref('')

// 店舗設定フォームデータ
const form = reactive({
  shop_name: '',
  phone: '',
  address: '',
  business_hours: '',
  notice: '',
  is_open: 1,
})

// 店舗情報の取得API呼び出し（GET）
async function loadShop() {
  loading.value = true
  error.value = ''
  message.value = ''
  try {
    const res = await fetch('/api/shop.php')
    if (!res.ok) throw new Error(`GET failed: ${res.status}`)
    const data = await res.json()

    form.shop_name = data.shop_name ?? ''
    form.phone = data.phone ?? ''
    form.address = data.address ?? ''
    form.business_hours = data.business_hours ?? ''
    form.notice = data.notice ?? ''
    form.is_open = Number(data.is_open ?? 1)
  } catch (e) {
    error.value = e?.message ?? String(e)
  } finally {
    loading.value = false
  }
}

// 店舗情報の更新API呼び出し（PUT）
async function saveShop() {
  saving.value = true
  error.value = ''
  message.value = ''
  try {
    const payload = {
      shop_name: form.shop_name,
      phone: form.phone,
      address: form.address,
      business_hours: form.business_hours,
      notice: form.notice,
      is_open: form.is_open,
    }

    const res = await fetch('/api/shop.php', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })

    const data = await res.json().catch(() => ({}))
    if (!res.ok) {
      throw new Error(data?.message || data?.error || `PUT failed: ${res.status}`)
    }

    message.value = '保存しました ✅'
    await loadShop()
  } catch (e) {
    error.value = e?.message ?? String(e)
  } finally {
    saving.value = false
  }
}

onMounted(loadShop)
</script>

<template>
  <div class="shop-settings-container">
    <!-- 页面标题 -->
    <!-- ページヘッダー -->
    <div class="page-header">
      <h2 class="page-title">営業情報管理</h2>
      <p class="page-desc">店舗の基本情報と営業状態を管理します</p>
    </div>

    <!-- 加载状态 -->
    <el-card v-loading="loading" class="settings-card">
      <!-- 通知メッセージエリア -->
      <!-- 错误提示 -->
      <el-alert
        v-if="error"
        :title="error"
        type="error"
        :closable="true"
        @close="error = ''"
        style="margin-bottom: 20px;"
      />

      <!-- 成功提示 -->
      <el-alert
        v-if="message"
        :title="message"
        type="success"
        :closable="true"
        @close="message = ''"
        style="margin-bottom: 20px;"
      />

      <!-- 表单内容 -->
      <!-- 設定フォーム -->
      <el-form :model="form" label-width="160px" class="settings-form">
        <!-- 店铺名称 -->
        <el-form-item label="店舗名">
          <el-input
            v-model="form.shop_name"
            placeholder="店舗名"
          />
          <div class="form-tip">※ 現在は読み取り専用モードです（更新が必要な場合はバックエンド機能を拡張してください）</div>
        </el-form-item>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="電話番号">
              <el-input
                v-model="form.phone"
     
                placeholder="電話番号"
              />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="営業状態">
              <el-select
                v-model="form.is_open"
                placeholder="営業状態を選択してください"
                style="width: 100%;"
              >
                <el-option label="営業中" :value="1" />
                <el-option label="休業" :value="0" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>

        <!-- 店铺地址 -->
        <el-form-item label="店舗住所">
          <el-input
            v-model="form.address"
            placeholder="店舗住所"
          />
        </el-form-item>

        <!-- 营业时间 -->
        <el-form-item label="営業時間">
          <el-input
            v-model="form.business_hours"
            type="textarea"
            :rows="3"
            placeholder="営業時間を入力してください"
          />
          <div class="form-tip">例：平日 18:00〜23:00（L.O.22:30） / 土日祝 17:00〜23:00</div>
        </el-form-item>

        <!-- 店铺公告 -->
        <el-form-item label="お知らせ">
          <el-input
            v-model="form.notice"
            type="textarea"
            :rows="3"
            placeholder="店舗のお知らせを入力してください"
          />
        </el-form-item>

        <!-- 操作按钮 -->
        <!-- アクションボタンエリア -->
        <el-form-item>
          <el-button
            type="primary"
            :loading="saving"
            @click="saveShop"
            :disabled="loading"
          >
            <el-icon><Check /></el-icon>
            {{ saving ? '保存中...' : '設定を保存' }}
          </el-button>
          <el-button
            @click="loadShop"
            :disabled="loading || saving"
          >
            <el-icon><Refresh /></el-icon>
            再読み込み
          </el-button>
        </el-form-item>
      </el-form>
    </el-card>
  </div>
</template>

<style scoped>



:deep(.el-textarea__inner),
:deep(.el-input__inner) {
  outline: none !important;
}

.shop-settings-container {
  padding: 0;
  width: 100%;
  height: 100%;
}


.page-header {
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 1px solid #e4e7ed;
}

.page-title {
  font-size: 28px;
  font-weight: 600;
  color: #303133;
  margin: 0 0 8px 0;
}

.page-desc {
  font-size: 14px;
  color: #909399;
  margin: 0;
}


.settings-card {
  border-radius: 0;
  border: none;
  box-shadow: none;
  background: transparent;
}


.settings-card :deep(.el-card__body) {
  padding: 0;
}

.settings-form {
  padding: 0;
  max-width: 1200px;
}


.form-tip {
  font-size: 12px;
  color: #909399;
  margin-top: 6px;
  line-height: 1.5;
}

.el-form-item:last-child {
  margin-top: 24px;
}


@media (max-width: 768px) {
  .page-title {
    font-size: 22px;
  }
  
  .settings-form :deep(.el-form-item__label) {
    width: 120px !important;
  }
  
  .el-row {
    flex-direction: column;
  }
  
  .el-col {
    width: 100% !important;
  }
}

</style>
