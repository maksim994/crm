<template>
  <admin-layout>
    <div class="flex flex-col gap-6 xl:flex-row xl:items-start">
      <aside
        class="w-full shrink-0 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] xl:sticky xl:top-24 xl:w-72"
      >
        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">Документация</h2>
        <nav class="space-y-5">
          <div v-for="group in groups" :key="group.title">
            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-400">{{ group.title }}</p>
            <ul class="space-y-1">
              <li v-for="doc in group.documents" :key="doc.slug">
                <router-link
                  :to="`/docs/${doc.slug}`"
                  class="block rounded-lg px-3 py-2 text-sm transition-colors"
                  :class="
                    doc.slug === activeSlug
                      ? 'bg-brand-50 font-medium text-brand-600 dark:bg-brand-500/10 dark:text-brand-400'
                      : 'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5'
                  "
                >
                  {{ doc.title }}
                </router-link>
              </li>
            </ul>
          </div>
        </nav>
      </aside>

      <div class="min-w-0 flex-1">
        <div
          v-if="loading"
          class="rounded-2xl border border-gray-200 bg-white p-8 text-sm text-gray-500 dark:border-gray-800 dark:bg-white/[0.03]"
        >
          Загрузка…
        </div>

        <div
          v-else-if="error"
          class="rounded-2xl border border-error-200 bg-error-50 p-8 text-sm text-error-700 dark:border-error-500/20 dark:bg-error-500/10 dark:text-error-400"
        >
          {{ error }}
        </div>

        <article
          v-else
          class="docs-content rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03] sm:p-8"
        >
          <header class="mb-8 border-b border-gray-200 pb-6 dark:border-gray-800">
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ title }}</h1>
            <p v-if="description" class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ description }}</p>
          </header>

          <div
            class="prose prose-gray max-w-none dark:prose-invert prose-headings:scroll-mt-24 prose-a:text-brand-500 prose-a:no-underline hover:prose-a:underline prose-pre:bg-gray-900 prose-pre:text-gray-100"
            @click="onContentClick"
            v-html="html"
          />
        </article>
      </div>
    </div>
  </admin-layout>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { marked } from 'marked'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import { api } from '@/api/client'

interface DocSummary {
  slug: string
  title: string
  description: string | null
}

interface DocGroup {
  title: string
  documents: DocSummary[]
}

interface DocDetail {
  slug: string
  title: string
  description: string | null
  content: string
}

const route = useRoute()
const router = useRouter()

const groups = ref<DocGroup[]>([])
const loading = ref(true)
const error = ref('')
const title = ref('')
const description = ref<string | null>(null)
const html = ref('')

const activeSlug = computed(() => route.params.slug as string | undefined)

marked.setOptions({
  gfm: true,
  breaks: false,
})

async function loadIndex() {
  const res = await api<{ groups: DocGroup[] }>('/docs')
  groups.value = res.groups
}

async function loadDocument(slug: string) {
  loading.value = true
  error.value = ''

  try {
    const res = await api<{ data: DocDetail }>(`/docs/${slug}`)
    title.value = res.data.title
    description.value = res.data.description
    html.value = await marked.parse(res.data.content)
    document.title = `${res.data.title} | WBooster`
  } catch {
    error.value = 'Документ не найден.'
    title.value = ''
    description.value = null
    html.value = ''
  } finally {
    loading.value = false
  }
}

function onContentClick(event: MouseEvent) {
  const target = event.target
  if (!(target instanceof Element)) {
    return
  }

  const link = target.closest('a')
  if (!link) {
    return
  }

  const href = link.getAttribute('href')
  if (!href || !href.startsWith('/docs/')) {
    return
  }

  event.preventDefault()
  router.push(href)
}

watch(
  () => route.params.slug,
  async (slug) => {
    if (typeof slug !== 'string' || !slug) {
      return
    }
    await loadDocument(slug)
  },
  { immediate: true },
)

onMounted(async () => {
  await loadIndex()

  if (!route.params.slug && groups.value.length) {
    const firstDoc = groups.value[0]?.documents[0]
    if (firstDoc) {
      router.replace(`/docs/${firstDoc.slug}`)
    }
  }
})
</script>

<style scoped>
.docs-content :deep(table) {
  display: block;
  overflow-x: auto;
}

.docs-content :deep(pre) {
  overflow-x: auto;
}
</style>
