<template>
  <aside
    :class="[
      'fixed mt-16 flex flex-col lg:mt-0 top-0 px-5 left-0 bg-white dark:bg-gray-900 dark:border-gray-800 text-gray-900 h-screen transition-all duration-300 ease-in-out z-99999 border-r border-gray-200',
      {
        'lg:w-[290px]': isExpanded || isMobileOpen || isHovered,
        'lg:w-[90px]': !isExpanded && !isHovered,
        'translate-x-0 w-[290px]': isMobileOpen,
        '-translate-x-full': !isMobileOpen,
        'lg:translate-x-0': true,
      },
    ]"
    @mouseenter="!isExpanded && (isHovered = true)"
    @mouseleave="isHovered = false"
  >
    <div
      :class="[
        'py-8 flex',
        !isExpanded && !isHovered ? 'lg:justify-center' : 'justify-start',
      ]"
    >
      <router-link to="/">
        <img
          v-if="isExpanded || isHovered || isMobileOpen"
          class="dark:hidden"
          src="/images/logo/logo.svg"
          alt="WBooster"
          width="150"
          height="40"
        />
        <img
          v-if="isExpanded || isHovered || isMobileOpen"
          class="hidden dark:block"
          src="/images/logo/logo-dark.svg"
          alt="WBooster"
          width="150"
          height="40"
        />
        <img v-else src="/images/logo/logo-icon.svg" alt="WBooster" width="32" height="32" />
      </router-link>
    </div>

    <div class="flex flex-col overflow-y-auto duration-300 ease-linear no-scrollbar">
      <nav class="mb-6 flex flex-col gap-2">
        <router-link
          to="/"
          class="menu-item group"
          :class="route.path === '/' ? 'menu-item-active' : 'menu-item-inactive'"
        >
          <span :class="route.path === '/' ? 'menu-item-icon-active' : 'menu-item-icon-inactive'">
            <TableIcon />
          </span>
          <span v-if="isExpanded || isHovered || isMobileOpen" class="menu-item-text">Лиды</span>
        </router-link>

        <div v-if="sites.length && (isExpanded || isHovered || isMobileOpen)" class="mt-4">
          <h2 class="mb-3 text-xs uppercase leading-[20px] text-gray-400">Проекты</h2>
        </div>
        <div v-else-if="sites.length" class="mt-4 flex justify-center">
          <HorizontalDots />
        </div>

        <div v-for="site in sites" :key="site.id" class="flex flex-col">
          <button
            type="button"
            class="menu-item group w-full"
            :class="[
              isProjectOpen(site.id) ? 'menu-item-active' : 'menu-item-inactive',
              !isExpanded && !isHovered ? 'lg:justify-center' : 'lg:justify-start',
            ]"
            @click="toggleProject(site.id)"
          >
            <span
              :class="[
                isProjectOpen(site.id) ? 'menu-item-icon-active' : 'menu-item-icon-inactive',
              ]"
            >
              <BoxCubeIcon />
            </span>
            <span v-if="isExpanded || isHovered || isMobileOpen" class="menu-item-text truncate">
              {{ site.name }}
            </span>
            <ChevronDownIcon
              v-if="isExpanded || isHovered || isMobileOpen"
              :class="[
                'ml-auto h-5 w-5 shrink-0 transition-transform duration-200',
                { 'rotate-180 text-brand-500': isProjectOpen(site.id) },
              ]"
            />
          </button>

          <div
            v-show="isProjectOpen(site.id) && (isExpanded || isHovered || isMobileOpen)"
            class="mt-1 space-y-1 pl-9"
          >
            <router-link
              :to="`/projects/${site.id}/leads`"
              class="menu-dropdown-item block"
              :class="
                isProjectRoute(site.id, 'leads')
                  ? 'menu-dropdown-item-active'
                  : 'menu-dropdown-item-inactive'
              "
            >
              Лиды и продажи
            </router-link>
            <router-link
              :to="`/projects/${site.id}/traffic`"
              class="menu-dropdown-item block"
              :class="
                isProjectRoute(site.id, 'traffic')
                  ? 'menu-dropdown-item-active'
                  : 'menu-dropdown-item-inactive'
              "
            >
              Трафик и лиды
            </router-link>
            <router-link
              :to="`/projects/${site.id}/analytics`"
              class="menu-dropdown-item block"
              :class="
                isProjectRoute(site.id, 'analytics')
                  ? 'menu-dropdown-item-active'
                  : 'menu-dropdown-item-inactive'
              "
            >
              Аналитика
            </router-link>
          </div>
        </div>
      </nav>
    </div>
  </aside>
</template>

<script setup lang="ts">
import { computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { BoxCubeIcon, ChevronDownIcon, HorizontalDots, TableIcon } from '@/icons'
import { useSidebar } from '@/composables/useSidebar'
import { loadCabinetSites, useCabinetSites } from '@cabinet/composables/useCabinetSites'

const route = useRoute()
const { isExpanded, isMobileOpen, isHovered, openSubmenu, toggleSubmenu } = useSidebar()
const { sites } = useCabinetSites()

const activeProjectId = computed(() => {
  const match = route.path.match(/^\/projects\/([^/]+)\//)
  return match?.[1] ?? null
})

function projectKey(siteId: string): string {
  return `project-${siteId}`
}

function isProjectOpen(siteId: string): boolean {
  const key = projectKey(siteId)
  return openSubmenu.value === key || activeProjectId.value === siteId
}

function isProjectRoute(siteId: string, section: 'leads' | 'traffic' | 'analytics'): boolean {
  return route.path === `/projects/${siteId}/${section}`
}

function toggleProject(siteId: string): void {
  toggleSubmenu(projectKey(siteId))
}

watch(activeProjectId, (siteId) => {
  if (siteId) {
    openSubmenu.value = projectKey(siteId)
  }
}, { immediate: true })

onMounted(async () => {
  await loadCabinetSites()
})
</script>
