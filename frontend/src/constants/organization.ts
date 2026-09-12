import type { ParseStatus } from '@/types/api'

export const FORMAT_ERROR = 'Ссылка ведёт на поисковую выдачу, а не на карточку организации — не найден идентификатор.'

export const STATUS_LABELS: Record<ParseStatus, string> = {
  pending: 'В очереди на обработку',
  in_progress: 'Собираем отзывы и рейтинг…',
  success: 'Данные актуальны',
  failed_structure_changed: 'Яндекс изменил разметку страницы — парсер не смог прочитать отзывы',
  failed_blocked: 'Яндекс временно заблокировал запросы, повторим позже',
  failed_unavailable: 'Источник недоступен, попробуйте ещё раз позже',
}
