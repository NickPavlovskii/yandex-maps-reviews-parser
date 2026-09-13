<template>
  <div class="reviews-page">
    <p
      v-if="!organization"
      class="empty"
    >
      Сначала подключите карточку в
      <router-link :to="{ name: 'settings' }">настройках</router-link>.
    </p>

    <template v-else>
      <header class="hero">
        <div>
          <h1 class="hero__title">
            {{ organization.name || 'Собираем данные…' }}
          </h1>
          <p class="hero__meta">
            Данные из кэша от {{ formatDateTime(organization.last_parsed_at) }}
            · собрано {{ formatNumber(organization.reviews_count) }} отзывов
          </p>
        </div>

        <div class="hero__actions">
          <app-button
            bg-color="#fff"
            color="#111"
            border
            target="_blank"
            rel="noreferrer"
            :href="organization.url"
          >
            Карточка на картах
            <template #append>
              <span aria-hidden="true">↗</span>
            </template>
          </app-button>
          <app-button
            :disabled="isRefreshing"
            @click="refresh"
          >
            <template #prepend>
              <span aria-hidden="true">↻</span>
            </template>
            {{ refreshLabel }}
          </app-button>
        </div>
      </header>

      <section class="rating-card">
        <div class="rating-card__score">
          <p class="rating-card__value">
            {{ formatRating(organization.avg_rating) }}
            <span>из {{ MAX_RATING }}</span>
          </p>
          <StarRating
            :value="organization.avg_rating"
            size="md"
          />
          <p class="rating-card__caption">Средний рейтинг по Яндекс.Картам</p>
        </div>

        <ul class="histogram">
          <li
            v-for="star in RATING_STARS"
            :key="star"
          >
            <span>{{ star }}</span>
            <div class="histogram__track">
              <div
                class="histogram__bar"
                :style="{ width: barWidth(star) }"
              />
            </div>
            <b>{{ formatNumber(breakdown(star)) }}</b>
          </li>
        </ul>

        <dl class="rating-card__counts">
          <div>
            <dt>Оценок</dt>
            <dd>{{ formatNumber(organization.ratings_count) }}</dd>
          </div>
          <div>
            <dt>Отзывов</dt>
            <dd>{{ formatNumber(organization.reviews_count) }}</dd>
          </div>
        </dl>
      </section>

      <section class="feed">
        <div class="feed__toolbar">
          <h2>Отзывы {{ formatNumber(organization.reviews_count) }}</h2>

          <div class="feed__tools">
            <app-input
              v-model.trim="search"
              placeholder="Поиск по тексту"
            >
              <template #lead>
                <img
                  :src="searchIcon"
                  width="16"
                  height="16"
                  alt=""
                  aria-hidden="true"
                >
              </template>
            </app-input>

            <app-select
              v-model="sort"
              :options="sortOptions"
            />
          </div>
        </div>

        <div class="chips">
          <button
            type="button"
            :class="['chip', { 'chip--active': ratingFilter === null }]"
            @click="ratingFilter = null"
          >
            Все оценки
          </button>
          <button
            v-for="star in RATING_STARS"
            :key="star"
            type="button"
            :class="['chip', { 'chip--active': ratingFilter === star }]"
            @click="ratingFilter = star"
          >
            {{ star }}★
          </button>
        </div>

        <p
          v-if="organizationStore.isLoadingReviews && !organizationStore.reviews.length"
          class="feed__hint"
        >
          Загружаем отзывы…
        </p>

        <p
          v-else-if="!organizationStore.reviews.length"
          class="feed__hint"
        >
          Отзывов пока нет.
        </p>

        <div
          v-else
          class="list"
        >
          <ReviewCard
            v-for="review in organizationStore.reviews"
            :key="review.id"
            :review="review"
          />
        </div>

        <ReviewsPager
          :meta="organizationStore.reviewsMeta"
          :busy="organizationStore.isLoadingReviews"
          @change="goToPage"
        />
      </section>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import searchIcon from '@/assets/search.svg'
import ReviewCard from '@/components/reviews/ReviewCard.vue'
import ReviewsPager from '@/components/reviews/ReviewsPager.vue'
import StarRating from '@/components/reviews/StarRating.vue'
import { MAX_RATING, RATING_STARS } from '@/constants/rating'
import { organizationStore } from '@/store/organization'
import {
  formatDateTime,
  formatNumber,
  formatRating,
} from '@/utils/format'

const search = ref('')
const sort = ref<'newest' | 'oldest'>('newest')
const sortOptions = [
  { value: 'newest' as const, label: 'Сначала новые' },
  { value: 'oldest' as const, label: 'Сначала старые' },
]
const ratingFilter = ref<number | null>(null)

const organization = computed(() => organizationStore.organization)
const isBusy = computed(() =>
  organization.value != null
  && (organization.value.parse_status === 'pending' || organization.value.parse_status === 'in_progress'),
)

const isRefreshing = computed(() => organizationStore.isSaving || isBusy.value)

const refreshLabel = computed(() => (
  isRefreshing.value ? 'Обновляем…' : 'Обновить данные'
))

const maxBreakdown = computed(() => {
  const values = RATING_STARS.map((star) => breakdown(star))
  return Math.max(1, ...values)
})

function breakdown(star: number) {
  return organization.value?.rating_breakdown?.find((item) => item.rating === star)?.count ?? 0
}

function barWidth(star: number) {
  return `${Math.max(2, (breakdown(star) / maxBreakdown.value) * 100)}%`
}

function filters() {
  return {
    q: search.value,
    sort: sort.value,
    rating: ratingFilter.value,
  }
}

function loadPage(page = 1) {
  return organizationStore.loadReviews(page, filters())
}

