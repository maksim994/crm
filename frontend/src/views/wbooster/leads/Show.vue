<template>
  <admin-layout>
    <div class="mb-4 flex gap-2">
      <router-link :to="`/leads/${lead?.id}/edit`" :class="btnPrimaryClass">Редактировать</router-link>
      <router-link to="/leads" :class="btnOutlineClass">Назад</router-link>
    </div>
    <div v-if="lead" class="grid gap-4 md:grid-cols-2">
      <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <h3 class="mb-4 font-semibold">Контекст</h3>
        <dl class="space-y-2 text-sm">
          <div class="flex justify-between gap-4"><dt class="text-gray-500">Заказчик</dt><dd>{{ lead.site?.agency_client?.name }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-gray-500">Проект</dt><dd>{{ lead.site?.name }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-gray-500">Канал</dt><dd>{{ lead.channel_label }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-gray-500">Статус</dt><dd>{{ lead.lead_status_label }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-gray-500">Реклама</dt><dd>{{ lead.advertising_channel || '—' }}</dd></div>
        </dl>
      </div>
      <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <h3 class="mb-4 font-semibold">Контакт</h3>
        <dl class="space-y-2 text-sm">
          <div class="flex justify-between gap-4"><dt class="text-gray-500">Телефон</dt><dd>{{ lead.phone || '—' }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-gray-500">Email</dt><dd>{{ lead.email || '—' }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-gray-500">Имя</dt><dd>{{ lead.contact_name || '—' }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-gray-500">Комментарий</dt><dd>{{ lead.comment || '—' }}</dd></div>
        </dl>
      </div>
      <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03] md:col-span-2">
        <h3 class="mb-4 font-semibold">UTM и Метрика</h3>
        <dl class="grid gap-2 text-sm sm:grid-cols-2">
          <div class="flex justify-between gap-4"><dt class="text-gray-500">Client ID</dt><dd>{{ lead.metrika_client_id || '—' }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-gray-500">UTM Source</dt><dd>{{ lead.utm_source || '—' }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-gray-500">UTM Medium</dt><dd>{{ lead.utm_medium || '—' }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-gray-500">UTM Campaign</dt><dd>{{ lead.utm_campaign || '—' }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-gray-500">UTM Term</dt><dd>{{ lead.utm_term || '—' }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-gray-500">UTM Content</dt><dd>{{ lead.utm_content || '—' }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-gray-500">UTM Campaign (first)</dt><dd>{{ lead.utm_campaign_first || '—' }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-gray-500">Landing</dt><dd>{{ lead.landing_domain || '—' }}</dd></div>
        </dl>
      </div>
    </div>
  </admin-layout>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import { btnOutlineClass, btnPrimaryClass } from '@/constants/buttonClasses'
import { api } from '@/api/client'

interface LeadDetails {
  id: string
  channel_label?: string
  lead_status_label?: string
  advertising_channel?: string | null
  phone?: string | null
  email?: string | null
  contact_name?: string | null
  comment?: string | null
  metrika_client_id?: string | null
  utm_source?: string | null
  utm_medium?: string | null
  utm_campaign?: string | null
  utm_term?: string | null
  utm_content?: string | null
  utm_campaign_first?: string | null
  landing_domain?: string | null
  site?: { name?: string; agency_client?: { name?: string } }
}

const route = useRoute()
const lead = ref<LeadDetails | null>(null)

onMounted(async () => {
  const res = await api<{ data: LeadDetails }>(`/leads/${route.params.id}`)
  lead.value = res.data
})
</script>
