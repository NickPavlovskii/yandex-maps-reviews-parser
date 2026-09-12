FROM mcr.microsoft.com/playwright:v1.63.0-noble

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --omit=dev

COPY yandex-parser.js server.js ./

ENV PORT=3000
EXPOSE 3000

USER root
CMD ["node", "server.js"]
