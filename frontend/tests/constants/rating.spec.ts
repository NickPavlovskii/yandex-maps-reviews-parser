import { describe, expect, it } from 'vitest'
import { MAX_RATING, RATING_STARS } from '@/constants/rating'

describe('rating constants', () => {
  it('даёт звёзды от максимума к единице', () => {
    expect(MAX_RATING).toBe(5)
    expect(RATING_STARS).toEqual([5, 4, 3, 2, 1])
  })
})
