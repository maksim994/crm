<template>
  <admin-layout>
    <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
      <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Проекты</h2>
      <router-link to="/sites/create" :class="btnPrimaryClass">Добавить проект</router-link>
    </div>
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <table class="min-w-full">
        <thead :class="tableHeadRowClass">
          <tr>
            <th :class="tableHeadCellClass">Заказчик</th>
            <th :class="tableHeadCellClass">Проект</th>
            <th :class="tableHeadCellClass">Домены</th>
            <th :class="tableHeadCellClass">Статус</th>
            <th :class="tableHeadCellRightClass">Действия</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="site in sites" :key="site.id" :class="tableRowClass">
            <td :class="tableCellClass">{{ site.agency_client?.name }}</td>
            <td :class="tableCellMediumClass">
              <router-link :to="`/sites/${site.id}`" class="text-gray-800 hover:text-brand-500 dark:text-white/90">
                {{ site.name }}
              </router-link>
            </td>
            <td :class="tableCellClass">{{ (site.domains || []).join(', ') }}</td>
            <td :class="tableCellClass">
              <span :class="statusBadgeClass(site.status)">{{ statusLabel(site.status) }}</span>
            </td>
            <td :class="`${tableCellClass} text-right space-x-3`">
              <router-link :to="`/sites/${site.id}`" class="text-sm text-brand-500">Открыть</router-link>
              <router-link :to="`/sites/${site.id}/edit`" :class="linkMutedClass">Изменить</router-link>
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
  statusPausedBadgeClass,
  tableCellClass,
  tableCellMediumClass,
  tableHeadCellClass,
  tableHeadCellRightClass,
  tableHeadRowClass,
  tableRowClass,
} from '@/constants/uiClasses'
import { api, type Paginated } from '@/api/client'

interface Site {
  id: string
  name: string
  domains: string[]
  status: string
  agency_client?: { name: string }
}

const sites = ref<Site[]>([])

function statusLabel(status: string): string {
  const map: Record<string, string> = {
    active: 'Активен',
    paused: 'Пауза',
    archived: 'Архив',
  }
  return map[status] ?? status
}

function statusBadgeClass(status: string): string {
  const map: Record<string, string> = {
    active: statusActiveBadgeClass,
    paused: statusPausedBadgeClass,
    archived: statusArchivedBadgeClass,
  }
  return map[status] ?? statusArchivedBadgeClass
}

async function load() {
  const res = await api<Paginated<Site>>('/sites')
  sites.value = res.data
}

onMounted(load)
</script>
