import { describe, it } from 'node:test';
import assert from 'node:assert/strict';
import {
  extractPagination,
  extractReviews,
  mapWithConcurrency,
} from './reviews-pagination.js';

describe('extractReviews', () => {
  it('reads reviews from the data wrapper', () => {
    assert.deepEqual(
      extractReviews({ data: { reviews: [{ reviewId: '1' }] } }),
      [{ reviewId: '1' }],
    );
  });

  it('falls back to a top-level reviews array', () => {
    assert.deepEqual(
      extractReviews({ reviews: [{ reviewId: '2' }] }),
      [{ reviewId: '2' }],
    );
  });

  it('returns an empty list for an unexpected payload', () => {
    assert.deepEqual(extractReviews(null), []);
    assert.deepEqual(extractReviews({ data: {} }), []);
  });
});

describe('extractPagination', () => {
  it('reads page metadata from fetchReviews params', () => {
    assert.deepEqual(
      extractPagination({
        data: {
          params: {
            page: 1,
            totalPages: 4,
            limit: 50,
            count: 182,
          },
        },
      }),
      {
        page: 1,
        totalPages: 4,
        limit: 50,
        count: 182,
      },
    );
  });

  it('computes total pages from count when totalPages is missing', () => {
    const pagination = extractPagination({
      params: { page: 1, limit: 50, count: 120 },
    });

    assert.equal(pagination.totalPages, 3);
  });
});

describe('mapWithConcurrency', () => {
  it('preserves order with a limited worker pool', async () => {
    const seen = [];
    const results = await mapWithConcurrency([1, 2, 3, 4], 2, async (value) => {
      seen.push(value);
      await new Promise((resolve) => setTimeout(resolve, 5));
      return value * 2;
    });

    assert.deepEqual(results, [2, 4, 6, 8]);
    assert.deepEqual(seen.sort((a, b) => a - b), [1, 2, 3, 4]);
  });
});
