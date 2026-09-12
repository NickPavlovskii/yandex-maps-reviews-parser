import { fileURLToPath } from 'node:url';
import path from 'node:path';
import { chromium } from 'playwright';
import {
  extractPagination,
  extractReviews,
  mapWithConcurrency,
} from './reviews-pagination.js';
import {
  extractStateFromHtml,
  htmlReviewPageCount,
  looksLikeCaptcha,
  readRating,
  reviewsCardPageUrl,
} from './reviews-html.js';

const HTTP_PAGE_CONCURRENCY = 5;

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

function log(message) {
  console.error(message);
}

export function extractBusinessIdFromUrl(value) {
  const decoded = decodeURIComponent(value);
  const match =
    decoded.match(/oid=(\d+)/i) ||
    decoded.match(/\/org\/(?:[^/]+\/)*(\d+)/i) ||
    decoded.match(/businessId=(\d+)/i);

  return match?.[1] ?? null;
}

function formatReviewDate(value) {
  if (value == null || value === '') {
    return null;
  }

  if (typeof value === 'string' && Number.isNaN(Number(value))) {
    return value.slice(0, 10);
  }

  const numeric = Number(value);
  if (!Number.isFinite(numeric) || numeric <= 0) {
    return null;
  }

  const millis = numeric > 1e12 ? numeric : numeric * 1000;
  return new Date(millis).toISOString().slice(0, 10);
}

function normalizeReview(review) {
  return {
    yandexReviewId: review.reviewId != null ? String(review.reviewId) : null,
    author: review.author?.name ?? null,
    rating: review.rating ?? null,
    text: review.text ?? null,
    businessReply: review.businessComment?.text ?? null,
    publishedAt: formatReviewDate(review.updatedTime ?? review.time ?? review.date),
    language: review.textLanguage ?? null,
    likes: review.reactions?.likes ?? 0,
    dislikes: review.reactions?.dislikes ?? 0,
  };
}

function applyOrganizationPayload(organization, payload) {
  const data = payload?.data ?? payload ?? {};
  const company = data.company ?? data.organization ?? data;

  const businessId =
    data.businessId ??
    company.businessId ??
    company.oid ??
    company.id ??
    null;

  if (businessId) {
    organization.businessId = String(businessId);
  }

  const name = company.title ?? company.name ?? data.title ?? null;
  if (name && !organization.name) {
    organization.name = String(name);
  }

  const ratingInfo = readRating(
    company.ratingData ?? company.rating ?? data.rating ?? company.score ?? data.ratingData,
  );

  if (ratingInfo.rating != null && organization.rating == null) {
    organization.rating = ratingInfo.rating;
  }

  const ratingsCount =
    ratingInfo.ratingsCount ??
    company.ratingsCount ??
    company.ratingCount ??
    data.ratingsCount ??
    data.ratingCount ??
    null;

  const reviewsCount =
    company.reviewsCount ??
    data.reviewsCount ??
    company.reviewCount ??
    data.params?.count ??
    payload?.params?.count ??
    null;

  if (ratingsCount != null && organization.ratingsCount == null) {
    organization.ratingsCount = Number(ratingsCount);
  }

  if (reviewsCount != null && organization.reviewsCount == null) {
    organization.reviewsCount = Number(reviewsCount);
  }
}

async function scrollReviewsPanel(page) {
  await page.evaluate(() => {
    const review = document.querySelector(
      '[itemprop="review"], [class*="business-review-view"], [class*="review-view"]',
    );

    if (review) {
      let el = review.parentElement;
      while (el) {
        const style = window.getComputedStyle(el);
        const scrollable =
          (style.overflowY === 'auto' || style.overflowY === 'scroll') &&
          el.scrollHeight > el.clientHeight + 20;

        if (scrollable) {
          el.scrollTop = el.scrollHeight;
          return;
        }

        el = el.parentElement;
      }
    }

    window.scrollBy(0, 2500);
  });
}

async function openReviewsTab(page) {
  const tab = page.getByRole('tab', { name: /отзыв/i }).first();
  if (await tab.count()) {
    try {
      await tab.click({ timeout: 4000 });
      await sleep(2000);
      return;
    } catch {
      // вкладка уже открыта
    }
  }

  const reviewsLink = page.getByText(/отзыв/i).first();
  if (await reviewsLink.count()) {
    try {
      await reviewsLink.click({ timeout: 5000 });
      await sleep(2500);
    } catch {
      // отзывы уже открыты
    }
  }
}

function ingestFetchReviewsPayload(reviews, organization, payload) {
  const responseReviews = extractReviews(payload);

  for (const review of responseReviews) {
    if (!review?.reviewId) {
      continue;
    }

    reviews.set(String(review.reviewId), normalizeReview(review));
  }

  applyOrganizationPayload(organization, payload);

  return {
    responseReviews,
    pagination: extractPagination(payload),
  };
}

