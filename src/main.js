import { createApp } from 'vue'
import { createPinia } from 'pinia'
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'
import App from './App.vue'
import { router } from './router' 
// 在 main.js 中加入这一段，注册所有图标
import * as ElementPlusIconsVue from '@element-plus/icons-vue'

const app = createApp(App)
// ... 剩下的 app.use(router) 等代码
for (const [key, component] of Object.entries(ElementPlusIconsVue)) {
	app.component(key, component)
  }
app.use(createPinia())
app.use(router)
app.use(ElementPlus)
app.mount('#app')