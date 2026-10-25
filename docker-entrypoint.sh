#!/bin/sh
set -eu
PORT="${PORT:-10000}"
# Render assigns PORT at runtime; make Apache listen on that port.
sed -i "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
# Use the persistent Render disk when DB_PATH is set (recommended).
if [ -n "${DB_PATH:-}" ]; then
  DB_DIR="$(dirname "$DB_PATH")"
  mkdir -p "$DB_DIR"
  chown -R www-data:www-data "$DB_DIR"
fi
exec apache2-foreground
