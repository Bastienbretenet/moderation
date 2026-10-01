#!/bin/sh
set -e

if [ ! -d node_modules ]; then
    npm ci --no-fund --no-audit
fi

exec "$@"
