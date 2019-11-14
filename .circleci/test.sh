#!/usr/bin/env bash
##
# Run tests.
#
set -e

MODULE=$(basename -s .info.yml -- ./*.info.yml)

# @todo: Enable code linting.
#echo "==> Lint code"
#build/vendor/bin/phpcs -s --standard=Drupal,DrupalPractice "build/web/modules/${MODULE}" || true

echo "==> Run tests"
mkdir -p /tmp/test_results/simpletest
rm -f /tmp/test.sqlite
# @todo: Remove the '--suppress-deprecations' switch.
php ./build/web/core/scripts/run-tests.sh \
  --sqlite /tmp/test.sqlite \
  --dburl sqlite://localhost//tmp/test.sqlite \
  --url http://localhost:8000 \
  --non-html \
  --xml /tmp/test_results/simpletest \
  --color \
  --suppress-deprecations \
  --verbose \
  --module "${MODULE}"
