<template>
  <admin-layout>
    <PageBreadcrumb page-title="Новый лид" />
    <div class="max-w-2xl">
      <form
        @submit.prevent="submit"
        class="space-y-5 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03] sm:p-8"
      >
        <FormSelect v-model="form.site_id" label="Проект" id="lead-site" required>
          <option value="">Выберите проект</option>
          <option v-for="site in sites" :key="site.id" :value="site.id">
            {{ site.agency_client?.name }} — {{ site.name }}
          </option>
        </FormSelect>
        <FormInput v-model="form.phone" label="Телефон" id="lead-phone" />
        <FormInput v-model="form.email" label="Email" id="lead-email" type="email" />
        <FormInput v-model="form.contact_name" label="Имя" id="lead-name" />
        <FormTextarea v-model="form.form_description" label="Описание" id="lead-description" :rows="3" />
        <FormTextarea v-model="form.comment" label="Комментарий" id="lead-comment" :rows="2" />
        <FormInput v-model="form.product_request" label="Запрос / продукт" id="lead-product" />
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
          <FormInput v-model="form.metrika_client_id" label="Metrika Client ID" id="lead-metrika-id" />
          <FormInput v-model="form.utm_source" label="UTM Source" id="lead-utm-source" />
          <FormInput v-model="form.utm_medium" label="UTM Medium" id="lead-utm-medium" />
          <FormInput v-model="form.utm_campaign" label="UTM Campaign" id="lead-utm-campaign" />
          <FormInput v-model="form.utm_term" label="UTM Term" id="lead-utm-term" />
          <FormInput v-model="form.utm_content" label="UTM Content" id="lead-utm-content" />
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400">
          Укажите телефон или email. UTM и Client ID можно заполнить вручную или оставить пустыми — при наличии
          Client ID CRM попробует подтянуть данные из Метрики.
        </p>
        <div class="flex flex-wrap gap-3 pt-2">
          <Button type="submit" :disabled="loading">Создать</Button>
          <router-link to="/leads" :class="btnOutlineClass">Отмена</router-link>
        </div>
      </form>
    </div>
  </admin-layout>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import Button from '@/components/ui/Button.vue'
import FormInput from '@/components/wbooster/FormInput.vue'
import FormSelect from '@/components/wbooster/FormSelect.vue'
import FormTextarea from '@/components/wbooster/FormTextarea.vue'
import { btnOutlineClass } from '@/constants/buttonClasses'
import { api, type Paginated } from '@/api/client'

interface SiteOption {
  id: string
  name: string
  agency_client?: { name: string }
}

const route = useRoute()
const router = useRouter()
const loading = ref(false)
const sites = ref<SiteOption[]>([])

const form = ref({
  site_id: '',
  phone: '',
  email: '',
  contact_name: '',
  form_description: '',
  comment: '',
  product_request: '',
  metrika_client_id: '',
  utm_source: '',
  utm_medium: '',
  utm_campaign: '',
  utm_term: '',
  utm_content: '',
})

onMounted(async () => {
  const res = await api<Paginated<SiteOption>>('/sites?per_page=100')
  sites.value = res.data

  const presetSiteId = route.query.site_id
  if (typeof presetSiteId === 'string' && sites.value.some((site) => site.id === presetSiteId)) {
    form.value.site_id = presetSiteId
  }
})

async function submit() {
  loading.value = true
  try {
    const res = await api<{ data: { id: string } }>('/leads', {
      method: 'POST',
      body: JSON.stringify(form.value),
    })
    router.push(`/leads/${res.data.id}`)
  } finally {
    loading.value = false
  }
}
</script>
