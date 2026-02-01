<template>
	<div class="order-page">
	  <!-- 検索・フィルタリングツールバー -->
	  <div class="toolbar">
		<div class="left">
		  <el-input
			v-model="filters.q"
			placeholder="検索（卓番・顧客名・電話番号）"
			clearable
			style="width: 260px"
			@keyup.enter="loadOrders"
		  />
		  <el-select
			v-model="filters.status"
			placeholder="ステータス"
			clearable
			style="width: 180px; margin-left: 12px"
			@change="loadOrders"
		  >
			<el-option label="pending（未処理）" value="pending" />
			<el-option label="paid（支払済）" value="paid" />
			<el-option label="done（完了）" value="done" />
			<el-option label="cancelled（キャンセル）" value="cancelled" />
		  </el-select>
  
		  <el-button style="margin-left: 12px" @click="resetFilters">リセット</el-button>
		  <el-button style="margin-left: 8px" @click="loadOrders">更新</el-button>
		</div>
  
		<div class="right">
		  <el-tag type="info">注文数：{{ orders.length }}</el-tag>
		</div>
	  </div>
  
	  <!-- 表格 -->
	  <!-- 注文一覧テーブル -->
	  <el-table
		:data="orders"
		border
		style="width: 100%; margin-top: 16px"
		v-loading="loading"
	  >
		<el-table-column prop="id" align="center" label="注文ID" width="70" />
		<el-table-column prop="table_no" align="center" label="卓番" width="90" />
		<el-table-column prop="customer_name" align="center" label="顧客名" min-width="140" />
		<el-table-column prop="customer_phone"  align="center" label="電話番号" min-width="150" />
		<el-table-column prop="people_count" align="center" label="人数" width="80" />
  
		<el-table-column label="金額" align="center" width="130">
		  <template #default="{ row }">
			{{ yen(row.total) }}
		  </template>
		</el-table-column>
  
		<el-table-column label="注文点数" align="center" width="90">
		  <template #default="{ row }">
			{{ row.items_count ?? "-" }}
		  </template>
		</el-table-column>
  
		<el-table-column prop="created_at" label="注文日時" align="center" min-width="170" />
  
		<!-- ステータス表示・変更カラム -->
		<el-table-column label="ステータス" align="center" width="220">
		  <template #default="{ row }">
			<el-tag :type="statusTagType(row.status)">
			  {{ row.status }}
			</el-tag>
  
			<el-dropdown
			  style="margin-left: 10px"
			  @command="(cmd) => changeStatus(row, cmd)"
			>
			  <el-button size="small">
				ステータス変更
				<i class="el-icon--right"></i>
			  </el-button>
			  <template #dropdown>
				<el-dropdown-menu>
				  <el-dropdown-item command="pending">pending</el-dropdown-item>
				  <el-dropdown-item command="paid">paid</el-dropdown-item>
				  <el-dropdown-item command="done">done</el-dropdown-item>
				  <el-dropdown-item command="cancelled">cancelled</el-dropdown-item>
				</el-dropdown-menu>
			  </template>
			</el-dropdown>
		  </template>
		</el-table-column>
  
		<el-table-column label="操作" align="center" width="140">
		  <template #default="{ row }">
			<el-button size="small" @click="openDetail(row)">詳細</el-button>
		  </template>
		</el-table-column>
	  </el-table>
  
	  <!-- 详情弹窗 -->
	  <!-- 注文詳細モーダル -->
	  <el-dialog
		v-model="detail.visible"
		title="注文詳細"
		width="820px"
		@close="resetDetail"
	  >
		<div v-loading="detail.loading">
		  <el-descriptions
			v-if="detail.order"
			:column="2"
			border
			style="margin-bottom: 14px"
		  >
			<el-descriptions-item label="注文ID">{{ detail.order.id }}</el-descriptions-item>
			<el-descriptions-item label="卓番">{{ detail.order.table_no }}</el-descriptions-item>
  
			<el-descriptions-item label="顧客名">{{ detail.order.customer_name || "-" }}</el-descriptions-item>
			<el-descriptions-item label="電話番号">{{ detail.order.customer_phone || "-" }}</el-descriptions-item>
  
			<el-descriptions-item label="人数">{{ detail.order.people_count }}</el-descriptions-item>
			<el-descriptions-item label="ステータス">
			  <el-tag :type="statusTagType(detail.order.status)">{{ detail.order.status }}</el-tag>
			</el-descriptions-item>
  
			<el-descriptions-item label="小計">{{ yen(detail.order.subtotal) }}</el-descriptions-item>
			<el-descriptions-item label="税">{{ yen(detail.order.tax) }}</el-descriptions-item>
  
			<el-descriptions-item label="合計">{{ yen(detail.order.total) }}</el-descriptions-item>
			<el-descriptions-item label="注文日時">{{ detail.order.created_at }}</el-descriptions-item>
		  </el-descriptions>
  
		  <div style="font-weight: 600; margin: 6px 0 10px;">注文明細</div>
  
		  <el-table
			v-if="detail.items.length"
			:data="detail.items"
			border
			style="width: 100%"
		  >
			<el-table-column prop="item_name" label="商品名" min-width="220" />
			<el-table-column label="単価" width="120">
			  <template #default="{ row }">{{ yen(row.unit_price) }}</template>
			</el-table-column>
			<el-table-column prop="quantity" label="数量" width="90" />
			<el-table-column label="小計" width="120">
			  <template #default="{ row }">{{ yen(row.line_total) }}</template>
			</el-table-column>
			<el-table-column prop="created_at" label="記録日時" min-width="170" />
		  </el-table>
  
		  <el-empty v-else description="明細なし（または商品がありません）" />
		</div>
  
		<template #footer>
		  <el-button @click="detail.visible = false">閉じる</el-button>
		</template>
	  </el-dialog>
	</div>
  </template>
  
  <script setup>
  import { ref, reactive, onMounted } from "vue";
  import { ElMessage } from "element-plus";
  
  // API設定
  const API_BASE = "http://localhost:8000/api";
  const API = {
	orders: `${API_BASE}/orders.php`,
  };
  
  const loading = ref(false);
  const orders = ref([]);
  
  const filters = reactive({
	q: "",
	status: "",
  });
  
  // フィルタ条件のリセット
  function resetFilters() {
	filters.q = "";
	filters.status = "";
	loadOrders();
  }
  
  // 金額表示フォーマット
  function yen(n) {
	const num = Number(n ?? 0);
	return "¥" + num.toLocaleString("ja-JP");
  }
  
  // ステータスに応じたタグ色決定
  function statusTagType(status) {
	switch (status) {
	  case "pending": return "warning";
	  case "paid": return "success";
	  case "done": return "info";
	  case "cancelled": return "danger";
	  default: return "info";
	}
  }
  
  // 注文一覧取得API呼び出し
  async function loadOrders() {
	loading.value = true;
	try {
	  const qs = new URLSearchParams();
	  if (filters.q) qs.set("q", filters.q);
	  if (filters.status) qs.set("status", filters.status);
  
	  const url = qs.toString() ? `${API.orders}?${qs}` : API.orders;
  
	  const res = await fetch(url);
	  const json = await res.json();
  
	  if (!json.ok) throw new Error(json.error || "orders api error");
	  orders.value = Array.isArray(json.data) ? json.data : [];
	} catch (e) {
	  console.error(e);
	  ElMessage.error("注文の読み込みに失敗しました：" + (e?.message || "unknown"));
	} finally {
	  loading.value = false;
	}
  }
  
  /**
   * ステータス変更（デフォルト：PUT /orders.php?id=xx  body: {status:"paid"}）
   * バックエンドが body に id を要求する場合は、payload を {id: row.id, status:newStatus} に変更してください
   */
  // 注文ステータス更新処理
  async function changeStatus(row, newStatus) {
	if (!row?.id) return;
	if (row.status === newStatus) return;
  
	const old = row.status;
	row.status = newStatus;
  
	try {
	  const payload = { status: newStatus };
  
	  const res = await fetch(`${API.orders}?id=${row.id}`, {
		method: "PUT",
		headers: { "Content-Type": "application/json" },
		body: JSON.stringify(payload),
	  });
  
	  const json = await res.json();
	  if (!json.ok) throw new Error(json.error || "update status failed");
  
	  ElMessage.success("ステータスを更新しました");
	} catch (e) {
	  console.error(e);
	  row.status = old;
	  ElMessage.error("ステータスの更新に失敗しました：" + (e?.message || "unknown"));
	}
  }
  
  /** 詳細モーダル */
  // 詳細モーダル用状態管理
  const detail = reactive({
	visible: false,
	loading: false,
	order: null,
	items: [],
  });
  
  function resetDetail() {
	detail.loading = false;
	detail.order = null;
	detail.items = [];
  }
  
  // 詳細データ取得・モーダル表示
  async function openDetail(row) {
	if (!row?.id) return;
	detail.visible = true;
	detail.loading = true;
  
	try {
	  const res = await fetch(`${API.orders}?id=${row.id}`);
	  const json = await res.json();
	  if (!json.ok) throw new Error(json.error || "detail api error");
  
	  detail.order = json.data?.order || null;
	  detail.items = Array.isArray(json.data?.items) ? json.data.items : [];
	} catch (e) {
	  console.error(e);
	  ElMessage.error("詳細の読み込みに失敗しました：" + (e?.message || "unknown"));
	  detail.visible = false;
	} finally {
	  detail.loading = false;
	}
  }
  
  onMounted(() => {
	loadOrders();
  });
  </script>
  
  <style scoped>
  .el-table th {
  padding: 12px 0;
}

  .order-page {
	padding: 16px;
  }
  .toolbar {
	display: flex;
	justify-content: space-between;
	align-items: center;
  }
  .toolbar .left {
	display: flex;
	align-items: center;
  }
  </style>
  