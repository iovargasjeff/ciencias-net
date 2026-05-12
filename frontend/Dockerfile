FROM node:20-alpine AS builder

ARG VITE_API_URL=https://api.cienciascolegio.pe
ENV VITE_API_URL=${VITE_API_URL}

WORKDIR /app

COPY package.json package-lock.json* ./

RUN npm ci

COPY . .

RUN npm run build

FROM nginx:alpine AS serve

COPY --from=builder /app/dist /usr/share/nginx/html

COPY nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
