<template>
  <span class="app-spinner">
    <span
      class="app-spinner__circle"
      aria-hidden="true"
    />
    <span
      v-if="label || $slots.default"
      class="app-spinner__label"
    >
      <slot>{{ label }}</slot>
    </span>
  </span>
</template>

<script setup lang="ts">
/**
 * Индикатор загрузки с опциональной подписью.
 *
 * @param {String} [label = ''] - текст рядом со спиннером
 */
withDefaults(
  defineProps<{
    label?: string
  }>(),
  {
    label: '',
  },
)
</script>

<style scoped>
.app-spinner {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #6f7378;
  font-size: 13px;
}

.app-spinner__circle {
  flex-shrink: 0;
  width: 14px;
  height: 14px;
  border: 2px solid var(--pale-gray-color);
  border-top-color: #111;
  border-radius: 50%;
  animation: app-spinner-rotate 0.8s linear infinite;
}

.app-spinner__label {
  line-height: 1.3;
}

@media (prefers-reduced-motion: reduce) {
  .app-spinner__circle {
    animation: none;
    border-top-color: var(--pale-gray-color);
    background: conic-gradient(#111 0 25%, var(--pale-gray-color) 0);
  }
}

@keyframes app-spinner-rotate {
  to {
    transform: rotate(360deg);
  }
}
</style>
