import '@/assets/main.css'
import { createApp } from 'vue'
import App from '@cabinet/App.vue'
import router from '@cabinet/router'
import { ensureCsrf } from '@cabinet/api/client'

const app = createApp(App)

app.use(router)

void ensureCsrf()

app.mount('#app')
