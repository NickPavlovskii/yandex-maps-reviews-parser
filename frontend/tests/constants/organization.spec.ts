import { describe, expect, it } from 'vitest'
import { FORMAT_ERROR, STATUS_LABELS } from '@/constants/organization'
import type { ParseStatus } from '@/types/api'

const STATUSES: ParseStatus[] = [
  'pending',
  'in_progress',
  'success',
  'failed_structure_changed',
  'failed_blocked',
  'failed_unavailable',
]

describe('organization constants', () => {
  it('держит подпись для каждого статуса парсинга', () => {
    expect(FORMAT_ERROR).toContain('идентификатор')

    for (const status of STATUSES) {
      expect(STATUS_LABELS[status]).toBeTruthy()
    }
  })
})
