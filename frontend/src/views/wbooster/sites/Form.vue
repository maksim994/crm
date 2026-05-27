<template>
  <admin-layout>
    <PageBreadcrumb :page-title="title" />
    <div class="max-w-6xl space-y-6">
      <div v-if="isEdit" class="grid gap-4 lg:grid-cols-2 lg:items-start">
        <div
          class="space-y-4 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03] sm:p-8"
        >
          <h3 class="mb-2 font-semibold text-gray-800 dark:text-white">Токен интеграции</h3>
          <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
            Используется в формах, webhook и скрипте wbooster.js. Всегда доступен на этой странице.
          </p>
          <code
            v-if="token"
            class="block break-all rounded-lg bg-gray-50 p-4 text-sm text-gray-800 dark:bg-gray-800 dark:text-white"
          >{{ token }}</code>
          <p v-else class="text-sm text-amber-600 dark:text-amber-400">
            Нажмите «Перевыпустить токен», чтобы сохранить и показывать его здесь.
          </p>
          <div class="mt-3 flex flex-wrap gap-2">
            <Button v-if="token" type="button" size="sm" @click="copyToken">Скопировать</Button>
            <Button type="button" variant="warning" size="sm" @click="regenerate">Перевыпустить токен</Button>
          </div>
          <p v-if="copyMessage" class="mt-2 text-sm text-success-600">{{ copyMessage }}</p>
        </div>

        <div
          v-if="embedScriptTag"
          class="space-y-4 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03] sm:p-8"
        >
          <h3 class="mb-2 font-semibold text-gray-800 dark:text-white">Скрипт подстановки почт</h3>
          <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
            Вставьте на site.ru — скрипт подставит нужную почту по источнику визита.
          </p>
          <pre class="overflow-x-auto rounded-lg bg-gray-50 p-4 text-xs whitespace-pre-wrap dark:bg-gray-800">{{ embedScriptTagDisplay }}</pre>
          <Button v-if="token" type="button" size="sm" @click="copyEmbedScript">Скопировать скрипт</Button>
          <p v-if="embedCopyMessage" class="mt-2 text-sm text-success-600">{{ embedCopyMessage }}</p>
        </div>
      </div>

      <form
        @submit.prevent="submit"
        class="space-y-5 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03] sm:p-8"
        :class="isEdit ? '' : 'max-w-2xl'"
      >
        <FormSelect
          v-model="form.agency_client_id"
          label="Заказчик"
          id="site-client"
          required
        >
          <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
        </FormSelect>
        <FormInput v-model="form.name" label="Название проекта" id="site-name" required />
        <FormTextarea
          v-model="domainsText"
          label="Домены (по одному в строке)"
          id="site-domains"
          :rows="3"
          required
        />
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
          <FormInput v-model="form.metrika_counter_id" label="Метрика ID" id="site-metrika" />
          <FormSelect v-model="form.status" label="Статус" id="site-status">
            <option value="active">Активен</option>
            <option value="paused">Пауза</option>
            <option value="archived">Архив</option>
          </FormSelect>
        </div>
        <FormTextarea
          v-model="brandKeywordsText"
          label="Ключевые слова бренда (для аналитики)"
          id="site-brand-keywords"
          :rows="2"
          placeholder="ruflex&#10;руфлекс"
        />
        <p class="-mt-3 text-sm text-gray-500 dark:text-gray-400">
          Нужны для отчётов «брендовый / небрендовый поиск» в ЛК. По одному слову или фразе в строке.
        </p>
        <FormInput v-model="form.timezone" label="Timezone" id="site-timezone" />
        <FormInput
          v-model="form.email_inbound_address"
          label="Почта (реклама)"
          id="site-inbound-email"
          type="email"
          placeholder="ads@client.ru"
        />
        <FormInput
          v-model="form.email_inbound_seo"
          label="Почта (SEO / поиск)"
          id="site-inbound-email-seo"
          type="email"
          placeholder="seo@client.ru"
        />
        <FormInput
          v-model="form.email_inbound_other"
          label="Почта (прямые заходы)"
          id="site-inbound-email-other"
          type="email"
          placeholder="info@client.ru"
        />
        <p class="-mt-3 text-sm text-gray-500 dark:text-gray-400">
          Скрипт wbooster.js на сайте подставляет нужный адрес автоматически: реклама, переход из поиска
          или прямой заход. На каждый ящик настройте пересылку на служебный ящик CRM.
        </p>
        <div class="flex flex-wrap gap-3 pt-2">
          <Button type="submit" :disabled="loading">Сохранить</Button>
          <router-link to="/sites" :class="btnOutlineClass">Отмена</router-link>
        </div>
      </form>

      <site-integration-guide
        v-if="isEdit && ingestUrl"
        :ingest-url="ingestUrl"
        :call-webhook-url="callWebhookUrl"
        :inbound-email-webhook-url="inboundEmailWebhookUrl"
        :embed-script-url="embedScriptUrl"
        :example-url="exampleUrl"
        :site-name="form.name"
        :domains="domainsList"
        :metrika-counter-id="form.metrika_counter_id"
        :email-ads="form.email_inbound_address"
        :email-seo="form.email_inbound_seo"
        :email-other="form.email_inbound_other"
      />
    </div>
  </admin-layout>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import Button from '@/components/ui/Button.vue'
