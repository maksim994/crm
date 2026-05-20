<template>
  <FullScreenLayout>
    <div class="relative p-6 bg-white z-1 dark:bg-gray-900 sm:p-0">
      <div
        class="relative flex flex-col justify-center w-full min-h-screen lg:flex-row dark:bg-gray-900"
      >
        <div class="flex flex-col flex-1 w-full lg:w-1/2">
          <div class="flex flex-col justify-center flex-1 w-full max-w-md mx-auto px-4 py-10">
            <div class="mb-8 text-center">
              <img src="/images/logo/logo.svg" alt="WBooster" class="mx-auto h-10 dark:hidden" />
              <img
                src="/images/logo/logo-dark.svg"
                alt="WBooster"
                class="mx-auto h-10 hidden dark:block"
              />
              <h1 class="mt-6 mb-2 font-semibold text-gray-800 text-title-sm dark:text-white/90">
                Вход в админку
              </h1>
              <p class="text-sm text-gray-500 dark:text-gray-400">WBooster CRM</p>
            </div>

            <p v-if="error" class="mb-4 text-sm text-error-600">{{ error }}</p>

            <form @submit.prevent="submit">
              <div class="space-y-5">
                <div>
                  <label
                    for="login-email"
                    class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400"
                  >
                    Email<span class="text-error-500">*</span>
                  </label>
                  <input
                    id="login-email"
                    v-model="email"
                    type="email"
                    required
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                  />
                </div>
                <div>
                  <label
                    for="login-password"
                    class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400"
                  >
                    Пароль<span class="text-error-500">*</span>
                  </label>
                  <input
                    id="login-password"
                    v-model="password"
                    type="password"
                    required
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                  />
                </div>
                <label class="flex cursor-pointer items-center gap-2">
                  <input
                    v-model="remember"
                    type="checkbox"
                    class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500/10"
                  />
                  <span class="text-sm text-gray-700 dark:text-gray-400">Запомнить</span>
                </label>
                <Button type="submit" class-name="w-full justify-center" :disabled="loading">
                  {{ loading ? 'Вход…' : 'Войти' }}
                </Button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </FullScreenLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import FullScreenLayout from '@/components/layout/FullScreenLayout.vue'
import Button from '@/components/ui/Button.vue'
import { api, ApiError } from '@/api/client'

const router = useRouter()
const route = useRoute()

const email = ref('')
const password = ref('')
const remember = ref(false)
const loading = ref(false)
const error = ref('')

async function submit() {
  loading.value = true
  error.value = ''
  try {
    await api('/login', {
      method: 'POST',
      body: JSON.stringify({
        email: email.value,
        password: password.value,
        remember: remember.value,
      }),
    })
    const redirect = (route.query.redirect as string) || '/'
    router.push(redirect)
  } catch (e) {
    if (e instanceof ApiError) {
      error.value = e.errors?.email?.[0] ?? e.message
    } else {
      error.value = 'Ошибка входа'
    }
  } finally {
    loading.value = false
  }
}
</script>
