<template>
  <admin-layout>
    <PageBreadcrumb :page-title="title" />
    <div class="max-w-2xl">
      <p
        v-if="tokenAlert"
        class="mb-4 rounded-lg bg-success-50 p-4 text-sm whitespace-pre-wrap dark:bg-success-500/10"
      >
        {{ tokenAlert }}
      </p>
      <p
        v-if="integration"
        class="mb-4 rounded-lg bg-gray-50 p-4 text-sm whitespace-pre-wrap dark:bg-gray-800"
      >
        {{ integration }}
      </p>
      <form
        @submit.prevent="submit"
        class="space-y-5 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03] sm:p-8"
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
          label="Почта проекта (для пересылки)"
          id="site-inbound-email"
          type="email"
          placeholder="zayavki@client.ru"
        />
        <p class="-mt-3 text-sm text-gray-500 dark:text-gray-400">
          Любой адрес проекта. Настройте на нём пересылку всех входящих на служебный ящик CRM
          (например mail@mv-deploy.ru). CRM сопоставит проект по этому адресу в заголовках письма.
        </p>
        <div v-if="isEdit">
          <Button type="button" variant="warning" @click="regenerate">Перевыпустить токен</Button>
        </div>
        <div class="flex flex-wrap gap-3 pt-2">
          <Button type="submit" :disabled="loading">Сохранить</Button>
          <router-link to="/sites" :class="btnOutlineClass">Отмена</router-link>
        </div>
      </form>
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
import { btnOutlineClass } from '@/constants/buttonClasses'
import { api, type Paginated } from '@/api/client'

const route = useRoute()
const router = useRouter()
const isEdit = computed(() => !!route.params.id)
const title = computed(() => (isEdit.value ? 'Редактирование проекта' : 'Новый проект'))
const loading = ref(false)
const tokenAlert = ref('')
const integration = ref('')
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

  const res = await api<{ data: typeof form.value & { domains: string[] }; integration: string }>(
    `/sites/${route.params.id}`,
  )
  Object.assign(form.value, res.data)
  domainsText.value = (res.data.domains || []).join('\n')
  brandKeywordsText.value = (res.data.metrika_brand_keywords || []).join('\n')
  integration.value = res.integration
})

async function submit() {
  loading.value = true
  const payload = {
    ...form.value,
    domains: domainsText.value
      .split('\n')
      .map((d) => d.trim())
      .filter(Boolean),
    metrika_brand_keywords: brandKeywordsText.value
      .split('\n')
      .map((keyword) => keyword.trim())
      .filter(Boolean),
  }
  try {
    if (isEdit.value) {
      await api(`/sites/${route.params.id}`, { method: 'PUT', body: JSON.stringify(payload) })
    } else {
      const res = await api<{ data: unknown; token: string; integration: string }>('/sites', {
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
  const res = await api<{ token: string; integration: string }>(
    `/sites/${route.params.id}/regenerate-token`,
    { method: 'POST' },
  )
  tokenAlert.value = `Токен:\n${res.token}\n\n${res.integration}`
  integration.value = res.integration
}
</script>
