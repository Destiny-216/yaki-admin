import { createRouter, createWebHistory } from 'vue-router';

const routes = [
  { path: '/login', component: () => import('../views/Login.vue') },
  {
    path: '/',
    component: () => import('../layout/MainLayout.vue'),
    redirect: '/dashboard',
    children: [
      { path: 'dashboard', name: 'Dashboard', component: () => import('../views/Dashboard.vue') },
      { path: 'menu', name: 'MenuManager', component: () => import('../views/MenuManager.vue') },
      { path: 'orders', name: 'OrderList', component: () => import('../views/OrderList.vue') },
      { path: 'booking', name: 'Booking', component: () => import('../views/Booking.vue') },
      { path: 'users', name: 'UserList', component: () => import('../views/UserList.vue') },
      { path: 'shop-settings',name: 'ShopSettings',component: () => import('../views/ShopSettings.vue') },
      {path: '/booking',name: '予約管理',component: () => import('../views/Booking.vue')
      }
   
    ]
  }

];

export const router = createRouter({
  history: createWebHistory(),
  routes
});