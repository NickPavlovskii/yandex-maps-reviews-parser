export const MAX_RATING = 5

export const RATING_STARS = Array.from(
  { length: MAX_RATING },
  (_, index) => MAX_RATING - index,
)
