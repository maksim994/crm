<template>
  <admin-layout>
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Заказчики</h2>
      <router-link to="/clients/create" :class="btnPrimaryClass">Добавить заказчика</router-link>
    </div>
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <table class="min-w-full">
        <thead class="border-b border-gray-200 dark:border-gray-800">
          <tr class="text-left text-sm text-gray-500">
            <th class="p-4">Название</th>
            <th class="p-4">ИНН</th>
            <th class="p-4">Проектов</th>
            <th class="p-4">Статус</th>
            <th class="p-4 text-right">Действия</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="client in clients"
            :key="client.id"
            class="border-b border-gray-100 dark:border-gray-800"
          >
            <td class="p-4 font-medium">
              <router-link
                :to="`/clients/${client.id}`"
                class="text-gray-800 hover:text-brand-500 dark:text-white"
              >
                {{ client.name }}
              </router-link>
            </td>
            <td class="p-4">{{ client.inn || '—' }}</td>
            <td class="p-4">{{ client.sites_count ?? 0 }}</td>
            <td class="p-4">
              <span class="rounded-full bg-brand-50 px-2 py-0.5 text-xs text-brand-600">
                {{ client.status === 'active' ? 'Активен' : 'Архив' }}
              </span>
            </td>
            <td class="p-4 text-right space-x-3">
              <router-link :to="`/clients/${client.id}`" class="text-sm text-brand-500 hover:underline">
                Открыть
              </router-link>
              <router-link
                :to="`/clients/${client.id}/edit`"
                class="text-sm text-gray-500 hover:text-brand-500"
              >
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

async function remove(id: string) {
  if (!confirm('Удалить заказчика?')) return
  await api(`/clients/${id}`, { method: 'DELETE' })
  await load()
}

onMounted(load)
</script>
