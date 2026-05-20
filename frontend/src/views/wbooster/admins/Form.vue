<template>
  <admin-layout>
    <PageBreadcrumb :page-title="title" />
    <div class="max-w-2xl">
      <p
        v-if="passwordAlert"
        class="mb-4 rounded-lg bg-success-50 p-4 text-sm whitespace-pre-wrap dark:bg-success-500/10"
      >
        {{ passwordAlert }}
      </p>
      <form
        @submit.prevent="submit"
        class="space-y-5 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03] sm:p-8"
      >
        <FormInput v-model="form.name" label="Имя" id="admin-name" required />
        <FormInput v-model="form.email" label="Email для входа" id="admin-email" type="email" required />
        <FormInput
          v-if="!isEdit"
          v-model="form.password"
          label="Пароль"
          id="admin-password"
          type="password"
          required
        />
        <FormInput
          v-else
          v-model="form.password"
          label="Новый пароль (оставьте пустым, чтобы не менять)"
          id="admin-password-edit"
          type="password"
        />

        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
          <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300" />
          Доступ активен
        </label>

        <div class="flex flex-wrap gap-3 pt-2">
          <Button type="submit" :disabled="loading">Сохранить</Button>
          <router-link to="/admins" :class="btnOutlineClass">Отмена</router-link>
        </div>
      </form>
    </div>
  </admin-layout>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import Button from '@/components/ui/Button.vue'
import FormInput from '@/components/wbooster/FormInput.vue'
import { btnOutlineClass } from '@/constants/buttonClasses'
import { api, ApiError } from '@/api/client'

interface PlatformAdmin {
  id: number
  name: string
  email: string
  is_active: boolean
}

const route = useRoute()
const router = useRouter()
const adminId = computed(() => route.params.id as string | undefined)
const isEdit = computed(() => !!adminId.value)
const title = computed(() => (isEdit.value ? 'Редактирование администратора' : 'Новый администратор'))

const loading = ref(false)
const passwordAlert = ref('')

const form = ref({
  name: '',
  email: '',
  password: '',
  is_active: true,
})

onMounted(async () => {
  if (!isEdit.value) {
    return
  }

  const res = await api<{ data: PlatformAdmin }>(`/platform-admins/${adminId.value}`)
  const admin = res.data
  form.value = {
    name: admin.name,
    email: admin.email,
    password: '',
    is_active: admin.is_active,
  }
})

async function submit() {
  loading.value = true

  const payload: Record<string, unknown> = {
    name: form.value.name,
    email: form.value.email,
    is_active: form.value.is_active,
  }

  if (form.value.password) {
    payload.password = form.value.password
  }

  try {
    if (isEdit.value) {
      await api(`/platform-admins/${adminId.value}`, {
        method: 'PUT',
        body: JSON.stringify(payload),
      })
      router.push('/admins')
    } else {
      if (!form.value.password) {
        return
      }
      payload.password = form.value.password
      const res = await api<{ data: PlatformAdmin; generated_password: string }>('/platform-admins', {
        method: 'POST',
        body: JSON.stringify(payload),
      })
      passwordAlert.value = `Администратор создан.\n\nПароль для входа:\n${res.generated_password}\n\nСохраните пароль — повторно он не отображается.`
      setTimeout(() => {
        router.push('/admins')
      }, 4000)
    }
  } catch (e) {
    const message = e instanceof ApiError ? e.message : 'Не удалось сохранить администратора'
    alert(message)
  } finally {
    loading.value = false
  }
}
</script>