async function collectReviewsByScrolling(page, reviews) {
  let previousReviewCount = -1;
  let unchangedIterations = 0;

  for (let i = 0; i < 100; i += 1) {
    await scrollReviewsPanel(page);
    await page.mouse.wheel(0, 2500);
    await sleep(600);

    const moreButtons = page.getByText(/показать ещё/i);
    if (await moreButtons.count()) {
      try {
        await moreButtons.last().click({ timeout: 2000 });
        await sleep(1800);
      } catch {
        // кнопка могла исчезнуть
      }
    }

    if (reviews.size === previousReviewCount) {
      unchangedIterations += 1;
    } else {
      unchangedIterations = 0;
    }

    previousReviewCount = reviews.size;

    if (unchangedIterations >= 5) {
      break;
    }
  }
}

function mergeOrganization(organization, parsed) {
  if (parsed.businessId && !organization.businessId) {
    organization.businessId = String(parsed.businessId);
  }

  if (parsed.name && !organization.name) {
    organization.name = String(parsed.name);
  }

  if (parsed.rating != null && organization.rating == null) {
    organization.rating = Number(parsed.rating);
  }

  if (parsed.ratingsCount != null && organization.ratingsCount == null) {
    organization.ratingsCount = Number(parsed.ratingsCount);
  }

  if (parsed.reviewsCount != null && organization.reviewsCount == null) {
    organization.reviewsCount = Number(parsed.reviewsCount);
  }
}

function ingestRawReviews(reviews, rawReviews) {
  for (const review of rawReviews) {
    if (!review?.reviewId) {
      continue;
    }

    reviews.set(String(review.reviewId), normalizeReview(review));
  }
}

async function fetchHtmlCardPage(context, url) {
  const response = await context.request.get(url, {
    timeout: 25_000,
    headers: {
      accept: 'text/html,application/xhtml+xml',
      'accept-language': 'ru-RU,ru;q=0.9',
    },
  });

  if (response.status() === 403) {
    const error = new Error('Yandex returned 403.');
    error.code = 'BLOCKED';
    throw error;
  }

  if (!response.ok()) {
    throw new Error(`reviews card page failed: HTTP ${response.status()}`);
  }

  const html = await response.text();

  if (looksLikeCaptcha(html)) {
    const error = new Error('Yandex returned a captcha challenge.');
    error.code = 'BLOCKED';
    throw error;
  }

  return html;
}

async function collectReviewsFromHtmlPages({
  context,
  mapsUrl,
  businessId,
  reviews,
  organization,
}) {
  const firstHtml = await fetchHtmlCardPage(context, reviewsCardPageUrl(mapsUrl, businessId, 1));
  const first = extractStateFromHtml(firstHtml);

  mergeOrganization(organization, first.organization);
  ingestRawReviews(reviews, first.reviews);
  log(`html page=1: ${first.reviews.length}, total: ${reviews.size}`);

  const knownCount = organization.reviewsCount;
  const totalPages = knownCount === 0 && first.reviews.length === 0
    ? 1
    : htmlReviewPageCount(knownCount || 600);
  const pages = Array.from({ length: Math.max(totalPages - 1, 0) }, (_, index) => index + 2);

  await mapWithConcurrency(pages, HTTP_PAGE_CONCURRENCY, async (pageNumber) => {
    const html = await fetchHtmlCardPage(
      context,
      reviewsCardPageUrl(mapsUrl, businessId, pageNumber),
    );
    const extracted = extractStateFromHtml(html);
    mergeOrganization(organization, extracted.organization);
    ingestRawReviews(reviews, extracted.reviews);
    log(`html page=${pageNumber}: ${extracted.reviews.length}, total: ${reviews.size}`);
  });

  return totalPages;
}

export async function launchBrowser() {
  const launchOptions = {
    headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage', '--disable-gpu'],
  };

  const channels = process.env.PLAYWRIGHT_BROWSERS_PATH
    ? [undefined]
    : [
        process.env.PLAYWRIGHT_CHANNEL,
        'chrome',
        'msedge',
        undefined,
      ].filter((value, index, list) => list.indexOf(value) === index);

  let lastError;

  for (const channel of channels) {
    try {
      const browser = await chromium.launch({
        ...launchOptions,
        ...(channel ? { channel } : {}),
      });

      log(`browser_started: ${channel ?? 'bundled-chromium'}`);
      return browser;
    } catch (error) {
      lastError = error;
      log(`Failed to launch ${channel ?? 'bundled-chromium'}: ${error.message}`);
    }
  }

  throw lastError ?? new Error('Unable to launch Chromium');
}

