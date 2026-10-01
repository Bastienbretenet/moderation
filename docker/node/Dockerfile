FROM node:24-alpine

WORKDIR /app

COPY --chmod=755 docker-entrypoint.sh /usr/local/bin/docker-entrypoint

USER node

ENTRYPOINT ["docker-entrypoint"]
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
