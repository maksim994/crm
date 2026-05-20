<template>
  <div class="min-h-screen xl:flex">
    <cabinet-sidebar />
    <Backdrop />
    <div class="flex-1 transition-all duration-300 ease-in-out"
      :class="[isExpanded || isHovered ? 'lg:ml-[290px]' : 'lg:ml-[90px]']"
    >
      <cabinet-header />
      <div class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6">
        <slot />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import Backdrop from '@/components/layout/Backdrop.vue'
import { useSidebar } from '@/composables/useSidebar'
import CabinetHeader from '@cabinet/components/CabinetHeader.vue'
import CabinetSidebar from '@cabinet/components/CabinetSidebar.vue'

const { isExpanded, isHovered } = useSidebar()
</script>
