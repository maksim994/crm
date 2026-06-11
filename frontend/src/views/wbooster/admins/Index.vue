<template>
  <admin-layout>
    <div class="mb-6 flex items-center justify-between">
      <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Администраторы</h2>
      <router-link to="/admins/create" :class="btnPrimaryClass">Добавить администратора</router-link>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <table class="min-w-full">
        <thead :class="tableHeadRowClass">
          <tr>
            <th :class="tableHeadCellClass">Имя</th>
            <th :class="tableHeadCellClass">Email</th>
            <th :class="tableHeadCellClass">Статус</th>
            <th :class="tableHeadCellRightClass">Действия</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="admin in admins" :key="admin.id" :class="tableRowClass">
            <td :class="tableCellMediumClass">{{ admin.name }}</td>
            <td :class="tableCellClass">{{ admin.email }}</td>
            <td :class="tableCellClass">
              <span :class="admin.is_active ? statusActiveUserBadgeClass : statusInactiveBadgeClass">
                {{ admin.is_active ? 'Активен' : 'Отключён' }}
              </span>
            </td>
            <td :class="`${tableCellClass} text-right space-x-3`">
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
      <p v-if="!admins.length" :class="emptyStateClass">Администраторы не добавлены.</p>
    </div>
  </admin-layout>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import { btnPrimaryClass } from '@/constants/buttonClasses'
import {
  emptyStateClass,
  statusActiveUserBadgeClass,
  statusInactiveBadgeClass,
  tableCellClass,
  tableCellMediumClass,
  tableHeadCellClass,
  tableHeadCellRightClass,
  tableHeadRowClass,
  tableRowClass,
} from '@/constants/uiClasses'
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
