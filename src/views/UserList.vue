<template>
	<div class="user-container">
	  <el-card>
		<template #header>
		  <!-- ヘッダー：タイトルと追加ボタン -->
		  <div class="card-header">
			<span>スタッフアカウント管理</span>
			<el-button type="primary" @click="handleAdd">スタッフ追加</el-button>
		  </div>
		</template>
  
		<!-- スタッフ一覧テーブル -->
		<el-table :data="userList" stripe style="width: 100%">
		  <el-table-column prop="username" label="ユーザー名" />
		  <el-table-column prop="role" label="役割">
			<template #default="scope">
			  <el-tag :type="scope.row.role === 'admin' ? 'danger' : 'success'">
				{{ scope.row.role === 'admin' ? '管理者' : '一般スタッフ' }}
			  </el-tag>
			</template>
		  </el-table-column>
		  <el-table-column prop="lastLogin" label="最終ログイン日時" />
		  <el-table-column label="操作" width="200">
			<template #default="scope">
			  <el-button size="small" link type="primary">権限変更</el-button>
			  <el-button size="small" link type="danger" @click="handleDelete(scope.$index)">削除</el-button>
			</template>
		  </el-table-column>
		</el-table>
	  </el-card>
  
	  <!-- スタッフ追加モーダル -->
	  <el-dialog v-model="dialogVisible" title="スタッフアカウント追加" width="400px">
		<el-form :model="userForm" label-width="80px">
		  <el-form-item label="ユーザー名">
			<el-input v-model="userForm.username" />
		  </el-form-item>
		  <el-form-item label="初期パスワード">
			<el-input v-model="userForm.password" type="password" />
		  </el-form-item>
		  <el-form-item label="役割">
			<el-select v-model="userForm.role" placeholder="役割を選択してください">
			  <el-option label="管理者" value="admin" />
			  <el-option label="一般スタッフ" value="staff" />
			</el-select>
		  </el-form-item>
		</el-form>
		<template #footer>
		  <el-button @click="dialogVisible = false">キャンセル</el-button>
		  <el-button type="primary" @click="confirmAdd">確定</el-button>
		</template>
	  </el-dialog>
	</div>
  </template>
  
  <script setup>
  import { ref, reactive } from 'vue'
  import { ElMessage } from 'element-plus'
  
  // モックデータ（デモ用）
  const dialogVisible = ref(false)
  const userList = ref([
	{ username: 'admin', role: 'admin', lastLogin: '2026-01-12 10:00' },
	{ username: 'staff_01', role: 'staff', lastLogin: '2026-01-11 18:20' }
  ])
  
  const userForm = reactive({ username: '', password: '', role: 'staff' })
  
  const handleAdd = () => { dialogVisible.value = true }
  
  // スタッフ追加処理（フロントエンドのみの模擬処理）
  const confirmAdd = () => {
	userList.value.push({
	  username: userForm.username,
	  role: userForm.role,
	  lastLogin: '-'
	})
	dialogVisible.value = false
	ElMessage.success('スタッフアカウントを追加しました')
  }
  </script>
  
  <style scoped>
  .card-header { display: flex; justify-content: space-between; align-items: center; }
  </style>