import { describe, it } from 'node:test';
import assert from 'node:assert/strict';
import {
  extractStateFromHtml,
  htmlReviewPageCount,
  looksLikeCaptcha,
  reviewsCardPageUrl,
  extractAspects,
} from './reviews-html.js';

const fixture = `
<html><body>
<script type="application/json">
{
  "stack": [{
    "results": {
      "items": [{
        "title": "Грейсон",
        "businessId": "73966097892",
        "ratingData": {
          "ratingCount": 345,
          "ratingValue": 4.9,
          "reviewCount": 182
        },
        "reviewResults": {
          "reviews": [
            {
              "reviewId": "review-1",
              "author": { "name": "Иван" },
              "rating": 5,
              "text": "Отлично",
              "updatedTime": "2026-08-20T10:00:00.000Z"
            }
          ]
        }
      }]
    }
  }]
}
</script>
</body></html>
`;

describe('reviewsCardPageUrl', () => {
  it('builds a reviews card URL on the same host', () => {
    assert.equal(
      reviewsCardPageUrl('https://yandex.ru/maps/org/73966097892', '73966097892', 3),
      'https://yandex.ru/maps/org/73966097892/reviews/?page=3',
    );
  });

  it('omits page=1', () => {
    assert.equal(
      reviewsCardPageUrl('https://yandex.ru/maps/org/73966097892', '73966097892', 1),
      'https://yandex.ru/maps/org/73966097892/reviews/',
    );
  });
});

describe('extractStateFromHtml', () => {
  it('reads reviews and counters from the embedded JSON state', () => {
    const extracted = extractStateFromHtml(fixture);

    assert.equal(extracted.reviews.length, 1);
    assert.equal(extracted.reviews[0].reviewId, 'review-1');
    assert.equal(extracted.organization.name, 'Грейсон');
    assert.equal(extracted.organization.reviewsCount, 182);
    assert.equal(extracted.organization.ratingsCount, 345);
    assert.equal(extracted.organization.rating, 4.9);
  });

  it('reads rating when Yandex stores it as an object', () => {
    const html = `
<html><body>
<script type="application/json">
{
  "card": {
    "title": "Буйная Фляга",
    "businessId": "1",
    "rating": { "value": 4.6, "count": 12844 },
    "reviewsCount": 296
  }
}
</script>
</body></html>
`;
    const extracted = extractStateFromHtml(html);

    assert.equal(extracted.organization.rating, 4.6);
    assert.equal(extracted.organization.ratingsCount, 12844);
    assert.equal(extracted.organization.reviewsCount, 296);
  });
});

describe('htmlReviewPageCount', () => {
  it('caps pages at the Yandex review limit', () => {
    assert.equal(htmlReviewPageCount(182), 4);
    assert.equal(htmlReviewPageCount(600), 12);
  });
});

describe('looksLikeCaptcha', () => {
  it('ignores captcha markup when reviews are present', () => {
    assert.equal(looksLikeCaptcha(fixture), false);
    assert.equal(looksLikeCaptcha('<html>smartcaptcha</html>'), true);
  });
});

describe('extractAspects', () => {
  it('normalizes Yandex aspect rows from fetchReviews', () => {
    const aspects = extractAspects({
      data: {
        aspects: [
          { text: 'Еда', count: 1389, positive: 1080, negative: 263 },
          { name: 'Кухня', count: 1053, positives: 802, negatives: 222 },
          { text: 'Еда', count: 1, positive: 1, negative: 0 },
        ],
      },
    });

    assert.deepEqual(aspects, [
      { text: 'Еда', count: 1389, positive: 1080, negative: 263 },
      { text: 'Кухня', count: 1053, positive: 802, negative: 222 },
    ]);
  });
});
