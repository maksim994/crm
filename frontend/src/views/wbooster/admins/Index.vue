<template>
  <admin-layout>
    <div class="mb-6 flex items-center justify-between">
      <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Администраторы</h2>
      <router-link to="/admins/create" :class="btnPrimaryClass">Добавить администратора</router-link>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <table class="min-w-full">
        <thead class="border-b border-gray-200 dark:border-gray-800">
          <tr class="text-left text-sm text-gray-500">
            <th class="p-4">Имя</th>
            <th class="p-4">Email</th>
            <th class="p-4">Статус</th>
            <th class="p-4 text-right">Действия</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="admin in admins"
            :key="admin.id"
            class="border-b border-gray-100 dark:border-gray-800"
          >
            <td class="p-4 font-medium text-gray-800 dark:text-white">{{ admin.name }}</td>
            <td class="p-4">{{ admin.email }}</td>
            <td class="p-4">
              <span
                class="rounded-full px-2 py-0.5 text-xs"
                :class="
                  admin.is_active
                    ? 'bg-success-50 text-success-700 dark:bg-success-500/10'
                    : 'bg-gray-100 text-gray-500 dark:bg-gray-800'
                "
              >
                {{ admin.is_active ? 'Активен' : 'Отключён' }}
              </span>
            </td>
            <td class="p-4 text-right space-x-3">
              <router-link
                :to="`/admins/${admin.id}/edit`"
                class="text-sm text-brand-500 hover:underline"
              >
                Изменить
              </router-link>
              <button type="button" class="text-sm text-error-500" @click="remove(admin.id)">
                Удалить
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-if="!admins.length" class="p-6 text-sm text-gray-500">Администраторы не добавлены.</p>
    </div>
  </admin-layout>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import { btnPrimaryClass } from '@/constants/buttonClasses'
import { api, ApiError } from '@/api/client'

interface PlatformAdmin {
  id: number
  name: string
  email: string
  is_active: boolean
}

const admins = ref<PlatformAdmin[]>([])

async function load() {
  const res = await api<{ data: PlatformAdmin[] }>('/platform-admins')
  admins.value = res.data
}

async function remove(adminId: number) {
  if (!confirm('Удалить администратора?')) {
    return
  }

  try {
    await api(`/platform-admins/${adminId}`, { method: 'DELETE' })
    await load()
  } catch (e) {
    const message = e instanceof ApiError ? e.message : 'Не удалось удалить администратора'
    alert(message)
  }
}

onMounted(load)
</script>
