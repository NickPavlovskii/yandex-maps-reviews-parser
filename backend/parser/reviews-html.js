const MAX_HTML_PAGES = 13;

export function reviewsCardPageUrl(mapsUrl, businessId, page = 1) {
  const origin = new URL(mapsUrl).origin;
  const url = new URL(`/maps/org/${encodeURIComponent(businessId)}/reviews/`, `${origin}/`);

  if (page > 1) {
    url.searchParams.set('page', String(page));
  }

  return url.toString();
}

export function extractJsonScriptBlocks(html) {
  return [...html.matchAll(/<script[^>]*type="application\/json"[^>]*>([\s\S]*?)<\/script>/gi)]
    .map((match) => match[1].trim())
    .filter((block) => block !== '');
}

function parseJsonBlock(block) {
  try {
    return JSON.parse(block);
  } catch {
    try {
      return JSON.parse(block.replace(/&quot;/g, '"').replace(/&amp;/g, '&'));
    } catch {
      return null;
    }
  }
}

export function readRating(value) {
  if (value == null || value === '') {
    return { rating: null, ratingsCount: null };
  }

  if (typeof value === 'number' && Number.isFinite(value)) {
    return { rating: value, ratingsCount: null };
  }

  if (typeof value === 'string' && value.trim() !== '' && Number.isFinite(Number(value))) {
    return { rating: Number(value), ratingsCount: null };
  }

  if (typeof value === 'object') {
    const rating = value.ratingValue ?? value.value ?? value.score ?? (
      typeof value.rating === 'number' || typeof value.rating === 'string'
        ? value.rating
        : null
    );
    const ratingsCount =
      value.ratingCount ??
      value.ratingsCount ??
      value.count ??
      value.ratings ??
      null;

    return {
      rating: rating != null && Number.isFinite(Number(rating)) ? Number(rating) : null,
      ratingsCount:
        ratingsCount != null && Number.isFinite(Number(ratingsCount))
          ? Number(ratingsCount)
          : null,
    };
  }

  return { rating: null, ratingsCount: null };
}

export function extractAspects(payload) {
  if (!payload || typeof payload !== 'object') {
    return [];
  }

  const data = payload.data ?? payload;
  const company = data.company ?? data.organization ?? data;
  const candidates = [
    payload.aspects,
    data.aspects,
    company.aspects,
    data.reviewAspects,
    company.reviewAspects,
  ];
  const raw = candidates.find((item) => Array.isArray(item) && item.length > 0) ?? [];
  const aspects = [];
  const seen = new Set();

  for (const item of raw) {
    if (!item || typeof item !== 'object') {
      continue;
    }

    const text = String(item.text ?? item.name ?? item.title ?? '').trim();

    if (text === '' || seen.has(text)) {
      continue;
    }

    seen.add(text);
    aspects.push({
      text,
      count: Number(item.count ?? item.reviewsCount ?? 0) || 0,
      positive: Number(item.positive ?? item.positives ?? item.positiveCount ?? 0) || 0,
      negative: Number(item.negative ?? item.negatives ?? item.negativeCount ?? 0) || 0,
    });
  }

  return aspects;
}

function walkState(node, onReviewList, onOrganization) {
  if (!node || typeof node !== 'object') {
    return;
  }

  if (Array.isArray(node)) {
    if (node[0]?.reviewId) {
      onReviewList(node);
      return;
    }

    for (const item of node) {
      walkState(item, onReviewList, onOrganization);
    }

    return;
  }

  if (node.ratingData && typeof node.ratingData === 'object') {
    const parsed = readRating(node.ratingData);
    onOrganization({
      name: node.name ?? node.title ?? null,
      rating: parsed.rating,
      ratingsCount: parsed.ratingsCount,
      reviewsCount: node.ratingData.reviewCount ?? node.ratingData.reviewsCount ?? null,
      businessId: node.businessId ?? node.id ?? null,
    });
  }

  if (node.aggregateRating && typeof node.aggregateRating === 'object') {
    const parsed = readRating(node.aggregateRating);
    onOrganization({
      name: node.name ?? node.title ?? null,
      rating: parsed.rating,
      ratingsCount: parsed.ratingsCount,
      reviewsCount: node.aggregateRating.reviewCount ?? node.aggregateRating.reviewsCount ?? null,
      businessId: node.businessId ?? node.id ?? null,
    });
  }

  if (node.rating && typeof node.rating === 'object' && (node.businessId || node.title || node.name)) {
    const parsed = readRating(node.rating);
    onOrganization({
      name: node.name ?? node.title ?? null,
      rating: parsed.rating,
      ratingsCount: parsed.ratingsCount,
      reviewsCount: node.reviewsCount ?? node.reviewCount ?? null,
      businessId: node.businessId ?? node.id ?? null,
    });
  }

  if (Array.isArray(node.reviews) && node.reviews[0]?.reviewId) {
    onReviewList(node.reviews);
  }

  if (Array.isArray(node.reviewResults?.reviews)) {
    onReviewList(node.reviewResults.reviews);
  }

  for (const value of Object.values(node)) {
    if (value && typeof value === 'object') {
      walkState(value, onReviewList, onOrganization);
    }
  }
}

export function extractStateFromHtml(html) {
  const reviews = [];
  const seen = new Set();
  const organization = {
    businessId: null,
    name: null,
    rating: null,
    ratingsCount: null,
    reviewsCount: null,
  };

  for (const block of extractJsonScriptBlocks(html)) {
    const data = parseJsonBlock(block);
    if (!data) {
      continue;
    }

    walkState(
      data,
      (list) => {
        for (const review of list) {
          if (!review?.reviewId || seen.has(String(review.reviewId))) {
            continue;
          }

          seen.add(String(review.reviewId));
          reviews.push(review);
        }
      },
      (parsed) => {
        organization.businessId ??= parsed.businessId ? String(parsed.businessId) : null;
        organization.name ??= parsed.name ? String(parsed.name) : null;
        organization.rating ??= parsed.rating != null ? Number(parsed.rating) : null;
        organization.ratingsCount ??= parsed.ratingsCount != null ? Number(parsed.ratingsCount) : null;
        organization.reviewsCount ??= parsed.reviewsCount != null ? Number(parsed.reviewsCount) : null;
      },
    );
  }

  return { reviews, organization };
}

export function htmlReviewPageCount(reviewsCount, pageSize = 50) {
  const count = Number(reviewsCount) || 0;
  if (count <= 0) {
    return 1;
  }

  return Math.min(Math.ceil(count / pageSize), MAX_HTML_PAGES);
}

export function looksLikeCaptcha(html) {
  return /smartcaptcha|showcaptcha|checkcaptcha|captcha\.yandex/i.test(html)
    && !html.includes('"reviewId"');
}
