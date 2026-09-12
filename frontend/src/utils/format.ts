export function formatNumber(value: number | null | undefined): string {
  if (value == null) {
    return '—'
  }

  return value.toLocaleString('ru-RU')
}

export function formatRating(value: number | null | undefined): string {
  if (value == null) {
    return '—'
  }

  return value.toLocaleString('ru-RU', {
    minimumFractionDigits: 1,
    maximumFractionDigits: 1,
  })
}

export function formatDateTime(value: string | null | undefined): string {
  if (!value) {
    return '—'
  }

  const date = new Date(value)

  if (Number.isNaN(date.getTime())) {
    return '—'
  }

  const day = date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long' })
  const time = date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' })

  return `${day}, ${time}`
}

export function formatReviewDate(value: string | null | undefined): string {
  if (!value) {
    return '—'
  }

  const date = new Date(value)

  if (Number.isNaN(date.getTime())) {
    return '—'
  }

  return date
    .toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' })
    .replace(/\sг\.?$/, '')
}

export function formatDuration(seconds: number | null | undefined): string {
  if (seconds == null) {
    return '—'
  }

  const minutes = Math.floor(seconds / 60)
  const rest = seconds % 60

  if (minutes === 0) {
    return `${rest} с`
  }

  return `${minutes} м ${rest} с`
}

export function authorInitials(name: string | null | undefined): string {
  if (!name) {
    return '?'
  }

  const parts = name
    .trim()
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)

  return parts.map((part) => part[0]?.toUpperCase() ?? '').join('') || '?'
}

export function looksLikeYandexMapsUrl(value: string): boolean {
  return /yandex\.(ru|com)\/maps/i.test(value)
}
