# rest-api-mvc-php — application image (PHP-FPM)
#
# Redo of the C1 Docker deliverable per DEC-R1-001 review findings. The
# original files were reviewed (2 blocking issues found) but never actually
# landed in this repo/git history; this is a from-scratch rebuild that
# incorporates the fixes rather than re-attempting the same bugs.
#
# B1 fix (R1 review): `netcat-openbsd` is not in php:*-alpine by default —
#   the old entrypoint's MySQL-wait loop (`nc -z mysql 3306`) would have
#   failed immediately, forcing the container to exit. Installed below.
# B2 fix (R1 review), reassessed rather than patched: this app does NOT use
#   Composer's autoloader at runtime — app/bootstrap.php registers its own
#   spl_autoload_register(), and composer.json's only real package
#   (phpunit) is require-dev (test-only). Installing a composer binary into
#   the runtime image and running `composer install` there — as B2
#   originally suggested — would "fix" a step that has nothing real to
#   install. The actual bug was attempting it at all; the entrypoint below
#   no longer does.
#
# R91/R92/R102: multi-stage-shaped (single stage is sufficient here — no
# compiled/vendored artifacts to build), non-root USER, no secrets baked in
# (all config via env / .env, see .env.example), HEALTHCHECK below,
# resource limits declared at the compose level (R60/R64).

# syntax=docker/dockerfile:1

ARG PHP_IMAGE=php:8.1-fpm-alpine3.19
# Tag-pinned, not digest-pinned: this build environment has no live registry
# access to resolve today's sha256 for PHP_IMAGE. Pin explicitly before
# production use:
#   docker inspect --format='{{index .RepoDigests 0}}' php:8.1-fpm-alpine3.19
# and set PHP_IMAGE=php:8.1-fpm-alpine3.19@sha256:<digest>.

FROM ${PHP_IMAGE} AS runtime
LABEL org.opencontainers.image.title="rest-api-mvc-php"
LABEL org.opencontainers.image.description="PHP MVC REST API — JWT auth + OTP verification"

ARG APP_VERSION=dev
ENV APP_VERSION=${APP_VERSION}

# netcat-openbsd : B1 fix — MySQL-wait loop in entrypoint.sh
# fcgi           : provides cgi-fcgi, used by HEALTHCHECK to speak FastCGI
#                  directly to php-fpm (wget/curl can't — FastCGI isn't HTTP)
# pdo_mysql      : required by app/libraries/Database.php (PDO + MySQL),
#                  documented as a prerequisite in docs/installation.md
RUN apk add --no-cache netcat-openbsd fcgi \
    && docker-php-ext-install pdo pdo_mysql

# app/libraries/Core.php uses CWD-relative paths (`require_once('../app/...')`),
# written for a webserver that chdir's into the script's own directory
# (Apache/mod_php and `php -S` both do this by default — see
# docs/installation.md's dev-server instructions). php-fpm does not do this
# implicitly for every pool config, so it's pinned explicitly here rather
# than silently depending on FPM defaults.
RUN echo "chdir = /var/www/html/api" > /usr/local/etc/php-fpm.d/zz-chdir.conf

WORKDIR /var/www/html

COPY api ./api
COPY app ./app
COPY health.php ./health.php
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh \
    && chown -R www-data:www-data /var/www/html

USER www-data

# Talks FastCGI directly to php-fpm on its own listen port — see the Dockerfile
# comment on `fcgi` above for why this can't be a plain HTTP wget/curl check.
HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 \
  CMD SCRIPT_NAME=/health.php SCRIPT_FILENAME=/var/www/html/health.php REQUEST_METHOD=GET \
      cgi-fcgi -bind -connect 127.0.0.1:9000 || exit 1

EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm", "-F"]
