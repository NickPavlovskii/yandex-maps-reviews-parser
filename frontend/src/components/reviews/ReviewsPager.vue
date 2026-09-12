<template>
  <nav
    v-if="meta && meta.total > 0"
    class="pager"
    aria-label="Страницы отзывов"
  >
    <p class="pager__summary">
      {{ rangeFrom }}–{{ rangeTo }} из {{ formatNumber(meta.total) }} отзывов
      · по {{ meta.per_page }} на странице
    </p>

    <div
      v-if="meta.last_page > 1"
      class="pager__pages"
    >
      <button
        type="button"
        class="pager__nav"
        aria-label="Предыдущая страница"
        :disabled="busy || meta.current_page <= 1"
        @click="emit('change', meta.current_page - 1)"
      >
        ‹
      </button>

      <template
        v-for="(item, index) in items"
        :key="`${item}-${index}`"
      >
        <span
          v-if="item === '…'"
          class="pager__ellipsis"
        >…</span>
        <button
          v-else
          type="button"
          :class="['pager__page', { 'pager__page--active': item === meta.current_page }]"
          :disabled="busy"
          :aria-current="ariaCurrent(item)"
          @click="emit('change', item)"
        >
          {{ item }}
        </button>
      </template>

      <button
        type="button"
        class="pager__nav"
        aria-label="Следующая страница"
        :disabled="busy || meta.current_page >= meta.last_page"
        @click="emit('change', meta.current_page + 1)"
      >
        ›
      </button>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { ReviewsMeta } from '@/types/api'
import { formatNumber } from '@/utils/format'

const props = defineProps<{
  meta: ReviewsMeta | null
  busy?: boolean
}>()

const emit = defineEmits<{
  change: [page: number]
}>()

const rangeFrom = computed(() => {
  if (!props.meta || props.meta.total === 0) {
    return 0
  }

  return (props.meta.current_page - 1) * props.meta.per_page + 1
})

const rangeTo = computed(() => {
  if (!props.meta) {
    return 0
  }

  return Math.min(props.meta.current_page * props.meta.per_page, props.meta.total)
})

const ariaCurrent = computed(() => {
  const page = props.meta?.current_page

  return (item: number | '…'): 'page' | undefined => (
    item === page ? 'page' : undefined
  )
})

const MAX_PAGES_WITHOUT_ELLIPSIS = 7
const EDGE_PAGE_COUNT = 5
const SIBLING_COUNT = 1

function pageRange(from: number, to: number) {
  return Array.from({ length: to - from + 1 }, (_, index) => from + index)
}

const items = computed<(number | '…')[]>(() => {
  const last = props.meta?.last_page ?? 1
  const current = props.meta?.current_page ?? 1

  if (last <= MAX_PAGES_WITHOUT_ELLIPSIS) {
    return pageRange(1, last)
  }

  if (current < EDGE_PAGE_COUNT) {
    return [...pageRange(1, EDGE_PAGE_COUNT), '…', last]
  }

  if (current > last - EDGE_PAGE_COUNT + 1) {
    return [1, '…', ...pageRange(last - EDGE_PAGE_COUNT + 1, last)]
  }

  return [
    1,
    '…',
    ...pageRange(current - SIBLING_COUNT, current + SIBLING_COUNT),
    '…',
    last,
  ]
})
</script>

<style scoped>
.pager {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-top: 16px;
  padding: 8px 4px 0;
}

.pager__summary {
  margin: 0;
  color: #8b8e93;
  font-size: 13px;
}

.pager__pages {
  display: flex;
  align-items: center;
  gap: 6px;
}

.pager__nav,
.pager__page {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  padding: 0;
  border: none;
  border-radius: 999px;
  background: #fff;
  color: #111;
  font-size: 14px;
  cursor: pointer;
}

.pager__page--active {
  background: #111;
  color: #fff;
}

.pager__nav:disabled,
.pager__page:disabled:not(.pager__page--active) {
  color: #c0c3c7;
  cursor: default;
}

.pager__ellipsis {
  width: 20px;
  color: #8b8e93;
  font-size: 14px;
  text-align: center;
}

@media (max-width: 720px) {
  .pager {
    flex-direction: column;
    align-items: stretch;
  }

  .pager__pages {
    justify-content: center;
    flex-wrap: wrap;
  }
}
</style>
