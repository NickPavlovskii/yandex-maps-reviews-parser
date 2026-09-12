<template>
  <div class="home-page">
    <app-page-header
      title="Скелет проекта"
      subtitle="Laravel + Vue 3"
      icon="mdi-layers-outline"
    />

    <app-button
      title="кнопка"
      :outlined="true"
      @click="loadHealth"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { api } from '@/api'
import type { HealthResponse } from '@/types/api'

const loading = ref(false)
const error = ref('')
const health = ref<HealthResponse | null>(null)

const healthSubtitle = computed(() => {
  if (loading.value) {
    return 'Загрузка…'
  }
  if (error.value) {
    return error.value
  }
  return health.value ? `cache: ${health.value.cache}` : ''
})

async function loadHealth() {
  loading.value = true
  error.value = ''

  try {
    health.value = await api.health.get()
  } catch {
    error.value = 'Не удалось получить /api/health'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  void loadHealth()
})
</script>

<style scoped lang="scss">
.home-page {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.home-page__cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 16px;
}
</style>
