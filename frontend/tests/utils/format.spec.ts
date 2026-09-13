import { describe, expect, it } from 'vitest'
import {
  authorInitials,
  formatDateTime,
  formatDuration,
  formatNumber,
  formatRating,
  formatRatingPrecise,
  formatReviewDate,
  looksLikeYandexMapsUrl,
} from '@/utils/format'

describe('formatNumber', () => {
  it('форматирует число и ставит тире вместо пустого значения', () => {
    expect(formatNumber(1200)).toBe((1200).toLocaleString('ru-RU'))
    expect(formatNumber(0)).toBe('0')
    expect(formatNumber(null)).toBe('—')
    expect(formatNumber(undefined)).toBe('—')
  })
})

describe('formatRating', () => {
  it('оставляет один знак после запятой', () => {
    expect(formatRating(4.56)).toBe((4.56).toLocaleString('ru-RU', {
      minimumFractionDigits: 1,
      maximumFractionDigits: 1,
    }))
    expect(formatRating(5)).toBe((5).toLocaleString('ru-RU', {
      minimumFractionDigits: 1,
      maximumFractionDigits: 1,
    }))
    expect(formatRating(null)).toBe('—')
  })
})

describe('formatDateTime', () => {
  it('собирает дату и время или возвращает тире', () => {
    expect(formatDateTime('2024-03-15T14:30:00.000Z')).toBe('15 марта, 14:30')
    expect(formatDateTime('')).toBe('—')
    expect(formatDateTime('не дата')).toBe('—')
    expect(formatDateTime(null)).toBe('—')
  })
})

describe('formatReviewDate', () => {
  it('пишет полную дату без сокращения года', () => {
    expect(formatReviewDate('2024-03-15T10:00:00.000Z')).toBe('15 марта 2024')
    expect(formatReviewDate('')).toBe('—')
    expect(formatReviewDate('не дата')).toBe('—')
  })
})

describe('formatRatingPrecise', () => {
  it('оставляет два знака после запятой', () => {
    expect(formatRatingPrecise(4.6)).toBe((4.6).toLocaleString('ru-RU', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }))
    expect(formatRatingPrecise(null)).toBe('—')
  })
})

describe('formatDuration', () => {
  it('переводит секунды в минуты и секунды', () => {
    expect(formatDuration(9)).toBe('9 с')
    expect(formatDuration(60)).toBe('1 м 0 с')
    expect(formatDuration(125)).toBe('2 м 5 с')
    expect(formatDuration(null)).toBe('—')
  })
})

describe('authorInitials', () => {
  it('берёт до двух заглавных букв из имени', () => {
    expect(authorInitials('Иван Петров')).toBe('ИП')
    expect(authorInitials('анна')).toBe('А')
    expect(authorInitials('  Мария  Ивановна  Сидорова ')).toBe('МИ')
    expect(authorInitials('')).toBe('?')
    expect(authorInitials(null)).toBe('?')
  })
})

describe('looksLikeYandexMapsUrl', () => {
  it('принимает только ссылки на карты Яндекса', () => {
    expect(looksLikeYandexMapsUrl('https://yandex.ru/maps/org/123')).toBe(true)
    expect(looksLikeYandexMapsUrl('https://yandex.com/maps/org/cafe/1')).toBe(true)
    expect(looksLikeYandexMapsUrl('https://maps.yandex.ru/org/1')).toBe(false)
    expect(looksLikeYandexMapsUrl('https://yandex.ru/search')).toBe(false)
    expect(looksLikeYandexMapsUrl('https://google.com/maps')).toBe(false)
  })
})