import FormInput from '@/components/wbooster/FormInput.vue'
import FormSelect from '@/components/wbooster/FormSelect.vue'
import FormTextarea from '@/components/wbooster/FormTextarea.vue'
import SiteIntegrationGuide from '@/components/wbooster/SiteIntegrationGuide.vue'
import { btnOutlineClass } from '@/constants/buttonClasses'
import { api, type Paginated } from '@/api/client'

const route = useRoute()
const router = useRouter()
const isEdit = computed(() => !!route.params.id)
const title = computed(() => (isEdit.value ? 'Редактирование проекта' : 'Новый проект'))
const loading = ref(false)
const token = ref('')
const embedScriptTag = ref('')
const copyMessage = ref('')
const embedCopyMessage = ref('')
const ingestUrl = ref('')
const callWebhookUrl = ref('')
const inboundEmailWebhookUrl = ref('')
const embedScriptUrl = ref('')
const clients = ref<{ id: string; name: string }[]>([])
const domainsText = ref('')
const brandKeywordsText = ref('')

const form = ref({
  agency_client_id: '',
  name: '',
  metrika_counter_id: '',
  metrika_brand_keywords: [] as string[],
  timezone: 'Europe/Moscow',
  status: 'active',
  email_inbound_address: '',
  email_inbound_seo: '',
  email_inbound_other: '',
})

const embedScriptTagDisplay = computed(() => {
  if (!token.value) {
    return embedScriptTag.value
  }

  return embedScriptTag.value.replace('SITE_TOKEN', token.value)
})

const domainsList = computed(() =>
  domainsText.value
    .split('\n')
    .map((domain) => domain.trim())
    .filter(Boolean),
)

const exampleUrl = computed(() => {
  if (!ingestUrl.value || !token.value) {
    return ''
  }

  const params = new URLSearchParams({ token: token.value, phone: '+79001234567' })
  return `${ingestUrl.value}?${params.toString()}`
})

onMounted(async () => {
  const clientRes = await api<Paginated<{ id: string; name: string }>>('/clients?per_page=100')
  clients.value = clientRes.data

  if (!isEdit.value) {
    const presetClientId = route.query.agency_client_id
    if (typeof presetClientId === 'string' && clients.value.some((c) => c.id === presetClientId)) {
      form.value.agency_client_id = presetClientId
    } else if (clients.value.length) {
      form.value.agency_client_id = clients.value[0].id
    }
    return
  }

  const res = await api<{
    data: typeof form.value & { domains: string[] }
    token: string | null
    ingest_url: string
    call_webhook_url: string
    inbound_email_webhook_url: string
    embed_script_url: string
    embed_script_tag: string
  }>(`/sites/${route.params.id}`)
  Object.assign(form.value, res.data)
  domainsText.value = (res.data.domains || []).join('\n')
  brandKeywordsText.value = (res.data.metrika_brand_keywords || []).join('\n')
  ingestUrl.value = res.ingest_url
  callWebhookUrl.value = res.call_webhook_url
  inboundEmailWebhookUrl.value = res.inbound_email_webhook_url
  embedScriptUrl.value = res.embed_script_url
  embedScriptTag.value = res.embed_script_tag
  if (res.token) {
    token.value = res.token
  }
})

async function submit() {
  loading.value = true
  const payload = {
    ...form.value,
    domains: domainsList.value,
    metrika_brand_keywords: brandKeywordsText.value
      .split('\n')
      .map((keyword) => keyword.trim())
      .filter(Boolean),
  }
  try {
    if (isEdit.value) {
      await api(`/sites/${route.params.id}`, { method: 'PUT', body: JSON.stringify(payload) })
    } else {
      const res = await api<{ data: unknown; token: string }>('/sites', {
        method: 'POST',
        body: JSON.stringify(payload),
      })
      router.push({
        path: `/sites/${(res.data as { id: string }).id}`,
        query: { token: res.token },
      })
      return
    }
    router.push('/sites')
  } finally {
    loading.value = false
  }
}

async function regenerate() {
  const res = await api<{ token: string; embed_script_tag: string }>(
    `/sites/${route.params.id}/regenerate-token`,
    { method: 'POST' },
  )
  token.value = res.token
  embedScriptTag.value = res.embed_script_tag
  copyMessage.value = 'Токен обновлён'
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

async function copyEmbedScript() {
  if (!token.value) {
    return
  }

  try {
    await navigator.clipboard.writeText(embedScriptTagDisplay.value)
    embedCopyMessage.value = 'Скрипт скопирован'
  } catch {
    embedCopyMessage.value = 'Не удалось скопировать'
  }
}
</script>
