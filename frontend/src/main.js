import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'

const app = createApp(App)

// Intégration de Pinia (store global)
const pinia = createPinia()
app.use(pinia)

app.use(router)
app.mount('#app')
