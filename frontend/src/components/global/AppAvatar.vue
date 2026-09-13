<template>
  <span
    :class="['app-avatar', { 'app-avatar--image': Boolean(src) }]"
    :style="sizeStyle"
    :aria-hidden="label ? undefined : true"
    :aria-label="label || undefined"
    :role="label ? 'img' : undefined"
  >
    <img
      v-if="src"
      class="app-avatar__image"
      :src="src"
      alt=""
    >
    <span
      v-else
      class="app-avatar__initials"
    >
      {{ initials }}
    </span>
  </span>
</template>

<script setup lang="ts">
/**
 * Круглый аватар: фото автора или инициалы, если изображения нет.
 *
 * @param {String} [name = ''] - имя для инициалов
 * @param {String} [src = ''] - URL изображения
 * @param {Number} [size = 36] - размер в пикселях
 * @param {String} [label = ''] - подпись для скринридеров
 */
import { computed } from 'vue'
import { authorInitials } from '@/utils/format'

const props = withDefaults(
  defineProps<{
    name?: string | null
    src?: string
    size?: number
    label?: string
  }>(),
  {
    name: '',
    src: '',
    size: 36,
    label: '',
  },
)

const initials = computed(() => authorInitials(props.name))
const sizeStyle = computed(() => ({
  width: `${props.size}px`,
  height: `${props.size}px`,
  fontSize: `${Math.round(props.size * 0.3)}px`,
}))
</script>

<style scoped>
.app-avatar {
  display: inline-flex;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  border-radius: 50%;
  background: #f0f1f2;
  color: var(--muted-gray);
  font-weight: 600;
  line-height: 1;
}

.app-avatar__image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
</style>