export async function parseOrganization(mapsUrl, options = {}) {
  if (!mapsUrl) {
    throw new Error('Yandex Maps URL is required');
  }

  const browser = options.browser ?? await launchBrowser();
  const ownsBrowser = options.browser == null;
  const context = await browser.newContext({
    locale: 'ru-RU',
    viewport: {
      width: 1440,
      height: 1000,
    },
    userAgent:
      'Mozilla/5.0 (Windows NT 10.0; Win64; x64) ' +
      'AppleWebKit/537.36 (KHTML, like Gecko) ' +
      'Chrome/122.0.0.0 Safari/537.36',
  });

  const page = await context.newPage();
  const reviews = new Map();
  const organization = {
    businessId: extractBusinessIdFromUrl(mapsUrl),
    name: null,
    rating: null,
    ratingsCount: null,
    reviewsCount: null,
  };

  let reviewsRequests = 0;
  let fetchReviewsPagination = null;
  let collectionMode = 'none';
  let htmlPages = 0;

  page.on('response', async (response) => {
    const responseUrl = response.url();

    if (
      !responseUrl.includes('/maps/api/business/') &&
      !responseUrl.includes('/maps/api/search/')
    ) {
      return;
    }

    let payload;
    try {
      payload = await response.json();
    } catch {
      return;
    }

    if (responseUrl.includes('/maps/api/business/fetchReviews')) {
      const { responseReviews, pagination } = ingestFetchReviewsPayload(
        reviews,
        organization,
        payload,
      );

      if (pagination.totalPages > 0) {
        fetchReviewsPagination = pagination;
      }

      reviewsRequests += 1;
      log(`fetchReviews: ${responseReviews.length}, total: ${reviews.size}`);
      return;
    }

    applyOrganizationPayload(organization, payload);
  });

  try {
    log(`Opening: ${mapsUrl}`);

    await page.goto(mapsUrl, {
      waitUntil: 'domcontentloaded',
      timeout: 60_000,
    });

    await sleep(1500);

    const looksComplete = () => {
      const expected = organization.reviewsCount ?? 0;
      return expected === 0
        ? reviews.size > 0 || collectionMode === 'html'
        : reviews.size >= expected * 0.8;
    };

    if (organization.businessId) {
      try {
        htmlPages = await collectReviewsFromHtmlPages({
          context,
          mapsUrl,
          businessId: organization.businessId,
          reviews,
          organization,
        });
        collectionMode = 'html';
      } catch (error) {
        if (error.code === 'BLOCKED') {
          throw error;
        }

        log(`html pagination failed, falling back to scroll: ${error.message}`);
      }
    }

    if (!looksComplete()) {
      await openReviewsTab(page);
      await collectReviewsByScrolling(page, reviews);
      collectionMode = collectionMode === 'html' ? 'html+scroll' : 'scroll';
    }

    try {
      const title = await page.title();
      if (title && !organization.name) {
        organization.name = title
          .replace(/\s*[—|-]\s*Яндекс.?Карты.*$/i, '')
          .trim();
      }
    } catch {
      // ignore
    }

    if (!organization.businessId) {
      const requestUrls = await page.evaluate(() =>
        performance.getEntriesByType('resource').map((entry) => entry.name),
      );

      const apiUrl = requestUrls.find((entry) =>
        entry.includes('/maps/api/business/'),
      );

      const match = apiUrl?.match(/businessId=(\d+)/);
      if (match) {
        organization.businessId = match[1];
      }
    }

    if (collectionMode === 'none' && reviewsRequests === 0) {
      const error = new Error(
        'Yandex returned no fetchReviews responses. Markup or internal API may have changed.',
      );
      error.code = 'STRUCTURE_CHANGED';
      throw error;
    }

    return {
      success: true,
      organization,
      reviews: [...reviews.values()],
      meta: {
        reviewsCollected: reviews.size,
        reviewsRequests,
        collectionMode,
        htmlPages,
        totalPages: htmlPages || fetchReviewsPagination?.totalPages || null,
        url: mapsUrl,
      },
    };
  } finally {
    await context.close();

    if (ownsBrowser) {
      await browser.close();
    }
  }
}

async function main() {
  const mapsUrl = process.argv[2];

  if (!mapsUrl) {
    console.log(JSON.stringify({
      success: false,
      error: 'Yandex Maps URL is required',
    }));
    process.exitCode = 1;
    return;
  }

  try {
    const result = await parseOrganization(mapsUrl);
    console.log(JSON.stringify(result));
  } catch (error) {
    console.log(JSON.stringify({
      success: false,
      error: {
        code: error.code ?? 'PARSER_ERROR',
        message: error.message,
        name: error.name,
      },
    }));
    process.exitCode = 1;
  }
}

const isCli = process.argv[1] && fileURLToPath(import.meta.url) === path.resolve(process.argv[1]);

if (isCli) {
  await main();
}
