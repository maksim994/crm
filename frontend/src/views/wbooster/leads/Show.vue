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
          <div class="flex justify-between"><dt class="text-gray-500">Заказчик</dt><dd>{{ lead.site?.agency_client?.name }}</dd></div>
          <div class="flex justify-between"><dt class="text-gray-500">Проект</dt><dd>{{ lead.site?.name }}</dd></div>
          <div class="flex justify-between"><dt class="text-gray-500">Канал</dt><dd>{{ lead.channel_label }}</dd></div>
          <div class="flex justify-between"><dt class="text-gray-500">Статус</dt><dd>{{ lead.lead_status_label }}</dd></div>
        </dl>
      </div>
      <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <h3 class="mb-4 font-semibold">Контакт</h3>
        <dl class="space-y-2 text-sm">
          <div class="flex justify-between"><dt class="text-gray-500">Телефон</dt><dd>{{ lead.phone || '—' }}</dd></div>
          <div class="flex justify-between"><dt class="text-gray-500">Email</dt><dd>{{ lead.email || '—' }}</dd></div>
          <div class="flex justify-between"><dt class="text-gray-500">Имя</dt><dd>{{ lead.contact_name || '—' }}</dd></div>
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

const route = useRoute()
const lead = ref<Record<string, unknown> | null>(null)

onMounted(async () => {
  const res = await api<{ data: Record<string, unknown> }>(`/leads/${route.params.id}`)
  lead.value = res.data
})
</script>
