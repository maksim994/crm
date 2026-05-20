<template>
  <header
    class="sticky top-0 flex w-full bg-white border-gray-200 z-99999 dark:border-gray-800 dark:bg-gray-900 lg:border-b"
  >
    <div class="flex items-center justify-between w-full gap-4 px-4 py-3 lg:px-6 lg:py-4">
      <div class="flex items-center gap-3">
        <button
          type="button"
          class="flex items-center justify-center w-10 h-10 text-gray-500 border border-gray-200 rounded-lg lg:hidden dark:border-gray-800"
          @click="handleToggle"
        >
          ☰
        </button>
        <p v-if="clientName" class="text-sm text-gray-500 dark:text-gray-400">
          {{ clientName }}
        </p>
      </div>
      <button
        type="button"
        class="text-sm text-gray-600 hover:text-brand-500 dark:text-gray-400"
        @click="logout"
      >
        Выйти
      </button>
    </div>
  </header>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useSidebar } from '@/composables/useSidebar'
import { api } from '@cabinet/api/client'

const router = useRouter()
const { toggleMobileSidebar } = useSidebar()
const clientName = ref('')

function handleToggle() {
  toggleMobileSidebar()
}

onMounted(async () => {
  try {
    const res = await api<{
      data: { agency_client?: { name: string } }
    }>('/user')
    clientName.value = res.data.agency_client?.name ?? ''
  } catch {
    clientName.value = ''
  }
})

async function logout() {
  await api('/logout', { method: 'POST' })
  router.push('/login')
}
</script>
