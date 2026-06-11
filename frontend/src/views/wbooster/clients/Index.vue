<template>
  <admin-layout>
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Заказчики</h2>
      <router-link to="/clients/create" :class="btnPrimaryClass">Добавить заказчика</router-link>
    </div>
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <table class="min-w-full">
        <thead :class="tableHeadRowClass">
          <tr>
            <th :class="tableHeadCellClass">Название</th>
            <th :class="tableHeadCellClass">ИНН</th>
            <th :class="tableHeadCellClass">Проектов</th>
            <th :class="tableHeadCellClass">Статус</th>
            <th :class="tableHeadCellRightClass">Действия</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="client in clients" :key="client.id" :class="tableRowClass">
            <td :class="tableCellMediumClass">
              <router-link
                :to="`/clients/${client.id}`"
                class="text-gray-800 hover:text-brand-500 dark:text-white/90"
              >
                {{ client.name }}
              </router-link>
            </td>
            <td :class="tableCellClass">{{ client.inn || '—' }}</td>
            <td :class="tableCellClass">{{ client.sites_count ?? 0 }}</td>
            <td :class="tableCellClass">
              <span :class="clientStatusBadgeClass(client.status)">{{ statusLabel(client.status) }}</span>
            </td>
            <td :class="`${tableCellClass} text-right space-x-3`">
              <router-link :to="`/clients/${client.id}`" class="text-sm text-brand-500 hover:underline">
                Открыть
              </router-link>
              <router-link :to="`/clients/${client.id}/edit`" :class="linkMutedClass">
                Изменить
              </router-link>
              <button type="button" class="text-sm text-error-500" @click="remove(client.id)">
                Удалить
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </admin-layout>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import { btnPrimaryClass } from '@/constants/buttonClasses'
import {
  linkMutedClass,
  statusActiveBadgeClass,
  statusArchivedBadgeClass,
  tableCellClass,
  tableCellMediumClass,
  tableHeadCellClass,
  tableHeadCellRightClass,
  tableHeadRowClass,
  tableRowClass,
} from '@/constants/uiClasses'
import { api, type Paginated } from '@/api/client'

interface Client {
  id: string
  name: string
  inn: string | null
  status: string
  sites_count?: number
}

const clients = ref<Client[]>([])

async function load() {
  const res = await api<Paginated<Client>>('/clients')
  clients.value = res.data
}

function statusLabel(status: string): string {
  return status === 'active' ? 'Активен' : 'Архив'
}

function clientStatusBadgeClass(status: string): string {
  return status === 'active' ? statusActiveBadgeClass : statusArchivedBadgeClass
}

async function remove(id: string) {
  if (!confirm('Удалить заказчика?')) return
  await api(`/clients/${id}`, { method: 'DELETE' })
  await load()
}

onMounted(load)
</script>
