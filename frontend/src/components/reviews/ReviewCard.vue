<template>
  <article class="review">
    <app-avatar :name="review.author" />
    <div class="review__body">
      <header class="review__head">
        <strong>{{ review.author || 'Без имени' }}</strong>
        <star-rating :value="review.rating" />
        <time>{{ formatReviewDate(review.published_at) }}</time>
      </header>
      <p class="review__text">
        {{ review.text || 'Без текста' }}
      </p>
      <div
        v-if="review.business_reply"
        class="reply"
      >
        <p class="reply__label">
          <span aria-hidden="true">↩</span>
          Ответ организации
        </p>
        <p>{{ review.business_reply }}</p>
      </div>
    </div>
  </article>
</template>

<script setup lang="ts">
import StarRating from '@/components/reviews/StarRating.vue'
import type { Review } from '@/types/api'
import { formatReviewDate } from '@/utils/format'

defineProps<{
  review: Review
}>()
</script>

<style scoped>
.review {
  display: flex;
  gap: 14px;
  min-width: 0;
  padding: 22px 24px;
}

.review__body {
  min-width: 0;
}

.review + .review {
  border-top: 1px solid var(--divider-color);
}

.review__head {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px 10px;
  margin-bottom: 8px;
}

.review__head strong {
  font-size: 14px;
}

.review__head time {
  color: var(--secondary-text-color);
  font-size: 13px;
}

.review__text {
  margin: 0;
  font-size: 15px;
  line-height: 1.55;
  overflow-wrap: anywhere;
}

.reply {
  margin-top: 14px;
  padding-left: 14px;
  border-left: 2px solid #ececee;
}

.reply__label {
  display: flex;
  align-items: center;
  gap: 6px;
  margin: 0 0 6px;
  color: var(--secondary-text-color);
  font-size: 13px;
}

.reply p {
  margin: 0;
  color: #3d4044;
  font-size: 14px;
  line-height: 1.5;
  overflow-wrap: anywhere;
}

@media (max-width: 860px) {
  .review {
    padding: 18px 16px;
  }
}
</style>
