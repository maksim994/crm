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

      <div class="mb-6 grid gap-4 lg:grid-cols-2 lg:items-start">
        <div
          class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]"
        >
          <h3 class="mb-2 font-semibold text-gray-800 dark:text-white">Токен интеграции</h3>
          <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
            Используется в формах, webhook и скрипте wbooster.js.
          </p>

          <code
            v-if="token"
            class="mb-4 block break-all rounded-lg bg-gray-50 p-4 text-sm text-gray-800 dark:bg-gray-800 dark:text-white"
          >{{ token }}</code>

          <p v-else class="mb-4 text-sm text-amber-600 dark:text-amber-400">
            Нажмите «Перевыпустить токен», чтобы сохранить и показывать его здесь.
          </p>

          <div class="flex flex-wrap gap-2">
            <Button v-if="token" type="button" size="sm" @click="copyToken">Скопировать</Button>
            <Button type="button" variant="warning" size="sm" @click="regenerate">Перевыпустить токен</Button>
          </div>
          <p v-if="copyMessage" class="mt-2 text-sm text-success-600">{{ copyMessage }}</p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
          <h3 class="mb-2 font-semibold text-gray-800 dark:text-white">Скрипт подстановки почт</h3>
          <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
            Вставьте на site.ru. Скрипт сам определяет источник визита и подставляет почту из настроек проекта.
          </p>
          <pre class="mb-3 overflow-x-auto rounded-lg bg-gray-50 p-4 text-sm whitespace-pre-wrap dark:bg-gray-800">{{ embedScriptTagDisplay }}</pre>
          <Button v-if="token" type="button" size="sm" @click="copyEmbedScript">Скопировать скрипт с токеном</Button>
          <p v-if="embedCopyMessage" class="mt-2 text-sm text-success-600">{{ embedCopyMessage }}</p>
        </div>
      </div>

      <div class="mb-6">
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
              <dt class="text-gray-500">Почта (реклама)</dt>
              <dd class="text-right">{{ site.email_inbound_address || '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500">Почта (SEO / поиск)</dt>
              <dd class="text-right">{{ site.email_inbound_seo || '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500">Почта (прямые заходы)</dt>
              <dd class="text-right">{{ site.email_inbound_other || '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500">Лидов</dt>
              <dd>{{ site.leads_count ?? 0 }}</dd>
            </div>
          </dl>
        </div>
      </div>

      <site-integration-guide
        v-if="ingestUrl"
        :ingest-url="ingestUrl"
        :call-webhook-url="callWebhookUrl"
        :inbound-email-webhook-url="inboundEmailWebhookUrl"
        :embed-script-url="embedScriptUrl"
        :example-url="exampleUrl"
        :site-name="site.name"
        :domains="site.domains"
        :metrika-counter-id="site.metrika_counter_id"
        :email-ads="site.email_inbound_address"
        :email-seo="site.email_inbound_seo"
        :email-other="site.email_inbound_other"
      />

      <div class="mt-6">
        <diagnostics-panel
          title="Проверка проекта"
          :groups="diagnosticGroups"
          :overall-status="diagnosticStatus"
          :checked-at="diagnosticCheckedAt"
          :loading="diagnosticsLoading"
          :error="diagnosticsError"
          @refresh="loadDiagnostics"
        />
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
import DiagnosticsPanel, {
  type DiagnosticGroup,
} from '@/components/wbooster/DiagnosticsPanel.vue'
import SiteIntegrationGuide from '@/components/wbooster/SiteIntegrationGuide.vue'
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
  email_inbound_seo?: string | null
  email_inbound_other?: string | null
  leads_count?: number
  agency_client?: { id: string; name: string }
}

const route = useRoute()
const router = useRouter()
const loading = ref(true)
const site = ref<SiteDetail | null>(null)
const ingestUrl = ref('')
const callWebhookUrl = ref('')
const inboundEmailWebhookUrl = ref('')
const embedScriptUrl = ref('')
const embedScriptTag = ref('')
const embedScriptTagDisplay = computed(() => {
  if (token.value) {
    return embedScriptTag.value.replace('SITE_TOKEN', token.value)
  }

  return embedScriptTag.value
})
const token = ref('')
const copyMessage = ref('')
const embedCopyMessage = ref('')
const diagnosticsLoading = ref(false)
const diagnosticsError = ref<string | null>(null)
const diagnosticGroups = ref<DiagnosticGroup[]>([])
const diagnosticStatus = ref<string | null>(null)
const diagnosticCheckedAt = ref<string | null>(null)

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

async function loadDiagnostics() {
  if (!site.value) {
    return
  }

  diagnosticsLoading.value = true
  diagnosticsError.value = null

  try {
    const res = await api<{
      status: string
      checked_at: string
      groups: DiagnosticGroup[]
    }>(`/sites/${site.value.id}/diagnostics`)

    diagnosticGroups.value = res.groups
    diagnosticStatus.value = res.status
    diagnosticCheckedAt.value = res.checked_at
  } catch (err) {
    diagnosticsError.value = err instanceof Error ? err.message : 'Ошибка диагностики'
    diagnosticGroups.value = []
    diagnosticStatus.value = null
    diagnosticCheckedAt.value = null
  } finally {
    diagnosticsLoading.value = false
  }
}

async function copyEmbedScript() {
  if (!token.value) {
    return
  }

  const snippet = embedScriptTag.value.replace('SITE_TOKEN', token.value)

  try {
    await navigator.clipboard.writeText(snippet)
    embedCopyMessage.value = 'Скрипт скопирован'
  } catch {
    embedCopyMessage.value = 'Не удалось скопировать'
  }
}

async function load() {
  loading.value = true
  try {
    const res = await api<{
      data: SiteDetail
      token: string | null
      ingest_url: string
      call_webhook_url: string
      inbound_email_webhook_url: string
      embed_script_url: string
      embed_script_tag: string
    }>(`/sites/${route.params.id}`)
    site.value = res.data
    ingestUrl.value = res.ingest_url
    callWebhookUrl.value = res.call_webhook_url
    inboundEmailWebhookUrl.value = res.inbound_email_webhook_url
    embedScriptUrl.value = res.embed_script_url
    embedScriptTag.value = res.embed_script_tag
    if (res.token) {
      token.value = res.token
    }
  } finally {
    loading.value = false
  }
}

async function regenerate() {
  if (!confirm('Перевыпустить токен? Старый перестанет работать.')) {
    return
  }
  const res = await api<{ token: string; embed_script_tag: string }>(
    `/sites/${route.params.id}/regenerate-token`,
    { method: 'POST' },
  )
  token.value = res.token
  embedScriptTag.value = res.embed_script_tag
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
  await loadDiagnostics()
})
</script>
