<template>
	<div class="login-container">
	  <el-card class="login-card">
		<h2>🔥炎の味 焼肉店</h2>
		<el-form :model="loginForm">
		  <el-form-item>
			<el-input v-model="loginForm.username" placeholder="ユーザー名" />
		  </el-form-item>
		  <el-form-item>
			<el-input v-model="loginForm.password" type="password" placeholder="パスワード" />
		  </el-form-item>
		  <el-button type="primary" @click="handleLogin" style="width: 100%">ログイン</el-button>
		</el-form>
	  </el-card>
	</div>
  </template>
  
  <script setup>
  import { reactive } from 'vue'
  import { useRouter } from 'vue-router'
  import { ElMessage } from 'element-plus'
  
  const router = useRouter()
  const loginForm = reactive({ username: '', password: '' })
  
  const handleLogin = () => {
	if (loginForm.username === 'admin' && loginForm.password === '123456') {
	  ElMessage.success('ログインしました。お帰りなさい！')
	  router.push('/')
	} else {
	  ElMessage.error('admin / 123456 を入力してください')
	}
  }
  </script>
  
  <style scoped>
  .login-container {
	height: 100vh;
	display: flex;
	justify-content: center;
	align-items: center;
	background-image: url('https://images.unsplash.com/photo-1544025162-d76694265947?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80');
	background-size: cover;
	background-position: center;
  }
  .login-card {
	width: 400px;
	background: rgba(255, 255, 255, 0.9); 
	backdrop-filter: blur(10px); 
	border-radius: 15px;
  }
  </style>