<template>
  <cabinet-layout>
    <div class="mb-4">
      <router-link to="/" class="text-sm text-brand-500 hover:underline">← К списку лидов</router-link>
    </div>

    <div
      v-if="lead"
      class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]"
    >
      <h2 class="mb-6 text-lg font-semibold text-gray-800 dark:text-white">Карточка лида</h2>
      <dl class="grid gap-4 sm:grid-cols-2">
        <div v-for="row in rows" :key="row.label">
          <dt class="text-sm text-gray-500">{{ row.label }}</dt>
          <dd class="mt-1 text-sm font-medium text-gray-800 dark:text-white/90">
            <a
              v-if="row.href"
              :href="row.href"
              target="_blank"
              rel="noopener"
              class="text-brand-500 hover:underline"
            >
              {{ row.value }}
            </a>
            <template v-else>{{ row.value }}</template>
          </dd>
        </div>
      </dl>
    </div>
  </cabinet-layout>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import CabinetLayout from '@cabinet/layout/CabinetLayout.vue'
import { api } from '@cabinet/api/client'

interface LeadDetail {
  id: string
  site?: { name: string }
  channel_label: string
  phone: string | null
  email: string | null
  contact_name: string | null
  form_description: string | null
  lead_status_label: string
  city: string | null
  product_request: string | null
  comment: string | null
  sku_count: number | null
  advertising_channel: string | null
  landing_domain: string | null
  metrika_client_id: string | null
  utm_source: string | null
  utm_medium: string | null
  utm_campaign: string | null
  utm_term: string | null
  utm_content: string | null
  utm_campaign_first: string | null
  call_recording_url: string | null
  call_duration_sec: number | null
  created_at: string
}

const route = useRoute()
const lead = ref<LeadDetail | null>(null)

function formatDate(iso: string) {
  return new Date(iso).toLocaleString('ru-RU')
}

const rows = computed(() => {
  if (!lead.value) {
    return []
  }
  const l = lead.value
  return [
    { label: 'Дата', value: formatDate(l.created_at) },
    { label: 'Проект', value: l.site?.name ?? '—' },
    { label: 'Источник', value: l.channel_label },
    { label: 'Статус', value: l.lead_status_label },
    { label: 'Телефон', value: l.phone ?? '—' },
    { label: 'Email', value: l.email ?? '—' },
    { label: 'ФИО', value: l.contact_name ?? '—' },
    { label: 'Описание формы', value: l.form_description ?? '—' },
    { label: 'Рекламный канал', value: l.advertising_channel ?? '—' },
    { label: 'Город', value: l.city ?? '—' },
    { label: 'Запрос на продукт', value: l.product_request ?? '—' },
    { label: 'Комментарий', value: l.comment ?? '—' },
    { label: 'Кол-во SKU', value: l.sku_count != null ? String(l.sku_count) : '—' },
    { label: 'Домен посадочной', value: l.landing_domain ?? '—' },
    { label: 'Client ID Метрики', value: l.metrika_client_id ?? '—' },
    { label: 'UTM Source', value: l.utm_source ?? '—' },
    { label: 'UTM Medium', value: l.utm_medium ?? '—' },
    { label: 'UTM Campaign', value: l.utm_campaign ?? '—' },
    { label: 'UTM Term', value: l.utm_term ?? '—' },
    { label: 'UTM Content', value: l.utm_content ?? '—' },
    { label: 'UTM Campaign (первая)', value: l.utm_campaign_first ?? '—' },
    {
      label: 'Запись звонка',
      value: l.call_recording_url ? 'Слушать' : '—',
      href: l.call_recording_url ?? undefined,
    },
    {
      label: 'Длительность звонка',
      value: l.call_duration_sec != null ? `${l.call_duration_sec} сек` : '—',
    },
    { label: 'ID лида', value: l.id },
  ]
})

onMounted(async () => {
  const res = await api<{ data: LeadDetail }>(`/leads/${route.params.id}`)
  lead.value = res.data
})
</script>