async function goToPage(page: number) {
  await loadPage(page)
  document.querySelector('.feed')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

async function refresh() {
  if (!organization.value) {
    return
  }

  try {
    await organizationStore.saveUrl(organization.value.url)
  } catch {
    // urlError is shown on settings
  }
}

onMounted(async () => {
  await organizationStore.restore()

  if (organizationStore.organization?.parse_status === 'success') {
    await loadPage(1)
  }
})

watch([sort, ratingFilter], () => {
  void loadPage(1)
})

let searchTimer: ReturnType<typeof setTimeout> | null = null

watch(search, () => {
  if (searchTimer) {
    clearTimeout(searchTimer)
  }

  searchTimer = setTimeout(() => {
    void loadPage(1)
  }, 300)
})

onBeforeUnmount(() => {
  if (searchTimer) {
    clearTimeout(searchTimer)
  }
})
</script>

<style scoped>
.reviews-page {
  width: min(1040px, 100%);
  min-width: 0;
  color: #111;
}

.empty {
  margin: 48px 0 0;
  color: var(--muted-gray);
  font-size: 15px;
}

.empty a {
  color: #111;
}

.hero {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 24px;
  margin-bottom: 28px;
}

.hero__title {
  margin: 0 0 10px;
  font-size: 36px;
  font-weight: 700;
  letter-spacing: -0.04em;
  line-height: 1.1;
  overflow-wrap: anywhere;
}

.hero__meta {
  margin: 0;
  color: var(--muted-gray);
  font-size: 14px;
  overflow-wrap: break-word;
}

.hero__actions {
  display: flex;
  flex-shrink: 0;
  gap: 8px;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  height: 44px;
  padding: 0 18px;
  border-radius: 999px;
  font-size: 14px;
  font-weight: 500;
  text-decoration: none;
  cursor: pointer;
}

.btn--ghost {
  border: 1px solid #ececee;
  background: #fff;
  color: #111;
}

.btn--dark {
  border: none;
  background: #111;
  color: #fff;
}

.btn:disabled {
  opacity: 0.55;
  cursor: default;
}

.rating-card {
  display: grid;
  grid-template-columns: 220px minmax(240px, 1fr) auto;
  align-items: center;
  gap: 32px;
  margin-bottom: 40px;
  padding: 28px 32px;
  background: #fff;
  border-radius: 20px;
}

.rating-card__value {
  margin: 0 0 10px;
  font-size: 56px;
  font-weight: 700;
  letter-spacing: -0.05em;
  line-height: 0.9;
}

.rating-card__value span {
  margin-left: 6px;
  color: var(--secondary-text-color);
  font-size: 16px;
  font-weight: 500;
}

.rating-card__caption {
  margin: 10px 0 0;
  color: var(--muted-gray);
  font-size: 13px;
}

.histogram {
  display: grid;
  gap: 8px;
  margin: 0;
  padding: 0;
  list-style: none;
}

.histogram li {
  display: grid;
  grid-template-columns: 12px 1fr 56px;
  align-items: center;
  gap: 10px;
  color: var(--secondary-text-color);
  font-size: 13px;
}

.histogram b {
  font-weight: 500;
  color: #111;
  text-align: right;
}

.histogram__track {
  height: 8px;
  overflow: hidden;
  background: #f3f4f5;
  border-radius: 999px;
}

.histogram__bar {
  height: 100%;
  min-width: 8px;
  background: #f5c518;
  border-radius: inherit;
}

.rating-card__counts {
  display: flex;
  gap: 32px;
  margin: 0;
}

.rating-card__counts dt {
  margin: 0 0 8px;
  color: #8b8e93;
  font-size: 13px;
}

.rating-card__counts dd {
  margin: 0;
  font-size: 36px;
  font-weight: 700;
  letter-spacing: -0.04em;
}

.feed__toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 16px;
}

.feed__toolbar h2 {
  margin: 0;
  font-size: 22px;
  font-weight: 700;
  letter-spacing: -0.03em;
}

.feed__tools {
  display: flex;
  align-items: center;
  gap: 10px;
}

.feed__tools :deep(.app-input) {
  width: 240px;
}

.feed__tools :deep(.app-select) {
  width: auto;
  min-width: 210px;
  flex: 0 0 auto;
}

.chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 16px;
}

.chip {
  height: 36px;
  padding: 0 14px;
  border: none;
  border-radius: 999px;
  background: #fff;
  box-shadow: 0 0 0 1px #ececee;
  color: #111;
  font-size: 14px;
  cursor: pointer;
}

.chip--active {
  background: #111;
  box-shadow: none;
  color: #fff;
}

.feed__hint {
  margin: 0;
  padding: 24px;
  background: #fff;
  border-radius: 20px;
  color: var(--muted-gray);
}

.list {
  overflow: hidden;
  background: #fff;
  border-radius: 20px;
}

@media (max-width: 860px) {
  .hero,
  .feed__toolbar,
  .feed__tools {
    flex-direction: column;
    align-items: stretch;
  }

  .hero__title {
    font-size: 28px;
  }

  .hero__actions {
    flex-direction: column;
    width: 100%;
  }

  .hero__actions :deep(.app-button) {
    width: 100%;
  }

  .rating-card {
    grid-template-columns: 1fr;
    gap: 20px;
    padding: 20px 16px;
  }

  .rating-card__value {
    font-size: 44px;
  }

  .rating-card__counts {
    gap: 20px;
  }

  .rating-card__counts dd {
    font-size: 28px;
  }

  .feed__tools :deep(.app-input),
  .feed__tools :deep(.app-select) {
    width: 100%;
    min-width: 0;
  }
}
</style>
