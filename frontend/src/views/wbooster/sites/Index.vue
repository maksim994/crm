<template>
  <admin-layout>
    <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
      <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Проекты</h2>
      <router-link to="/sites/create" :class="btnPrimaryClass">Добавить проект</router-link>
    </div>
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <table class="min-w-full">
        <thead class="border-b border-gray-200 dark:border-gray-800">
          <tr class="text-left text-sm text-gray-500">
            <th class="p-4">Заказчик</th>
            <th class="p-4">Проект</th>
            <th class="p-4">Домены</th>
            <th class="p-4">Статус</th>
            <th class="p-4 text-right">Действия</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="site in sites" :key="site.id" class="border-b border-gray-100 dark:border-gray-800">
            <td class="p-4">{{ site.agency_client?.name }}</td>
            <td class="p-4 font-medium">
              <router-link :to="`/sites/${site.id}`" class="text-gray-800 hover:text-brand-500 dark:text-white">
                {{ site.name }}
              </router-link>
            </td>
            <td class="p-4 text-sm">{{ (site.domains || []).join(', ') }}</td>
            <td class="p-4">{{ site.status }}</td>
            <td class="p-4 text-right space-x-3">
              <router-link :to="`/sites/${site.id}`" class="text-sm text-brand-500">Открыть</router-link>
              <router-link :to="`/sites/${site.id}/edit`" class="text-sm text-gray-500 hover:text-brand-500">Изменить</router-link>
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

interface Site {
  id: string
  name: string
  domains: string[]
  status: string
  agency_client?: { name: string }
}

const sites = ref<Site[]>([])

async function load() {
  const res = await api<Paginated<Site>>('/sites')
  sites.value = res.data
}

onMounted(load)
</script>
