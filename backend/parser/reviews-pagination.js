const DEFAULT_PAGE_SIZE = 50;
const MAX_PAGES = 80;

export function extractReviews(payload) {
  if (!payload || typeof payload !== 'object') {
    return [];
  }

  const reviews = payload.data?.reviews ?? payload.reviews;

  return Array.isArray(reviews) ? reviews : [];
}

export function extractPagination(payload) {
  const params = payload?.data?.params ?? payload?.params ?? {};
  const page = Number(params.page) || 1;
  const limit = Number(params.limit ?? params.pageSize) || DEFAULT_PAGE_SIZE;
  const count = Number(params.count ?? params.reviewsCount) || 0;
  const reportedTotal = Number(params.totalPages) || 0;
  const computedTotal = count > 0 ? Math.ceil(count / limit) : 0;
  const totalPages = Math.min(reportedTotal || computedTotal, MAX_PAGES);

  return {
    page,
    totalPages,
    limit,
    count,
  };
}

export async function mapWithConcurrency(items, concurrency, mapper) {
  if (items.length === 0) {
    return [];
  }

  const results = new Array(items.length);
  let nextIndex = 0;

  async function worker() {
    while (nextIndex < items.length) {
      const current = nextIndex;
      nextIndex += 1;
      results[current] = await mapper(items[current], current);
    }
  }

  await Promise.all(
    Array.from(
      { length: Math.min(concurrency, items.length) },
      () => worker(),
    ),
  );

  return results;
}
