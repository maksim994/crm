<template>
  <admin-layout>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Лиды</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Режим администратора: лиды всех заказчиков. Клиент видит только свои лиды в
          <a href="/cabinet/" class="text-brand-500 hover:underline">/cabinet/</a>.
        </p>
      </div>
      <router-link to="/leads/create" :class="btnPrimaryClass">Добавить лид</router-link>
    </div>
    <div
      class="mb-4 grid grid-cols-1 gap-4 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] md:grid-cols-2 lg:grid-cols-4"
    >
      <div>
        <label :class="formLabelClass">Заказчик</label>
        <select
          v-model="filters.agency_client_id"
          :class="formSelectClass"
          @change="onClientChange"
        >
          <option value="">Все заказчики</option>
          <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
      </div>
      <div>
        <label :class="formLabelClass">Проект</label>
        <select
          v-model="filters.site_id"
          :class="formSelectClass"
          :disabled="!filters.agency_client_id"
          @change="load"
        >
          <option value="">Все проекты</option>
          <option v-for="s in filteredSites" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>
      </div>
      <div>
        <FormSelect v-model="filters.lead_status" label="Статус" id="leads-filter-status" @update:model-value="load">
          <option value="">Все статусы</option>
          <option value="not_processed">Не обработан</option>
          <option value="preparing_offer">Составляем КП</option>
        </FormSelect>
      </div>
      <div class="flex items-end">
        <Button variant="outline" @click="load">Обновить</Button>
      </div>
    </div>
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <table class="min-w-full text-sm">
        <thead class="border-b border-gray-200 dark:border-gray-800 text-gray-500">
          <tr>
            <th class="p-4 text-left">Дата</th>
            <th class="p-4 text-left">Заказчик</th>
            <th class="p-4 text-left">Проект</th>
            <th class="p-4 text-left">Телефон</th>
            <th class="p-4 text-left">Статус</th>
            <th class="p-4"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="lead in leads" :key="lead.id" class="border-b border-gray-100 dark:border-gray-800">
            <td class="p-4">{{ formatDate(lead.created_at) }}</td>
            <td class="p-4">{{ lead.site?.agency_client?.name }}</td>
            <td class="p-4">{{ lead.site?.name }}</td>
            <td class="p-4">{{ lead.phone || '—' }}</td>
            <td class="p-4">{{ lead.lead_status_label }}</td>
            <td class="p-4 text-right">
              <router-link :to="`/leads/${lead.id}`" class="text-brand-500">Открыть</router-link>
            </td>
          </tr>
          <tr v-if="!leads.length">
            <td colspan="6" class="p-8 text-center text-gray-500">Нет лидов по выбранным фильтрам</td>
          </tr>
        </tbody>
      </table>
    </div>
  </admin-layout>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import Button from '@/components/ui/Button.vue'
import FormSelect from '@/components/wbooster/FormSelect.vue'
import { formLabelClass, formSelectClass } from '@/constants/formClasses'
import { btnPrimaryClass } from '@/constants/buttonClasses'
import { api, type Paginated } from '@/api/client'

interface Lead {
  id: string
  phone: string | null
  lead_status_label: string
  created_at: string
  site?: { name: string; agency_client?: { name: string } }
}

interface Client {
  id: string
  name: string
}

interface Site {
  id: string
  name: string
  agency_client_id: string
}

const route = useRoute()
const leads = ref<Lead[]>([])
const clients = ref<Client[]>([])
const sites = ref<Site[]>([])

const filters = reactive({
  agency_client_id: '',
  site_id: '',
  lead_status: '',
})

const filteredSites = computed(() => {
  if (!filters.agency_client_id) {
    return []
  }
  return sites.value.filter((s) => s.agency_client_id === filters.agency_client_id)
})

function formatDate(iso: string) {
  if (!iso) return '—'
  return new Date(iso).toLocaleString('ru-RU')
}

function queryString(): string {
  const params = new URLSearchParams()
  Object.entries(filters).forEach(([key, value]) => {
    if (value) {
      params.set(key, value)
    }
  })
  const q = params.toString()
  return q ? `?${q}` : ''
}

async function load() {
  const res = await api<Paginated<Lead>>(`/leads${queryString()}`)
  leads.value = res.data
}

function onClientChange() {
  filters.site_id = ''
  load()
}

async function loadClients() {
  const res = await api<Paginated<Client>>('/clients?per_page=100')
  clients.value = res.data
}

async function loadSites() {
  const res = await api<Paginated<Site>>('/sites?per_page=100')
  sites.value = res.data
}

watch(() => filters.lead_status, load)

onMounted(async () => {
  await Promise.all([loadClients(), loadSites()])

  const clientId = route.query.agency_client_id
  const siteId = route.query.site_id
  if (typeof clientId === 'string') {
    filters.agency_client_id = clientId
  }
  if (typeof siteId === 'string') {
    const matched = sites.value.find((s) => s.id === siteId)
    if (matched) {
      filters.agency_client_id = matched.agency_client_id
      filters.site_id = siteId
    }
  }

  await load()
})
</script>
