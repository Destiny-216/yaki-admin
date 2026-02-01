<template>
	<div class="dashboard-page">
	  <!-- 顶部工具栏 -->
	  <!-- 期間選択・更新ツールバー -->
	  <div class="toolbar">
		<div class="left">
		  <el-select v-model="rangeDays" style="width: 180px" @change="refresh">
			<el-option label="過去 7 日間" :value="7" />
			<el-option label="過去 14 日間" :value="14" />
			<el-option label="過去 30 日間" :value="30" />
		  </el-select>
  
		  <el-button style="margin-left: 12px" @click="refresh" :loading="loading">
			更新
		  </el-button>
		</div>
  
		<div class="right">
		  <el-tag type="info">データソース：orders / reservations / menu_items</el-tag>
		</div>
	  </div>
  
	  <!-- 统计卡片 -->
	  <!-- KPI表示カードエリア -->
	  <el-row :gutter="16" style="margin-top: 16px">
		<el-col :span="6">
		  <div class="stat-card">
			<div class="stat-head">
			  <div class="title">本日の売上</div>
			  <el-tag type="success" effect="light">リアルタイム</el-tag>
			</div>
			<div class="stat-value">{{ formatYen(stats.todayRevenue) }}</div>
			<div class="stat-sub">
			  完了/支払済注文の合計（paid + done）
			</div>
		  </div>
		</el-col>
  
		<el-col :span="6">
		  <div class="stat-card">
			<div class="stat-head">
			  <div class="title">新規予約</div>
			  <el-tag type="success" effect="light">正常</el-tag>
			</div>
			<div class="stat-value">{{ stats.todayReservations }} 件</div>
			<div class="stat-sub">created_at で集計（本日）</div>
		  </div>
		</el-col>
  
		<el-col :span="6">
		  <div class="stat-card">
			<div class="stat-head">
			  <div class="title">未処理の注文</div>
			  <el-tag type="warning" effect="light">要対応</el-tag>
			</div>
			<div class="stat-value">{{ stats.pendingOrders }} 件</div>
			<div class="stat-sub">status = pending</div>
		  </div>
		</el-col>
  
		<el-col :span="6">
		  <div class="stat-card">
			<div class="stat-head">
			  <div class="title">在庫アラート</div>
			  <el-tag type="info" effect="light">参考</el-tag>
			</div>
			<div class="stat-value">{{ stats.soldOutItems }} 件</div>
			<div class="stat-sub">メニュー状態 sold_out 数（デモ用）</div>
		  </div>
		</el-col>
	  </el-row>
  
	  <!-- 趋势 -->
	  <!-- 売上・注文推移グラフエリア -->
	  <div class="panel" style="margin-top: 16px">
		<div class="panel-head">
		  <div class="panel-title">過去 {{ rangeDays }} 日間の推移</div>
		  <div class="panel-sub">注文数 / 売上 / 予約数（日別）</div>
		</div>
  
		<div class="panel-body">
		  <!-- ECharts 有就画图，没有就降级显示表格 -->
		  <div v-if="chartSupported" ref="chartRef" class="chart-box"></div>
  
		  <div v-else class="fallback">
			<el-alert
			  type="info"
			  show-icon
			  :closable="false"
			  title="echarts が検出されなかったため、表形式で推移を表示しています（デモには影響しません）。折れ線グラフを表示したい場合：npm i echarts"
			/>
			<el-table :data="trendTable" border style="width: 100%; margin-top: 12px">
			  <el-table-column prop="date" label="日付" width="140" />
			  <el-table-column prop="orders" label="注文数" width="120" />
			  <el-table-column prop="revenue" label="売上" width="160">
				<template #default="{ row }">{{ formatYen(row.revenue) }}</template>
			  </el-table-column>
			  <el-table-column prop="reservations" label="予約数" width="120" />
			</el-table>
		  </div>
		</div>
	  </div>
  
	  <!-- 小提示 -->
	  <el-alert
		style="margin-top: 16px"
		type="success"
		show-icon
		:closable="false"
		title="このダッシュボードは「デモ用ビジネス看板」です：実際の支払いがなくても完全なビジネスフローを表示できます。"
	  />
	</div>
  </template>
  
  <script setup>
  import { ref, reactive, onMounted, nextTick, computed, watch } from "vue";
  import { ElMessage } from "element-plus";
  
  // APIエンドポイント定義
  const API_BASE = "http://localhost:8000/api";
  const API = {
	orders: `${API_BASE}/orders.php`,
	reservations: `${API_BASE}/reservations.php`,
	menuItems: `${API_BASE}/menu_items.php`,
  };
  
  // 状態管理変数
  const loading = ref(false);
  const rangeDays = ref(7);
  
  // 統計データ格納用オブジェクト
  const stats = reactive({
	todayRevenue: 0,
	todayReservations: 0,
	pendingOrders: 0,
	soldOutItems: 0,
  });
  
  const orders = ref([]);
  const reservations = ref([]);
  const menuItems = ref([]);
  
  // 日付解析ヘルパー関数
  function safeParseDate(s) {
	// "2026-02-01 16:18:08" -> Date
	if (!s) return null;
	const d = new Date(String(s).replace(" ", "T"));
	return isNaN(d.getTime()) ? null : d;
  }
  
  // 金額フォーマット関数
  function formatYen(n) {
	const num = Number(n || 0);
	return "¥" + num.toLocaleString("ja-JP");
  }
  
  // 日付フォーマット (YYYY-MM-DD)
  function ymd(d) {
	const yy = d.getFullYear();
	const mm = String(d.getMonth() + 1).padStart(2, "0");
	const dd = String(d.getDate()).padStart(2, "0");
	return `${yy}-${mm}-${dd}`;
  }
  
  // 同一日判定ヘルパー
  function isSameDay(a, b) {
	return (
	  a.getFullYear() === b.getFullYear() &&
	  a.getMonth() === b.getMonth() &&
	  a.getDate() === b.getDate()
	);
  }
  
  // 過去N日間の日付ラベル生成
  function getLastNDaysLabels(n) {
	const arr = [];
	const today = new Date();
	for (let i = n - 1; i >= 0; i--) {
	  const d = new Date(today);
	  d.setDate(today.getDate() - i);
	  arr.push(ymd(d));
	}
	return arr;
  }
  
  // 汎用データ取得関数
  async function fetchJson(url) {
	const res = await fetch(url);
	const json = await res.json();
	if (!json?.ok) throw new Error(json?.error || "api error");
	return json.data ?? [];
  }
  
  // 全データの並列取得処理
  async function loadAll() {
	loading.value = true;
	try {
	  const tasks = [
		fetchJson(API.orders).catch(() => []),
		fetchJson(API.reservations).catch(() => []),
		fetchJson(API.menuItems).catch(() => []),
	  ];
  
	  const [o, r, m] = await Promise.all(tasks);
  
	  orders.value = Array.isArray(o) ? o : [];
	  reservations.value = Array.isArray(r) ? r : [];
	  menuItems.value = Array.isArray(m) ? m : [];
  
	  calcStats();
	  await nextTick();
	  await renderChartIfPossible();
	} catch (e) {
	  console.error(e);
	  ElMessage.error("ダッシュボードの読み込みに失敗しました：" + e.message);
	} finally {
	  loading.value = false;
	}
  }
  
  // KPI（重要業績評価指標）の計算ロジック
  function calcStats() {
	const today = new Date();
  
	let revenue = 0;
	let pending = 0;
  
	for (const o of orders.value) {
	  const dt = safeParseDate(o.created_at);
	  if (!dt) continue;
  
	  if (o.status === "pending") pending++;
  
	  if (isSameDay(dt, today) && (o.status === "paid" || o.status === "done")) {
		revenue += Number(o.total || 0);
	  }
	}
  
	let todayResv = 0;
	for (const r of reservations.value) {
	  const dt = safeParseDate(r.created_at || r.date || r.createdAt);
	  if (!dt) continue;
	  if (isSameDay(dt, today)) todayResv++;
	}
  
	let soldOut = 0;
	for (const it of menuItems.value) {
	  if (it.status === "sold_out") soldOut++;
	}
  
	stats.todayRevenue = revenue;
	stats.pendingOrders = pending;
	stats.todayReservations = todayResv;
	stats.soldOutItems = soldOut;
  }
  
  /** ------- 推移データ（グラフ/表用） ------- */
  // グラフ表示用のデータ整形
  const trendLabels = computed(() => getLastNDaysLabels(rangeDays.value));
  
  const trendSeries = computed(() => {
	const labels = trendLabels.value;
  
	const map = {};
	for (const d of labels) {
	  map[d] = { orders: 0, revenue: 0, reservations: 0 };
	}
  
	for (const o of orders.value) {
	  const dt = safeParseDate(o.created_at);
	  if (!dt) continue;
	  const key = ymd(dt);
	  if (!map[key]) continue;
  
	  map[key].orders += 1;
	  if (o.status === "paid" || o.status === "done") {
		map[key].revenue += Number(o.total || 0);
	  }
	}
  
	for (const r of reservations.value) {
	  const dt = safeParseDate(r.created_at || r.date || r.createdAt);
	  if (!dt) continue;
	  const key = ymd(dt);
	  if (!map[key]) continue;
	  map[key].reservations += 1;
	}
  
	return {
	  labels,
	  orders: labels.map((d) => map[d].orders),
	  revenue: labels.map((d) => map[d].revenue),
	  reservations: labels.map((d) => map[d].reservations),
	};
  });
  
  const trendTable = computed(() => {
	const s = trendSeries.value;
	return s.labels.map((date, idx) => ({
	  date,
	  orders: s.orders[idx],
	  revenue: s.revenue[idx],
	  reservations: s.reservations[idx],
	}));
  });
  
  /** ------- グラフ：echarts（オプション） ------- */
  // EChartsライブラリを使用したグラフ描画処理
  const chartRef = ref(null);
  let chartInstance = null;
  const chartSupported = ref(false);
  
  async function renderChartIfPossible() {
	let echarts;
	try {
	  echarts = await import("echarts");
	} catch {
	  chartSupported.value = false;
	  return;
	}
  
	chartSupported.value = true;
  
	await nextTick();
	if (!chartRef.value) return;
  
	if (!chartInstance) {
	  chartInstance = echarts.init(chartRef.value);
	  window.addEventListener("resize", () => chartInstance?.resize());
	}
  
	const s = trendSeries.value;
  
	chartInstance.setOption({
	  tooltip: { trigger: "axis" },
	  legend: { data: ["売上", "注文数", "予約数"] },
	  grid: { left: 40, right: 30, top: 40, bottom: 80 },
	  xAxis: { type: "category", data: s.labels },
	  yAxis: [{ type: "value" }, { type: "value" }],
	  series: [
		{
		  name: "売上",
		  type: "line",
		  yAxisIndex: 0,
		  data: s.revenue,
		  smooth: true,
		},
		{
		  name: "注文数",
		  type: "line",
		  yAxisIndex: 1,
		  data: s.orders,
		  smooth: true,
		},
		{
		  name: "予約数",
		  type: "line",
		  yAxisIndex: 1,
		  data: s.reservations,
		  smooth: true,
		},
	  ],
	});
  }
  
  function refresh() {
	loadAll();
  }
  
  watch(rangeDays, async () => {
	await loadAll();
  });
  
  onMounted(() => {
	loadAll();
  });
  </script>
  
  <style scoped>
  .dashboard-page {
	padding: 16px;
  }
  
  .toolbar {
	display: flex;
	justify-content: space-between;
	align-items: center;
  }
  
  .stat-card {
	background: #fff;
	border: 1px solid #ebeef5;
	border-radius: 10px;
	padding: 16px;
	min-height: 110px;
  }
  
  .stat-head {
	display: flex;
	justify-content: space-between;
	align-items: center;
  }
  
  .stat-head .title {
	font-weight: 600;
	color: #333;
  }
  
  .stat-value {
	margin-top: 14px;
	font-size: 26px;
	font-weight: 700;
	color: #111;
  }
  
  .stat-sub {
	margin-top: 8px;
	font-size: 12px;
	color: #888;
  }
  
  .panel {
	background: #fff;
	border: 1px solid #ebeef5;
	border-radius: 10px;
	overflow: hidden;
  }
  
  .panel-head {
	padding: 14px 16px;
	border-bottom: 1px solid #ebeef5;
  }
  
  .panel-title {
	font-weight: 700;
	color: #222;
  }
  
  .panel-sub {
	margin-top: 4px;
	font-size: 12px;
	color: #888;
  }
  
  .panel-body {
	padding: 16px;
  }
  
  .chart-box {
	width: 100%;
	height: 360px;
  }
  
  .fallback {
	width: 100%;
  }
  </style>
  