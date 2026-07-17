import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'

// Подключаем наши стили Tailwind и Vue Flow
import './assets/main.css'

const app = createApp(App)

app.use(createPinia())
//app.use(router)

app.mount('#app')