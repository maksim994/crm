<template>
  <FullScreenLayout>
    <div class="relative p-6 bg-white z-1 dark:bg-gray-900 sm:p-0">
      <div class="relative flex min-h-screen w-full flex-col justify-center dark:bg-gray-900">
        <div class="mx-auto w-full max-w-md px-4 py-10">
          <div class="mb-8 text-center">
            <img src="/images/logo/logo.svg" alt="WBooster" class="mx-auto h-10 dark:hidden" />
            <img src="/images/logo/logo-dark.svg" alt="WBooster" class="mx-auto hidden h-10 dark:block" />
            <h1 class="mt-6 mb-2 text-title-sm font-semibold text-gray-800 dark:text-white/90">Личный кабинет</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Просмотр лидов по вашим проектам</p>
          </div>
          <p v-if="error" class="mb-4 text-sm text-error-600">{{ error }}</p>
          <form @submit.prevent="submit">
            <div class="space-y-5">
              <div>
                <label for="email" :class="formLabelClass">Email</label>
                <input id="email" v-model="email" type="email" required :class="formInputClass" />
              </div>
              <div>
                <label for="password" :class="formLabelClass">Пароль</label>
                <input id="password" v-model="password" type="password" required :class="formInputClass" />
              </div>
              <Button type="submit" class-name="w-full justify-center" :disabled="loading">{{ loading ? 'Вход…' : 'Войти' }}</Button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </FullScreenLayout>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import FullScreenLayout from '@/components/layout/FullScreenLayout.vue'
import Button from '@/components/ui/Button.vue'
import { formInputClass, formLabelClass } from '@/constants/formClasses'
import { api, ApiError } from '@cabinet/api/client'

const router = useRouter()
const route = useRoute()
const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')

onMounted(() => {
  if (route.query.error === 'wrong_role') {
    error.value = 'Этот аккаунт не имеет доступа к личному кабинету. Используйте /admin/ для входа администратора.'
  }
  if (route.query.error === 'impersonate_failed') {
    error.value = 'Не удалось войти по ссылке администратора. Запросите новую ссылку в админке.'
  }
})

async function submit() {
  loading.value = true
  error.value = ''
  try {
    await api('/login', { method: 'POST', body: JSON.stringify({ email: email.value, password: password.value }) })
    router.push((route.query.redirect as string) || '/')
  } catch (e) {
    error.value = e instanceof ApiError ? (e.errors?.email?.[0] ?? e.message) : 'Ошибка входа'
  } finally {
    loading.value = false
  }
}
</script>
