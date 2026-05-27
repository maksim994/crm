<template>
  <cabinet-layout>
    <div class="mb-4">
      <router-link to="/" class="text-sm text-brand-500 hover:underline">← К списку лидов</router-link>
    </div>

    <div v-if="loading" class="text-sm text-gray-500">Загрузка…</div>

    <template v-else-if="lead">
      <div class="mb-6">
        <div class="flex flex-wrap items-center gap-2">
          <h2 class="text-lg font-semibold text-gray-800 dark:text-white">{{ leadTitle }}</h2>
          <span :class="channelBadgeClass(lead.channel)">{{ lead.channel_label }}</span>
          <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-700 dark:bg-gray-800 dark:text-gray-300">
            {{ lead.lead_status_label }}
          </span>
        </div>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          {{ formatDate(lead.created_at) }} · {{ lead.site?.name ?? '—' }}
        </p>
      </div>

      <div class="grid gap-4 md:grid-cols-2">
        <section class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
          <h3 class="mb-4 font-semibold text-gray-800 dark:text-white">Общее</h3>
          <dl class="space-y-3 text-sm">
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500">Дата</dt>
              <dd class="text-right text-gray-800 dark:text-white">{{ formatDate(lead.created_at) }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500">Проект</dt>
              <dd class="text-right text-gray-800 dark:text-white">{{ lead.site?.name ?? '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500">Статус</dt>
              <dd class="text-right text-gray-800 dark:text-white">{{ lead.lead_status_label }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500 shrink-0">ID</dt>
              <dd class="break-all text-right font-mono text-xs text-gray-800 dark:text-white">{{ lead.id }}</dd>
            </div>
          </dl>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
          <h3 class="mb-4 font-semibold text-gray-800 dark:text-white">Источник</h3>
          <dl class="space-y-3 text-sm">
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500">Тип</dt>
              <dd>
                <span :class="channelBadgeClass(lead.channel)">{{ lead.channel_label }}</span>
              </dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500">Рекламный канал</dt>
              <dd class="text-right text-gray-800 dark:text-white">{{ lead.advertising_channel || '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500">Домен посадочной</dt>
              <dd class="text-right text-gray-800 dark:text-white">{{ lead.landing_domain || '—' }}</dd>
            </div>
          </dl>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
          <h3 class="mb-4 font-semibold text-gray-800 dark:text-white">Контакт</h3>
          <dl class="space-y-3 text-sm">
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500">Телефон</dt>
              <dd class="text-right">
                <a
                  v-if="lead.phone"
                  :href="`tel:${lead.phone}`"
                  class="text-brand-500 hover:underline"
                >{{ lead.phone }}</a>
                <span v-else class="text-gray-800 dark:text-white">—</span>
              </dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500">Email</dt>
              <dd class="text-right break-all">
                <a
                  v-if="lead.email"
                  :href="`mailto:${lead.email}`"
                  class="text-brand-500 hover:underline"
                >{{ lead.email }}</a>
                <span v-else class="text-gray-800 dark:text-white">—</span>
              </dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500">ФИО</dt>
              <dd class="text-right text-gray-800 dark:text-white">{{ lead.contact_name || '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500">Город</dt>
              <dd class="text-right text-gray-800 dark:text-white">{{ lead.city || '—' }}</dd>
            </div>
          </dl>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
          <h3 class="mb-4 font-semibold text-gray-800 dark:text-white">Заявка</h3>
          <dl class="space-y-4 text-sm">
            <div>
              <dt class="text-gray-500">Описание формы</dt>
              <dd class="mt-1 whitespace-pre-wrap text-gray-800 dark:text-white">{{ lead.form_description || '—' }}</dd>
            </div>
            <div>
              <dt class="text-gray-500">Запрос на продукт</dt>
              <dd class="mt-1 whitespace-pre-wrap text-gray-800 dark:text-white">{{ lead.product_request || '—' }}</dd>
            </div>
            <div>
              <dt class="text-gray-500">Комментарий</dt>
              <dd class="mt-1 whitespace-pre-wrap text-gray-800 dark:text-white">{{ lead.comment || '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500">Кол-во SKU</dt>
              <dd class="text-gray-800 dark:text-white">{{ lead.sku_count != null ? lead.sku_count : '—' }}</dd>
            </div>
          </dl>
        </section>

        <section
          v-if="hasCallData"
          class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]"
        >
          <h3 class="mb-4 font-semibold text-gray-800 dark:text-white">Звонок</h3>
          <dl class="space-y-3 text-sm">
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500">Запись</dt>
              <dd class="text-right">
                <a
                  v-if="lead.call_recording_url"
                  :href="lead.call_recording_url"
                  target="_blank"
                  rel="noopener"
                  class="text-brand-500 hover:underline"
                >Слушать</a>
                <span v-else class="text-gray-800 dark:text-white">—</span>
              </dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500">Длительность</dt>
              <dd class="text-gray-800 dark:text-white">
                {{ lead.call_duration_sec != null ? `${lead.call_duration_sec} сек` : '—' }}
              </dd>
            </div>
          </dl>
        </section>

        <section
          class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03] md:col-span-2"
        >
          <h3 class="mb-4 font-semibold text-gray-800 dark:text-white">UTM и Метрика</h3>
          <dl class="grid gap-3 text-sm sm:grid-cols-2">
            <div class="flex justify-between gap-4 sm:block">
              <dt class="text-gray-500">Client ID</dt>
              <dd class="mt-0.5 break-all text-gray-800 dark:text-white sm:mt-1">{{ lead.metrika_client_id || '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4 sm:block">
              <dt class="text-gray-500">UTM Source</dt>
              <dd class="mt-0.5 text-gray-800 dark:text-white sm:mt-1">{{ lead.utm_source || '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4 sm:block">
              <dt class="text-gray-500">UTM Medium</dt>
              <dd class="mt-0.5 text-gray-800 dark:text-white sm:mt-1">{{ lead.utm_medium || '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4 sm:block">
              <dt class="text-gray-500">UTM Campaign</dt>
              <dd class="mt-0.5 text-gray-800 dark:text-white sm:mt-1">{{ lead.utm_campaign || '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4 sm:block">
              <dt class="text-gray-500">UTM Term</dt>
              <dd class="mt-0.5 text-gray-800 dark:text-white sm:mt-1">{{ lead.utm_term || '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4 sm:block">
              <dt class="text-gray-500">UTM Content</dt>
              <dd class="mt-0.5 text-gray-800 dark:text-white sm:mt-1">{{ lead.utm_content || '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4 sm:block">
              <dt class="text-gray-500">UTM Campaign (первая)</dt>
              <dd class="mt-0.5 text-gray-800 dark:text-white sm:mt-1">{{ lead.utm_campaign_first || '—' }}</dd>
            </div>
          </dl>
        </section>
      </div>
    </template>
  </cabinet-layout>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import CabinetLayout from '@cabinet/layout/CabinetLayout.vue'
import { api } from '@cabinet/api/client'

interface LeadDetail {
  id: string
  channel: string
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
const loading = ref(true)
const lead = ref<LeadDetail | null>(null)

const leadTitle = computed(() => {
  if (!lead.value) {
    return 'Лид'
  }

  return lead.value.contact_name || lead.value.phone || lead.value.email || 'Лид'
})

const hasCallData = computed(() => {
  if (!lead.value) {
    return false
  }

  return (
    lead.value.channel === 'call' ||
    !!lead.value.call_recording_url ||
    lead.value.call_duration_sec != null
  )
})

function formatDate(iso: string) {
  return new Date(iso).toLocaleString('ru-RU')
}

function channelBadgeClass(channel: string): string {
  const base = 'rounded-full px-2 py-0.5 text-xs'
  const map: Record<string, string> = {
    form: `${base} bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400`,
    call: `${base} bg-success-50 text-success-600 dark:bg-success-500/10 dark:text-success-400`,
    email: `${base} bg-warning-50 text-warning-600 dark:bg-warning-500/10 dark:text-warning-400`,
    manual: `${base} bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400`,
  }
  return map[channel] ?? `${base} bg-gray-100 text-gray-600`
}

onMounted(async () => {
  try {
    const res = await api<{ data: LeadDetail }>(`/leads/${route.params.id}`)
    lead.value = res.data
  } finally {
    loading.value = false
  }
})
</script>
