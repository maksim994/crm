<template>
  <admin-layout>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 md:gap-6 mb-6">
      <div
        v-for="card in cards"
        :key="card.label"
        class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6"
      >
        <div class="text-sm text-gray-500 dark:text-gray-400">{{ card.label }}</div>
        <h4 class="mt-2 font-bold text-gray-800 text-title-sm dark:text-white/90">
          {{ card.value }}
        </h4>
      </div>
    </div>
    <div class="flex flex-wrap gap-3">
      <router-link to="/clients/create" :class="btnPrimaryClass">Добавить заказчика</router-link>
      <router-link to="/sites/create" :class="btnOutlineClass">Добавить проект</router-link>
      <router-link to="/leads" :class="btnOutlineClass">Все лиды</router-link>
    </div>
  </admin-layout>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import { btnOutlineClass, btnPrimaryClass } from '@/constants/buttonClasses'
import { api } from '@/api/client'

const stats = ref({ clients_count: 0, sites_count: 0, leads_count: 0 })

const cards = computed(() => [
  { label: 'Заказчики', value: stats.value.clients_count },
  { label: 'Проекты', value: stats.value.sites_count },
  { label: 'Лиды', value: stats.value.leads_count },
])

onMounted(async () => {
  stats.value = await api('/dashboard')
})
</script>
