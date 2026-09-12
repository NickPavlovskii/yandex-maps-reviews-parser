import http from 'node:http';
import { launchBrowser, parseOrganization } from './yandex-parser.js';

const port = Number(process.env.PORT ?? 3000);

let browserPromise;

function getBrowser() {
  if (!browserPromise) {
    browserPromise = launchBrowser().catch((error) => {
      browserPromise = undefined;
      throw error;
    });
  }

  return browserPromise;
}

function readJsonBody(req) {
  return new Promise((resolve, reject) => {
    let body = '';

    req.on('data', (chunk) => {
      body += chunk;

      if (body.length > 1_000_000) {
        reject(new Error('Request body is too large'));
      }
    });

    req.on('end', () => {
      if (body.trim() === '') {
        resolve({});
        return;
      }

      try {
        resolve(JSON.parse(body));
      } catch {
        reject(new Error('Invalid JSON'));
      }
    });

    req.on('error', reject);
  });
}

function sendJson(res, status, payload) {
  res.writeHead(status, { 'Content-Type': 'application/json' });
  res.end(JSON.stringify(payload));
}

const server = http.createServer(async (req, res) => {
  if (req.method === 'GET' && req.url === '/health') {
    sendJson(res, 200, { ok: true });
    return;
  }

  if (req.method !== 'POST' || req.url !== '/parse') {
    sendJson(res, 404, { success: false, error: 'Not found' });
    return;
  }

  try {
    const payload = await readJsonBody(req);
    const url = typeof payload.url === 'string' ? payload.url : '';

    if (url === '') {
      sendJson(res, 422, { success: false, error: 'URL is required' });
      return;
    }

    const browser = await getBrowser();
    const result = await parseOrganization(url, { browser });
    sendJson(res, 200, result);
  } catch (error) {
    const status = error.code === 'STRUCTURE_CHANGED' || error.code === 'BLOCKED'
      ? 422
      : 500;

    sendJson(res, status, {
      success: false,
      error: {
        code: error.code ?? 'PARSER_ERROR',
        message: error instanceof Error ? error.message : 'Parser failed',
      },
    });
  }
});

server.requestTimeout = 360_000;
server.headersTimeout = 360_000;
server.timeout = 360_000;

server.listen(port, '0.0.0.0', () => {
  console.error(`parser listening on ${port}`);
});
