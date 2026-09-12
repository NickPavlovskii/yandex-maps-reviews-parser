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
    onOrganization({
      name: node.name ?? node.title ?? null,
      rating: node.ratingData.ratingValue ?? null,
      ratingsCount: node.ratingData.ratingCount ?? null,
      reviewsCount: node.ratingData.reviewCount ?? null,
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
