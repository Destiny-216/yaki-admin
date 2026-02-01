<template>
	<el-container class="layout-container">
	  <el-aside :width="isCollapse ? '64px' : '220px'" class="aside-menu">
		<div class="logo-wrapper">
		  <span v-if="!isCollapse">🔥 炎の味 管理画面</span>
		  <span v-else>🔥</span>
		</div>
		
		<el-menu
		  :collapse="isCollapse"
		  :default-active="$route.path"
		  background-color="#304156"
		  text-color="#bfcbd9"
		  active-text-color="#409EFF"
		  class="el-menu-vertical"
		  @select="handleMenuSelect"
		>
		  <el-menu-item index="/dashboard">
			<el-icon><Monitor /></el-icon>
			<span>ダッシュボード</span>
		  </el-menu-item>
		  <el-menu-item index="/menu">
			<el-icon><KnifeFork /></el-icon>
			<span>メニュー管理</span>
		  </el-menu-item>
		  <el-menu-item index="/orders">
			<el-icon><List /></el-icon>
			<span>注文管理</span>
		  </el-menu-item>
		  <el-menu-item index="/booking">
			<el-icon><Calendar /></el-icon>
			<span>予約管理</span>
		  </el-menu-item>
		  <el-menu-item index="/users">
			<el-icon><User /></el-icon>
			<span>ユーザー管理</span>
		  </el-menu-item>
		  <el-menu-item index="/shop-settings">
			<el-icon><Setting /></el-icon>
			<span>営業情報管理</span>
		  </el-menu-item>
		</el-menu>
	  </el-aside>
  
	  <el-container>
		<el-header class="main-header">
		  <div class="header-left">
			<el-icon class="fold-btn" @click="isCollapse = !isCollapse">
			  <Expand v-if="isCollapse" />
			  <Fold v-else />
			</el-icon>
			<el-breadcrumb separator="/">
			  <el-breadcrumb-item :to="{ path: '/' }">ホーム</el-breadcrumb-item>
			  <el-breadcrumb-item>{{ $route.name || '現在のページ' }}</el-breadcrumb-item>
			</el-breadcrumb>
		  </div>
		  
		  <div class="header-right">
			<el-dropdown>
			  <span class="user-info">
				<el-avatar :size="32" src="https://cube.elemecdn.com/0/88/03b0d39583f48206768a7534e55bcpng.png" />
				<span class="username">店長 - 阿熊</span>
			  </span>
			  <template #dropdown>
				<el-dropdown-menu>
				  <el-dropdown-item>マイページ</el-dropdown-item>
				  <el-dropdown-item divided @click="handleLogout">ログアウト</el-dropdown-item>
				</el-dropdown-menu>
			  </template>
			</el-dropdown>
		  </div>
		</el-header>
   
		<el-main class="main-content">
		  <router-view />
		</el-main>
	  </el-container>
	</el-container>
  </template>
  
  <script setup>
  import { ref } from 'vue'
  import { useRouter, useRoute } from 'vue-router'
  import { 
	Monitor, KnifeFork, List, Calendar, Expand, Fold, User,Setting
  } from '@element-plus/icons-vue'
  
  const isCollapse = ref(false)
  const router = useRouter()
  const route = useRoute()
  
  const handleMenuSelect = (index) => {
	if (route.path !== index) {
	  router.push(index).catch(err => {
		if (err.name !== 'NavigationDuplicated') {
		  console.error('路由导航错误:', err)
		}
	  })
	}
  }
  
  const handleLogout = () => {
	localStorage.removeItem('token')
	router.push('/login')
  }
  </script>
  
  <style scoped>
  .layout-container { height: 100vh; }
  .aside-menu {
	background-color: #304156;
	transition: width 0.3s;
	overflow: hidden;
  }
  .el-menu-vertical { border-right: none; }
  
  .logo-wrapper {
	height: 60px;
	display: flex;
	align-items: center;
	justify-content: center;
	color: white;
	font-weight: bold;
	background: #2b2f3a;
	white-space: nowrap;
  }
  
  .main-header {
	background: #fff;
	border-bottom: 1px solid #e6e6e6;
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 0 20px;
  }
  
  .header-left { display: flex; align-items: center; gap: 20px; }
  .fold-btn { font-size: 20px; cursor: pointer; color: #666; }
  .header-right { cursor: pointer; }
  .user-info { display: flex; align-items: center; gap: 10px; }
  .main-content { background-color: #f0f2f5; padding: 20px; }
  </style>