#!/bin/sh
# rest-api-mvc-php — container entrypoint
#
# B1 fix (DEC-R1-001): the MySQL-wait loop below now works — netcat-openbsd
# is installed in the Dockerfile (it wasn't before, so this loop used to
# fail immediately and the container exited before php-fpm ever started).
#
# B2 (DEC-R1-001), reassessed: no `composer install` step here. This app's
# runtime autoloading is app/bootstrap.php's own spl_autoload_register(),
# not Composer's. Composer/vendor/ is only used for `composer test`
# (phpunit, a dev/test dependency) — never at container start. Running it
# here would have nothing real to install and was the actual bug, not a
# missing composer binary.

set -e

DB_WAIT_HOST="${DB_HOST:-mysql}"
DB_WAIT_PORT="${DB_PORT:-3306}"

echo "entrypoint: waiting for MySQL at ${DB_WAIT_HOST}:${DB_WAIT_PORT}..."
i=0
until nc -z "$DB_WAIT_HOST" "$DB_WAIT_PORT" 2>/dev/null; do
  i=$((i + 1))
  if [ "$i" -ge 60 ]; then
    echo "entrypoint: MySQL not reachable after 60s, starting anyway (app will error per-request until it is)." >&2
    break
  fi
  sleep 1
done
echo "entrypoint: proceeding."

exec "$@"
