<template>
  <span
    :class="['stars', `stars--${size}`]"
    :aria-label="`${filled} из ${MAX_RATING}`"
  >
    <img
      v-for="index in MAX_RATING"
      :key="index"
      class="stars__icon"
      :src="starSrc(index)"
      alt=""
      aria-hidden="true"
    >
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import starEmpty from '@/assets/star-empty.svg'
import starFilled from '@/assets/star-filled.svg'
import { MAX_RATING } from '@/constants/rating'

const props = withDefaults(
  defineProps<{
    value?: number | null
    size?: 'sm' | 'md'
  }>(),
  {
    value: 0,
    size: 'sm',
  },
)

const filled = computed(() => Math.max(0, Math.min(MAX_RATING, Math.floor(props.value ?? 0))))

const starSrc = computed(() => (index: number) => (
  index <= filled.value ? starFilled : starEmpty
))
</script>

<style scoped>
.stars {
  display: inline-flex;
  align-items: center;
  gap: 2px;
}

.stars__icon {
  display: block;
}

.stars--sm .stars__icon {
  width: 14px;
  height: 14px;
}

.stars--md .stars__icon {
  width: 18px;
  height: 18px;
}
</style>
