import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { notificationStore } from '@/store/notification'

describe('notificationStore', () => {
  beforeEach(() => {
    notificationStore.messages.splice(0)
    vi.useFakeTimers()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('добавляет сообщение и снимает его по таймеру', () => {
    notificationStore.add('success', 'Сохранено')

    expect(notificationStore.messages).toHaveLength(1)
    expect(notificationStore.messages[0]).toMatchObject({
      type: 'success',
      text: 'Сохранено',
    })

    vi.advanceTimersByTime(3000)

    expect(notificationStore.messages).toHaveLength(0)
  })

  it('удаляет сообщение вручную', () => {
    notificationStore.add('error', 'Ошибка')
    const id = notificationStore.messages[0]?.id

    expect(id).toBeDefined()
    notificationStore.remove(id as number)
    notificationStore.remove(999)

    expect(notificationStore.messages).toHaveLength(0)
  })
})
