<template>
  <v-app>
    <component
      :is="layout"
      class="page"
    />
  </v-app>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import TheDefault from '@/layouts/TheDefault.vue'
import TheMain from '@/layouts/TheMain.vue'

const route = useRoute()

const layouts = {
  default: TheDefault,
  main: TheMain,
}

const layout = computed(() => {
  const name = (route.meta.layout as keyof typeof layouts) || 'default'
  return layouts[name] ?? layouts.default
})
</script>
