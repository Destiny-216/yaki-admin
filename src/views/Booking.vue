<template>
	<div class="page">
	  <div class="page-head">
		<!-- ページヘッダーエリア -->
		<div>
		  <h2 class="title">予約管理</h2>
		  <p class="sub">予約一覧の確認・ステータス更新・削除ができます</p>
		</div>
  
		<!-- 検索・フィルタリング用ツールバー -->
		<div class="toolbar">
		  <el-select v-model="filterStatus" placeholder="ステータス" clearable style="width: 160px">
			<el-option label="pending（未確認）" value="pending" />
			<el-option label="confirmed（確定）" value="confirmed" />
			<el-option label="completed（完了）" value="completed" />
			<el-option label="cancelled（キャンセル）" value="cancelled" />
		  </el-select>
  
		  <el-input
			v-model="keyword"
			placeholder="氏名/電話/Email 検索"
			clearable
			style="width: 260px"
		  />
  
		  <el-button @click="fetchReservations" :loading="loading">
			再読み込み
		  </el-button>
		</div>
	  </div>
  
	  <!-- エラーメッセージ表示エリア -->
	  <el-alert
		v-if="errorMsg"
		type="error"
		:closable="true"
		show-icon
		:title="errorMsg"
		style="margin-bottom: 12px"
		@close="errorMsg=''"
	  />
  
	  <!-- 予約データ表示用テーブル -->
	  <el-table
		:data="filteredRows"
		border
		stripe
		v-loading="loading"
		style="width: 100%"
	  >
		<el-table-column prop="id" label="ID" width="70" />
		<el-table-column prop="customer_name" label="氏名" width="140" />
		<el-table-column prop="phone" label="電話" width="150" />
		<el-table-column prop="email" label="Email" min-width="200" />
		<el-table-column prop="reservation_date" label="日付" width="120" />
		<el-table-column prop="reservation_time" label="時間" width="110" />
		<el-table-column prop="people" label="人数" width="80" />
		<el-table-column prop="note" label="備考" min-width="180" />
		<!-- ステータス変更用ドロップダウン -->
		<el-table-column label="ステータス" width="220">
		  <template #default="{ row }">
			<div class="status-cell">
			  <el-tag :type="statusTagType(row.status)" effect="plain">
				{{ row.status }}
			  </el-tag>
  
			  <el-select v-model="row._nextStatus" size="small" style="width: 120px">
				<el-option label="pending" value="pending" />
				<el-option label="confirmed" value="confirmed" />
				<el-option label="completed" value="completed" />
				<el-option label="cancelled" value="cancelled" />
			  </el-select>
			</div>
		  </template>
		</el-table-column>
  
		<!-- 各行の操作ボタンエリア -->
		<el-table-column label="操作" width="160">
		  <template #default="{ row }">
			<el-button
			  type="primary"
			  size="small"
			  :disabled="row._nextStatus === row.status"
			  :loading="row._saving === true"
			  @click="saveStatus(row)"
			>
			  保存
			</el-button>
  
			<el-button
			  type="danger"
			  size="small"
			  :loading="row._deleting === true"
			  @click="deleteRow(row)"
			>
			  削除
			</el-button>
		  </template>
		</el-table-column>
	  </el-table>
  
	  <div class="footer">
		表示件数：{{ filteredRows.length }}（全 {{ rows.length }}）
	  </div>
	</div>
  </template>
  
  <script setup>
  import { ref, computed, onMounted } from "vue";
  import { ElMessageBox, ElMessage } from "element-plus";
  
  // APIエンドポイント設定
  const API_BASE = "http://localhost:8000/api/reservations.php";
  
  // 状態管理変数（データリスト、ローディング状態、エラーメッセージ）
  const rows = ref([]);
  const loading = ref(false);
  const errorMsg = ref("");
  
  // フィルタリング用変数
  const filterStatus = ref("");
  const keyword = ref("");
  
  onMounted(() => {
	fetchReservations();
  });
  
  // 予約一覧データ取得API呼び出し
  async function fetchReservations() {
	loading.value = true;
	errorMsg.value = "";
	try {
	  const res = await fetch(API_BASE);
	  const json = await res.json();
  
	  if (!json.ok) throw new Error(json.message || "取得失敗");
  
	  // フロントエンド制御用フィールド（_nextStatus等）を追加して初期化
	  rows.value = (json.data || []).map((r) => ({
		...r,
		_nextStatus: r.status,
		_saving: false,
		_deleting: false,
	  }));
	} catch (e) {
	  console.error(e);
	  errorMsg.value = "予約一覧の取得に失敗しました";
	} finally {
	  loading.value = false;
	}
  }
  
  // 検索キーワードとステータスによるフィルタリング機能
  const filteredRows = computed(() => {
	const ks = keyword.value.trim().toLowerCase();
	return rows.value.filter((r) => {
	  const okStatus = filterStatus.value ? r.status === filterStatus.value : true;
	  if (!okStatus) return false;
  
	  if (!ks) return true;
	  const hay = `${r.customer_name ?? ""} ${r.phone ?? ""} ${r.email ?? ""}`.toLowerCase();
	  return hay.includes(ks);
	});
  });
  
  // ステータスに応じたタグの色分けヘルパー
  function statusTagType(status) {
	switch (status) {
	  case "confirmed":
		return "success";
	  case "pending":
		return "warning";
	  case "cancelled":
		return "danger";
	  case "completed":
		return "info";
	  default:
		return "";
	}
  }
  
  // ステータス更新API呼び出し
  async function saveStatus(row) {
	const id = row.id;
	const next = row._nextStatus;
  
	if (!id) return;
	if (next === row.status) return;
  
	row._saving = true;
	try {
	  const res = await fetch(`${API_BASE}?action=updateStatus`, {
		method: "POST",
		headers: { "Content-Type": "application/json" },
		body: JSON.stringify({ id, status: next }),
	  });
	  const json = await res.json();
	  if (!json.ok) throw new Error(json.message || "更新失敗");
  
	  ElMessage.success("ステータスを更新しました");
	  row.status = next;
  
	  await fetchReservations();
	} catch (e) {
	  console.error(e);
	  ElMessage.error("更新に失敗しました");
	  row._nextStatus = row.status;
	} finally {
	  row._saving = false;
	}
  }
  
  // 予約削除API呼び出し（確認ダイアログ付き）
  async function deleteRow(row) {
	const id = row.id;
	if (!id) return;
  
	try {
	  await ElMessageBox.confirm(
		`ID=${id} の予約を削除しますか？`,
		"確認",
		{ type: "warning", confirmButtonText: "削除", cancelButtonText: "キャンセル" }
	  );
	} catch {
	  return;
	}
  
	row._deleting = true;
	try {
	  const res = await fetch(`${API_BASE}?action=delete`, {
		method: "POST",
		headers: { "Content-Type": "application/json" },
		body: JSON.stringify({ id }),
	  });
	  const json = await res.json();
	  if (!json.ok) throw new Error(json.message || "削除失敗");
  
	  ElMessage.success("削除しました");
	  await fetchReservations();
	} catch (e) {
	  console.error(e);
	  ElMessage.error("削除に失敗しました");
	} finally {
	  row._deleting = false;
	}
  }
  </script>
  
  <style scoped>
  .page {
	padding: 16px;
  }
  .page-head {
	display: flex;
	align-items: flex-start;
	justify-content: space-between;
	gap: 12px;
	margin-bottom: 12px;
  }
  .title {
	margin: 0;
	font-size: 22px;
  }
  .sub {
	margin: 6px 0 0;
	color: #666;
	font-size: 13px;
  }
  .toolbar {
	display: flex;
	gap: 10px;
	align-items: center;
  }
  .status-cell {
	display: flex;
	gap: 10px;
	align-items: center;
  }
  .footer {
	margin-top: 10px;
	color: #666;
	font-size: 13px;
  }
  </style>
  