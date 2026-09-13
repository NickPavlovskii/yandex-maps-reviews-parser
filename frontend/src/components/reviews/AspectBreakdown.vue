<template>
  <section
    v-if="rows.length"
    class="aspects"
  >
    <header class="aspects__head">
      <div>
        <h2>Темы отзывов</h2>
        <p>Аспекты приходят из того же ответа Яндекса, что и отзывы.</p>
      </div>
      <span>упоминаний · доля негатива</span>
    </header>

    <ul class="aspects__list">
      <li
        v-for="row in rows"
        :key="row.text"
      >
        <div class="aspects__meta">
          <strong>
            {{ row.text }}
            <span
              v-if="row.text === worst?.text"
              aria-hidden="true"
            >👎</span>
          </strong>
          <b>{{ formatNumber(row.count) }} · {{ row.negativeShare }}%</b>
        </div>
        <div
          class="aspects__track"
          :title="`${row.text}: ${row.positive} положительных, ${row.negative} негативных`"
        >
          <i
            class="aspects__positive"
            :style="{ width: `${row.positiveShare}%` }"
          />
          <i
            class="aspects__negative"
            :style="{ width: `${row.negativeShare}%` }"
          />
        </div>
      </li>
    </ul>

    <p
      v-if="worst"
      class="aspects__footnote"
    >
      Чаще всего жалуются на тему «{{ worst.text }}» — {{ worst.negativeShare }}% негативных упоминаний.
    </p>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { OrganizationAspect } from '@/types/api'
import { formatNumber } from '@/utils/format'

const props = defineProps<{
  aspects: OrganizationAspect[]
}>()

const rows = computed(() => [...props.aspects]
  .filter((item) => item.count > 0)
  .sort((left, right) => right.count - left.count)
  .map((item) => {
    const negativeShare = Math.round((item.negative / item.count) * 100)
    const positiveShare = Math.round((item.positive / item.count) * 100)

    return {
      ...item,
      negativeShare,
      positiveShare,
    }
  }))

const worst = computed(() => [...rows.value]
  .sort((left, right) => right.negativeShare - left.negativeShare || right.negative - left.negative)
  .at(0) ?? null)
</script>

<style scoped>
.aspects {
  margin-bottom: 24px;
  padding: 24px 28px 20px;
  background: #fff;
  border-radius: 20px;
}

.aspects__head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 20px;
}

.aspects__head h2 {
  margin: 0 0 4px;
  font-size: 18px;
  font-weight: 700;
  letter-spacing: -0.03em;
}

.aspects__head p,
.aspects__head span {
  margin: 0;
  color: var(--muted-gray);
  font-size: 13px;
}

.aspects__list {
  display: grid;
  gap: 16px;
  margin: 0;
  padding: 0;
  list-style: none;
}

.aspects__meta {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 8px;
}

.aspects__meta strong {
  font-size: 15px;
  font-weight: 600;
}

.aspects__meta b {
  color: var(--muted-gray);
  font-size: 13px;
  font-weight: 500;
  white-space: nowrap;
}

.aspects__track {
  display: flex;
  overflow: hidden;
  height: 8px;
  background: #f3f4f5;
  border-radius: 999px;
}

.aspects__positive,
.aspects__negative {
  display: block;
  height: 100%;
}

.aspects__positive {
  background: #1f7a3a;
}

.aspects__negative {
  background: #c43c32;
}

.aspects__footnote {
  margin: 18px 0 0;
  padding-top: 16px;
  border-top: 1px solid var(--divider-color);
  color: var(--muted-gray);
  font-size: 13px;
}

@media (max-width: 860px) {
  .aspects {
    padding: 20px 16px 16px;
  }

  .aspects__head {
    flex-direction: column;
  }
}
</style>
