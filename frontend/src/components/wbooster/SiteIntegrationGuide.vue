<template>
  <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03] sm:p-8">
    <h3 class="mb-4 font-semibold text-gray-800 dark:text-white">Инструкция по интеграции</h3>

    <div class="grid gap-6 lg:grid-cols-2">
      <div class="space-y-6">
        <section>
          <h4 class="mb-2 text-sm font-semibold text-gray-800 dark:text-white">Формы (seolead)</h4>
          <dl class="space-y-2 text-sm">
            <div>
              <dt class="text-gray-500">Endpoint</dt>
              <dd class="mt-1 break-all font-mono text-xs text-gray-800 dark:text-white">{{ ingestUrl }}</dd>
            </div>
            <div>
              <dt class="text-gray-500">Метод</dt>
              <dd class="text-gray-800 dark:text-white">GET или POST</dd>
            </div>
            <div>
              <dt class="text-gray-500">Метрика после ответа CRM</dt>
              <dd class="mt-1 break-all font-mono text-xs text-gray-800 dark:text-white">
                yaCounter.params({{ '{' }} 'crm-lead': &lt;id&gt; {{ '}' }})
              </dd>
            </div>
            <div>
              <dt class="text-gray-500">Параметры</dt>
              <dd class="text-gray-800 dark:text-white">
                token, phone, email, name, description, product, comment, metrika_client_id, utm_*
              </dd>
            </div>
          </dl>
          <p v-if="exampleUrl" class="mt-3 text-sm text-gray-500">
            Пример:
            <code class="mt-1 block break-all rounded bg-gray-50 p-2 text-xs dark:bg-gray-800">{{ exampleUrl }}</code>
          </p>
        </section>

        <section>
          <h4 class="mb-2 text-sm font-semibold text-gray-800 dark:text-white">Звонки (Callibri и др.)</h4>
          <dl class="space-y-2 text-sm">
            <div>
              <dt class="text-gray-500">Endpoint</dt>
              <dd class="mt-1 break-all font-mono text-xs text-gray-800 dark:text-white">{{ callWebhookUrl }}</dd>
            </div>
            <div>
              <dt class="text-gray-500">Метод</dt>
              <dd class="text-gray-800 dark:text-white">POST, token в query ?token=... или заголовок X-Site-Token</dd>
            </div>
            <div>
              <dt class="text-gray-500">Тело</dt>
              <dd class="text-gray-800 dark:text-white">
                phone или caller_phone, call_recording_url / record_url, call_duration_sec / duration
              </dd>
            </div>
          </dl>
        </section>
      </div>

      <div class="space-y-6">
        <section>
          <h4 class="mb-2 text-sm font-semibold text-gray-800 dark:text-white">Почта (IMAP)</h4>
          <ul class="list-disc space-y-1 pl-5 text-sm text-gray-700 dark:text-gray-300">
            <li>Служебный ящик INBOUND_IMAP_* (напр. mail@mv-deploy.ru)</li>
            <li>На проекте — адреса пересылки (реклама, SEO, прямые), с них forward на служебный ящик</li>
            <li>Cron: php artisan schedule:run → mail:fetch-inbound каждые 5 мин</li>
          </ul>
        </section>

        <section>
          <h4 class="mb-2 text-sm font-semibold text-gray-800 dark:text-white">Почта (webhook, опционально)</h4>
          <dl class="space-y-2 text-sm">
            <div>
              <dt class="text-gray-500">Endpoint</dt>
              <dd class="mt-1 break-all font-mono text-xs text-gray-800 dark:text-white">{{ inboundEmailWebhookUrl }}</dd>
            </div>
            <div>
              <dt class="text-gray-500">Формат</dt>
              <dd class="text-gray-800 dark:text-white">POST JSON/form, X-Inbound-Webhook-Secret при INBOUND_WEBHOOK_SECRET</dd>
            </div>
          </dl>
        </section>

        <section>
          <h4 class="mb-2 text-sm font-semibold text-gray-800 dark:text-white">Почта на сайте (автоподстановка)</h4>
          <dl class="space-y-2 text-sm">
            <div>
              <dt class="text-gray-500">Script</dt>
              <dd class="mt-1 break-all font-mono text-xs text-gray-800 dark:text-white">{{ embedScriptUrl }}</dd>
            </div>
          </dl>
          <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-gray-700 dark:text-gray-300">
            <li>Реклама (UTM cpc, yclid, gclid, …) → почта (реклама)</li>
            <li>Поиск (Google/Yandex organic) → почта (SEO / поиск)</li>
            <li>Прямой заход / закладка → почта (прямые заходы)</li>
          </ul>
        </section>

        <section v-if="showSiteDetails">
          <h4 class="mb-2 text-sm font-semibold text-gray-800 dark:text-white">Текущий проект</h4>
          <dl class="space-y-2 text-sm">
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500">Сайт</dt>
              <dd class="text-right text-gray-800 dark:text-white">{{ siteName || '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500">Домены</dt>
              <dd class="text-right text-gray-800 dark:text-white">{{ domainsLabel }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500">Счётчик Метрики</dt>
              <dd class="text-right text-gray-800 dark:text-white">{{ metrikaCounterId || '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500">Почта (реклама)</dt>
              <dd class="text-right break-all text-gray-800 dark:text-white">{{ emailAds || '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500">Почта (SEO / поиск)</dt>
              <dd class="text-right break-all text-gray-800 dark:text-white">{{ emailSeo || '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500">Почта (прямые заходы)</dt>
              <dd class="text-right break-all text-gray-800 dark:text-white">{{ emailOther || '—' }}</dd>
            </div>
          </dl>
        </section>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  ingestUrl: string
  callWebhookUrl: string
  inboundEmailWebhookUrl: string
  embedScriptUrl: string
  exampleUrl?: string
  siteName?: string
  domains?: string[]
  metrikaCounterId?: string | null
  emailAds?: string | null
  emailSeo?: string | null
  emailOther?: string | null
  showSiteDetails?: boolean
}>()

const domainsLabel = computed(() => {
  if (!props.domains?.length) {
    return '—'
  }

  return props.domains.join(', ')
})

const showSiteDetails = computed(() => props.showSiteDetails !== false)
</script>
