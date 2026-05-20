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
    <nav class="flex flex-col gap-2">
      <router-link
        to="/"
        class="menu-item group"
        :class="route.path === '/' ? 'menu-item-active' : 'menu-item-inactive'"
      >
        <span
          :class="route.path === '/' ? 'menu-item-icon-active' : 'menu-item-icon-inactive'"
        >
          <TableIcon />
        </span>
        <span v-if="isExpanded || isHovered || isMobileOpen" class="menu-item-text">Лиды</span>
      </router-link>
    </nav>
  </aside>
</template>

<script setup lang="ts">
import { useRoute } from 'vue-router'
import { TableIcon } from '@/icons'
import { useSidebar } from '@/composables/useSidebar'

const route = useRoute()
const { isExpanded, isMobileOpen, isHovered } = useSidebar()
</script>
