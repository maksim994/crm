<template>
  <cabinet-layout>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
      <div>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Лиды</h2>
        <p v-if="clientName" class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          {{ clientName }} — показаны лиды только ваших проектов
        </p>
      </div>
      <button type="button" :class="btnOutlineClass" @click="exportCsv">Экспорт CSV</button>
    </div>

    <div
      class="mb-6 grid grid-cols-1 gap-4 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] md:grid-cols-2 lg:grid-cols-3"
    >
      <div>
        <label :class="formLabelClass">Проект</label>
        <select v-model="filters.site_id" :class="formSelectClass" @change="load">
          <option value="">Все проекты</option>
          <option v-for="site in sites" :key="site.id" :value="site.id">{{ site.name }}</option>
        </select>
      </div>
      <div>
        <label :class="formLabelClass">Канал</label>
        <select v-model="filters.channel" :class="formSelectClass" @change="load">
          <option value="">Все</option>
          <option value="form">Заявка</option>
          <option value="call">Звонок</option>
          <option value="email">Почта</option>
        </select>
      </div>
      <div>
        <label :class="formLabelClass">Статус</label>
        <select v-model="filters.lead_status" :class="formSelectClass" @change="load">
          <option value="">Все</option>
          <option value="not_processed">Не обработан</option>
          <option value="no_answer">Не ответили</option>
          <option value="preparing_offer">Составляем КП</option>
          <option value="not_interested">Неинтересен</option>
          <option value="deal_lost">Сделка провалена</option>
        </select>
      </div>
      <div>
        <label :class="formLabelClass">Дата с</label>
        <input v-model="filters.date_from" type="date" :class="formInputClass" @change="load" />
      </div>
      <div>
        <label :class="formLabelClass">Дата по</label>
        <input v-model="filters.date_to" type="date" :class="formInputClass" @change="load" />
      </div>
      <div>
        <label :class="formLabelClass">UTM Campaign</label>
        <input
          v-model="filters.utm_campaign"
          type="text"
          :class="formInputClass"
          placeholder="Поиск…"
          @keyup.enter="load"
        />
      </div>
    </div>

    <div
      class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"
    >
      <table class="min-w-full text-sm">
        <thead class="border-b border-gray-200 text-gray-500 dark:border-gray-800">
          <tr>
            <th class="p-4 text-left">Дата</th>
            <th class="p-4 text-left">Проект</th>
            <th class="p-4 text-left">Контакт</th>
            <th class="p-4 text-left">Канал</th>
            <th class="p-4 text-left">Статус</th>
            <th class="p-4 text-left">Реклама</th>
            <th class="p-4"></th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="lead in leads"
            :key="lead.id"
            class="border-b border-gray-100 dark:border-gray-800"
          >
            <td class="p-4 whitespace-nowrap">{{ formatDate(lead.created_at) }}</td>
            <td class="p-4">{{ lead.site?.name }}</td>
            <td class="p-4">{{ lead.phone || lead.email || '—' }}</td>
            <td class="p-4">{{ lead.channel_label }}</td>
            <td class="p-4">{{ lead.lead_status_label }}</td>
            <td class="p-4">{{ lead.advertising_channel || '—' }}</td>
            <td class="p-4 text-right">
              <router-link :to="`/leads/${lead.id}`" class="text-brand-500 hover:underline">
                Открыть
              </router-link>
            </td>
          </tr>
          <tr v-if="!leads.length">
            <td colspan="7" class="p-8 text-center text-gray-500">Нет лидов по выбранным фильтрам</td>
          </tr>
        </tbody>
      </table>
    </div>

    <p v-if="meta" class="mt-4 text-sm text-gray-500">
      Показано {{ leads.length }} из {{ meta.total }}
    </p>
  </cabinet-layout>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import CabinetLayout from '@cabinet/layout/CabinetLayout.vue'
import { api, exportLeadsUrl, type Paginated } from '@cabinet/api/client'
import { btnOutlineClass } from '@/constants/buttonClasses'
import { formInputClass, formLabelClass, formSelectClass } from '@/constants/formClasses'

interface Lead {
  id: string
  phone: string | null
  email: string | null
  channel_label: string
  lead_status_label: string
  advertising_channel: string | null
  created_at: string
  site?: { name: string }
}

interface SiteOption {
  id: string
  name: string
}

const leads = ref<Lead[]>([])
const sites = ref<SiteOption[]>([])
const meta = ref<Paginated<Lead>['meta'] | null>(null)
const clientName = ref('')

const filters = reactive({
  site_id: '',
  channel: '',
  lead_status: '',
  date_from: '',
  date_to: '',
  utm_campaign: '',
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
  meta.value = res.meta
}

async function loadSites() {
  const res = await api<{ data: SiteOption[] }>('/sites')
  sites.value = res.data
}

function exportCsv() {
  const params: Record<string, string> = {}
  Object.entries(filters).forEach(([key, value]) => {
    if (value) {
      params[key] = value
    }
  })
  window.location.href = exportLeadsUrl(params)
}

async function loadClient() {
  try {
    const res = await api<{ data: { agency_client?: { name: string } } }>('/user')
    clientName.value = res.data.agency_client?.name ?? ''
  } catch {
    clientName.value = ''
  }
}

onMounted(async () => {
  await loadClient()
  await loadSites()
  await load()
})
</script>
