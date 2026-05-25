<template>
  <admin-layout>
    <PageBreadcrumb :page-title="site?.name ?? 'Проект'" />

    <div v-if="loading" class="text-sm text-gray-500">Загрузка…</div>

    <template v-else-if="site">
      <div class="mb-4 flex flex-wrap gap-2">
        <router-link :to="`/sites/${site.id}/edit`" :class="btnPrimaryClass">Редактировать</router-link>
        <router-link :to="leadsLink" :class="btnOutlineClass">Лиды проекта</router-link>
        <router-link to="/sites" :class="btnOutlineClass">К списку</router-link>
      </div>

      <div class="mb-6 grid gap-4 lg:grid-cols-2">
        <div
          class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]"
        >
          <h3 class="mb-4 font-semibold text-gray-800 dark:text-white">Проект</h3>
          <dl class="space-y-3 text-sm">
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500">Заказчик</dt>
              <dd class="text-right text-gray-800 dark:text-white">{{ site.agency_client?.name ?? '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500">Статус</dt>
              <dd>{{ statusLabel(site.status) }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500">Домены</dt>
              <dd class="text-right">{{ (site.domains || []).join(', ') || '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500">Метрика</dt>
              <dd>{{ site.metrika_counter_id || '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500">Timezone</dt>
              <dd>{{ site.timezone }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500">Почта проекта</dt>
              <dd class="text-right">{{ site.email_inbound_address || '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500">Лидов</dt>
              <dd>{{ site.leads_count ?? 0 }}</dd>
            </div>
          </dl>
        </div>

        <div
          class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]"
        >
          <h3 class="mb-2 font-semibold text-gray-800 dark:text-white">Токен интеграции</h3>
          <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
            Токен хранится в зашифрованном виде и показывается только сразу после создания проекта или
            перевыпуска. Сохраните его в настройках сайта.
          </p>

          <div
            v-if="token"
            class="mb-4 rounded-lg bg-success-50 p-4 dark:bg-success-500/10"
          >
            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-success-700 dark:text-success-400">
              Текущий токен (скопируйте сейчас)
            </p>
            <code class="block break-all text-sm text-gray-800 dark:text-white">{{ token }}</code>
            <div class="mt-3 flex flex-wrap gap-2">
              <Button type="button" size="sm" @click="copyToken">Скопировать</Button>
            </div>
          </div>

          <p v-else class="mb-4 text-sm text-gray-500">Токен не отображается. Перевыпустите, если нужен новый.</p>

          <Button type="button" variant="warning" @click="regenerate">Перевыпустить токен</Button>
          <p v-if="copyMessage" class="mt-2 text-sm text-success-600">{{ copyMessage }}</p>
        </div>
      </div>

      <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <h3 class="mb-4 font-semibold text-gray-800 dark:text-white">Интеграция</h3>
        <pre class="mb-4 overflow-x-auto rounded-lg bg-gray-50 p-4 text-sm whitespace-pre-wrap dark:bg-gray-800">{{ integration }}</pre>
        <p v-if="exampleUrl" class="text-sm text-gray-500">
          Пример:
          <code class="mt-1 block break-all rounded bg-gray-50 p-2 text-xs dark:bg-gray-800">{{ exampleUrl }}</code>
        </p>
      </div>
    </template>
  </admin-layout>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import Button from '@/components/ui/Button.vue'
import { btnOutlineClass, btnPrimaryClass } from '@/constants/buttonClasses'
import { api } from '@/api/client'

interface SiteDetail {
  id: string
  name: string
  domains: string[]
  status: string
  timezone: string
  metrika_counter_id?: string | null
  email_inbound_address?: string | null
  leads_count?: number
  agency_client?: { id: string; name: string }
}

const route = useRoute()
const router = useRouter()
const loading = ref(true)
const site = ref<SiteDetail | null>(null)
const integration = ref('')
const ingestUrl = ref('')
const token = ref('')
const copyMessage = ref('')

const leadsLink = computed(() => {
  if (!site.value) {
    return '/leads'
  }
  const params = new URLSearchParams()
  if (site.value.agency_client?.id) {
    params.set('agency_client_id', site.value.agency_client.id)
  }
  params.set('site_id', site.value.id)
  return `/leads?${params.toString()}`
})

const exampleUrl = computed(() => {
  if (!ingestUrl.value || !token.value) {
    return ''
  }
  const params = new URLSearchParams({ token: token.value, phone: '+79001234567' })
  return `${ingestUrl.value}?${params.toString()}`
})

function statusLabel(status: string): string {
  const map: Record<string, string> = {
    active: 'Активен',
    paused: 'Пауза',
    archived: 'Архив',
  }
  return map[status] ?? status
}

async function load() {
  loading.value = true
  try {
    const res = await api<{ data: SiteDetail; integration: string; ingest_url: string }>(
      `/sites/${route.params.id}`,
    )
    site.value = res.data
    integration.value = res.integration
    ingestUrl.value = res.ingest_url
  } finally {
    loading.value = false
  }
}

async function regenerate() {
  if (!confirm('Перевыпустить токен? Старый перестанет работать.')) {
    return
  }
  const res = await api<{ token: string; integration: string }>(
    `/sites/${route.params.id}/regenerate-token`,
    { method: 'POST' },
  )
  token.value = res.token
  integration.value = res.integration
  copyMessage.value = ''
}

async function copyToken() {
  if (!token.value) {
    return
  }
  try {
    await navigator.clipboard.writeText(token.value)
    copyMessage.value = 'Токен скопирован'
  } catch {
    copyMessage.value = 'Не удалось скопировать'
  }
}

onMounted(async () => {
  const queryToken = route.query.token
  if (typeof queryToken === 'string' && queryToken) {
    token.value = queryToken
    router.replace({ query: {} })
  }
  await load()
})
</script>
