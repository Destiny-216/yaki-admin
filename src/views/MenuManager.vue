<template>
	<div class="menu-page">
	  <!-- 検索・フィルタリングツールバー -->
	  <div class="toolbar">
		<div class="left">
		  <el-input
			v-model="filters.keyword"
			placeholder="検索：商品名"
			clearable
			style="width: 240px"
			@keyup.enter="loadItems"
		  />
		  <el-select
			v-model="filters.category_id"
			placeholder="カテゴリ"
			clearable
			style="width: 200px; margin-left: 12px"
			@change="loadItems"
		  >
			<el-option
			  v-for="c in categories"
			  :key="c.id"
			  :label="c.name"
			  :value="c.id"
			/>
		  </el-select>
  
		  <el-select
			v-model="filters.status"
			placeholder="ステータス"
			clearable
			style="width: 160px; margin-left: 12px"
			@change="loadItems"
		  >
			<el-option label="selling（販売中）" value="selling" />
			<el-option label="sold_out（売切）" value="sold_out" />
			<el-option label="hidden（非表示）" value="hidden" />
		  </el-select>
  
		  <el-button style="margin-left: 12px" @click="resetFilters">リセット</el-button>
		</div>
  
		<div class="right">
		  <el-button type="primary" @click="openCreate">商品追加</el-button>
		</div>
	  </div>
  
	  <!-- メニュー商品一覧テーブル -->
	  <el-table
		:data="items"
		border
		style="width: 100%; margin-top: 16px"
		v-loading="loading"
	  >
	  <el-table-column label="画像" width="120">
		<template #default="scope">
			<el-image
			:src="resolveImg(scope.row.image_url)"
			style="width: 80px; height: 50px; border-radius: 6px;"
			fit="cover"
			>
			<template #error>
				<div class="no-img">NO IMG</div>
			</template>
			</el-image>
		</template>
	</el-table-column>

  
		<el-table-column prop="name" label="商品名" min-width="180" />
		<el-table-column prop="category_name" label="カテゴリ" width="160" />
		<el-table-column label="価格" width="120">
		  <template #default="{ row }">
			¥{{ Number(row.price || 0).toLocaleString("ja-JP") }}
		  </template>
		</el-table-column>
  
		<!-- ステータス変更ドロップダウン -->
		<el-table-column label="ステータス" align="center" width="210">
		  <template #default="{ row }">
			<el-tag v-if="row.status === 'selling'" type="success">selling</el-tag>
			<el-tag v-else-if="row.status === 'sold_out'" type="warning">sold_out</el-tag>
			<el-tag v-else type="info">hidden</el-tag>
  
			<el-dropdown style="margin-left: 8px" @command="(cmd) => changeStatus(row, cmd)">
			  <el-button size="small">
				ステータス変更<i class="el-icon--right"></i>
			  </el-button>
			  <template #dropdown>
				<el-dropdown-menu>
				  <el-dropdown-item command="selling">selling</el-dropdown-item>
				  <el-dropdown-item command="sold_out">sold_out</el-dropdown-item>
				  <el-dropdown-item command="hidden">hidden</el-dropdown-item>
				</el-dropdown-menu>
			  </template>
			</el-dropdown>
		  </template>
		</el-table-column>
  
		<!-- おすすめフラグ切り替えスイッチ -->
		<el-table-column label="おすすめ" width="110">
		  <template #default="{ row }">
			<el-switch
			  :model-value="Number(row.is_recommended) === 1"
			  @change="(val) => toggleRecommended(row, val)"
			/>
		  </template>
		</el-table-column>
  
		<el-table-column prop="sort_order" label="並び順" width="90" />
  
		<el-table-column label="操作" width="200">
		  <template #default="{ row }">
			<el-button size="small" @click="openEdit(row)">編集</el-button>
			<el-button size="small" type="danger" @click="confirmDelete(row)">削除</el-button>
		  </template>
		</el-table-column>
	  </el-table>
  
	  <!-- 弹窗：新增/编辑 -->
	  <!-- 商品追加・編集用モーダル -->
	  <el-dialog
		v-model="dialog.visible"
		:title="dialog.mode === 'create' ? '商品追加' : '商品編集'"
		width="680px"
		@close="resetForm"
	  >
		<el-form :model="form" :rules="rules" ref="formRef" label-width="110px">
		  <el-form-item label="商品名" prop="name">
			<el-input v-model="form.name" placeholder="例：特選和牛ロース" />
		  </el-form-item>
  
		  <el-form-item label="カテゴリ" prop="category_id">
			<el-select v-model="form.category_id" placeholder="カテゴリを選択してください" style="width: 100%">
			  <el-option
				v-for="c in categories"
				:key="c.id"
				:label="c.name"
				:value="c.id"
			  />
			</el-select>
		  </el-form-item>
  
		  <el-form-item label="価格(円)" prop="price">
			<el-input-number v-model="form.price" :min="0" :step="100" style="width: 100%" />
		  </el-form-item>
  
		  <el-form-item label="説明" prop="description">
			<el-input
			  v-model="form.description"
			  type="textarea"
			  :rows="3"
			  placeholder="例：口の中でとろけるような、極上の和牛ロースです。"
			/>
		  </el-form-item>
  
		  <el-form-item label="画像URL" prop="image_url">
			<el-input v-model="form.image_url" placeholder="例：img/kr1.jpg または http://..." />
			<div style="margin-top: 8px;">
			  <el-image
				:src="form.image_url || ''"
				fit="cover"
				style="width: 160px; height: 100px; border-radius: 8px"
			  >
				<template #error>
				  <div style="width:160px;height:100px;display:flex;align-items:center;justify-content:center;background:#f3f3f3;border-radius:8px;">
					<span style="font-size:12px;color:#999;">プレビュー失敗</span>
				  </div>
				</template>
			  </el-image>
			</div>
		  </el-form-item>
  
		  <el-form-item label="ステータス" prop="status">
			<el-select v-model="form.status" style="width: 100%">
			  <el-option label="selling（販売中）" value="selling" />
			  <el-option label="sold_out（売切）" value="sold_out" />
			  <el-option label="hidden（非表示）" value="hidden" />
			</el-select>
		  </el-form-item>
  
		  <el-form-item label="並び順" prop="sort_order">
			<el-input-number v-model="form.sort_order" :min="0" :step="1" style="width: 100%" />
		  </el-form-item>
  
		  <el-form-item label="おすすめ">
			<el-switch v-model="form.is_recommended" />
		  </el-form-item>
		</el-form>
  
		<template #footer>
		  <el-button @click="dialog.visible = false">キャンセル</el-button>
		  <el-button type="primary" :loading="saving" @click="submitForm">
			保存
		  </el-button>
		</template>
	  </el-dialog>
	</div>
  </template>
  
  <script setup>
  import { ref, reactive, onMounted } from "vue";
  import { ElMessage, ElMessageBox } from "element-plus";
  

  const API_BASE = "http://localhost:8000/api";

  const API = {
	categories: `${API_BASE}/menu_categories.php`,
	items: `${API_BASE}/menu_items.php`,
  };

  // 画像URL解決ヘルパー（相対パス対応）
  const resolveImg = (url) => {
  if (!url) return "/img/noimage.jpg";

  if (/^https?:\/\//i.test(url)) return url;

  if (url.startsWith("/")) return url;

  return "/" + url;
};

  
  const loading = ref(false);
  const saving = ref(false);
  
  const categories = ref([]);
  const items = ref([]);
  
  // フィルタリング条件
  const filters = reactive({
	keyword: "",
	category_id: null,
	status: "",
  });
  
  function resetFilters() {
	filters.keyword = "";
	filters.category_id = null;
	filters.status = "";
	loadItems();
  }
  
  // カテゴリ一覧取得API
  async function loadCategories() {
	try {
	  const res = await fetch(API.categories);
	  const json = await res.json();
	  if (!json.ok) throw new Error(json.error || "categories api error");
	  categories.value = json.data || [];
	} catch (e) {
		ElMessage.info("デモ環境ではデータは表示されません")
		data.value = []
		}
  }
  
  // 商品一覧取得API（検索条件付き）
  async function loadItems() {
	loading.value = true;
	try {
	  const qs = new URLSearchParams();
	  if (filters.keyword) qs.set("keyword", filters.keyword);
	  if (filters.category_id) qs.set("category_id", String(filters.category_id));
	  if (filters.status) qs.set("status", filters.status);
  
	  const url = qs.toString() ? `${API.items}?${qs}` : API.items;
	  const res = await fetch(url);
	  const json = await res.json();
	  if (!json.ok) throw new Error(json.error || "items api error");
	  items.value = json.data || [];
	} catch (e) {
	  console.error(e);
	  ElMessage.error("メニューの読み込みに失敗しました：" + e.message);
	} finally {
	  loading.value = false;
	}
  }
  

  const dialog = reactive({
	visible: false, // モーダル表示フラグ
	mode: "create", // モード: create | edit
  });
  
  const formRef = ref(null);
  
  // フォームデータ初期値
  const form = reactive({
	id: null,
	category_id: null,
	name: "",
	description: "",
	price: 0,
	image_url: "",
	status: "selling",
	sort_order: 0,
	is_recommended: false,
  });
  
  // バリデーションルール
  const rules = {
	name: [{ required: true, message: "商品名を入力してください", trigger: "blur" }],
	category_id: [{ required: true, message: "カテゴリを選択してください", trigger: "change" }],
	price: [{ required: true, message: "価格を入力してください", trigger: "change" }],
	status: [{ required: true, message: "ステータスを選択してください", trigger: "change" }],
  };
  
  function resetForm() {
	form.id = null;
	form.category_id = null;
	form.name = "";
	form.description = "";
	form.price = 0;
	form.image_url = "";
	form.status = "selling";
	form.sort_order = 0;
	form.is_recommended = false;
  
	formRef.value?.clearValidate?.();
  }
  
  // 新規作成モーダルを開く
  function openCreate() {
	dialog.mode = "create";
	resetForm();
	dialog.visible = true;
  }
  
  // 編集モーダルを開く
  function openEdit(row) {
	dialog.mode = "edit";
	resetForm();
  
	form.id = row.id;
	form.category_id = row.category_id;
	form.name = row.name || "";
	form.description = row.description || "";
	form.price = Number(row.price || 0);
	form.image_url = row.image_url || "";
	form.status = row.status || "selling";
	form.sort_order = Number(row.sort_order || 0);
	form.is_recommended = Number(row.is_recommended || 0) === 1;
  
	dialog.visible = true;
  }
  
  // フォーム送信処理（新規作成・更新）
  async function submitForm() {
	try {
	  await formRef.value.validate();
	} catch {
	  return;
	}
  
	saving.value = true;
	try {
	  const payload = {
		id: form.id,
		category_id: form.category_id,
		name: form.name,
		description: form.description || null,
		price: Number(form.price || 0),
		image_url: form.image_url || null,
		status: form.status,
		sort_order: Number(form.sort_order || 0),
		is_recommended: form.is_recommended ? 1 : 0,
	  };
  
	  let res;
	  if (dialog.mode === "create") {
		res = await fetch(API.items, {
		  method: "POST",
		  headers: { "Content-Type": "application/json" },
		  body: JSON.stringify(payload),
		});
	  } else {
		res = await fetch(`${API.items}?id=${form.id}`, {
		  method: "PUT",
		  headers: { "Content-Type": "application/json" },
		  body: JSON.stringify(payload),
		});
	  }
  
	  const json = await res.json();
	  if (!json.ok) throw new Error(json.error || "save failed");
  
	  ElMessage.success("保存しました");
	  dialog.visible = false;
	  await loadItems();
	} catch (e) {
	  console.error(e);
	  ElMessage.error("保存に失敗しました：" + e.message);
	} finally {
	  saving.value = false;
	}
  }
  
  /** ---------- 删除 ---------- **/
  // 商品削除処理（確認ダイアログ付き）
  async function confirmDelete(row) {
	try {
	  await ElMessageBox.confirm(
		`「${row.name}」を削除しますか？`,
		"削除確認",
		{ type: "warning" }
	  );
  
	  const res = await fetch(`${API.items}?id=${row.id}`, { method: "DELETE" });
	  const json = await res.json();
	  if (!json.ok) throw new Error(json.error || "delete failed");
  
	  ElMessage.success("削除しました");
	  await loadItems();
	} catch (e) {
	  if (e?.message) console.error(e);
	}
  }
  
 
  // ステータス更新処理
  async function changeStatus(row, newStatus) {
	if (row.status === newStatus) return;
  
	try {
	  const payload = {id: row.id, status: newStatus };
	  const res = await fetch(`${API.items}?id=${row.id}`, {
		method: "PUT",
		headers: { "Content-Type": "application/json" },
		body: JSON.stringify(payload),
	  });
	  const json = await res.json();
	  if (!json.ok) throw new Error(json.error || "update status failed");
  
	  row.status = newStatus;
	  ElMessage.success("ステータスを更新しました");
	} catch (e) {
	  console.error(e);
	  ElMessage.error("ステータスの更新に失敗しました：" + e.message);
	}
  }
  
  // おすすめフラグ切り替え処理
  async function toggleRecommended(row, val) {
	try {
	  const payload = { is_recommended: val ? 1 : 0 };
	  const res = await fetch(`${API.items}?id=${row.id}`, {
		method: "PUT",
		headers: { "Content-Type": "application/json" },
		body: JSON.stringify(payload),
	  });
	  const json = await res.json();
	  if (!json.ok) throw new Error(json.error || "update recommended failed");
  
	  row.is_recommended = val ? 1 : 0;
	} catch (e) {
	  console.error(e);
	  ElMessage.error("更新に失敗しました：" + e.message);
	}
  }
  
  onMounted(async () => {
	await loadCategories();
	await loadItems();
  });
  </script>
  
  <style scoped>
  .menu-page {
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
  