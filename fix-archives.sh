#!/usr/bin/env bash
##
# Cleanup ZIP files to use with Minisite Drupal module.
#
# Usage:
# ./fix-archives.sh dir/with/zips dir/to/output/fixed

set -e

# The directory with source ZIP files.
SRC_DIR="${1}"

# The directory to output processed files. If not provided - $SRC_DIR will be used.
DST_DIR="${2:-${1}}"

#The prefix to use for every processed file.
DST_PREFIX="${3:-}"

# ------------------------------------------------------------------------------

[ "$(command_exists zip)" == "1" ] && error "Please install Zip" && exit 1
[ "$(command_exists unzip)" == "1" ] && error "Please install Unzip" && exit 1


TMP_DIR="${SRC_DIR}/tmp"

mkdir -p "${DST_DIR}"

for zipfile in "$SRC_DIR"/*.zip; do
  echo "==> Processing ${zipfile}"

  rm -Rf "${TMP_DIR}" && mkdir -p "${TMP_DIR}"

  unzip -q "${zipfile}" -d "${TMP_DIR}"

  pushd "${TMP_DIR}" > /dev/null

  # Check that there is only one parent dir.
  if [ "$(ls|wc -l)" -gt 1 ]; then
    echo "ERROR: ${zipfile} does not contain top-level directory. Re-zip manually and repeat." && exit 1
  fi

  tmp_src="$(ls | head -n 1)"

  # Check that index.html is provided.
  if [ ! -f "${tmp_src}/index.html" ]; then
    echo "ERROR: Top-level directory in archive does not contain index.html file." && exit 1
  fi

  archive="${DST_PREFIX}${tmp_src}"

  zip -q -r "${DST_DIR}/${archive}" "${tmp_src}" -i "*.html" "*.htm" "*.js" "*.css" "*.png" "*.jpg" "*.gif" "*.svg" "*.pdf" "*.doc" "*.docx" "*.ppt" "*.pptx" "*.xls" "*.xlsx" "*.tif" "*.xml" "*.txt" "*.woff" "*.woff2" "*.ttf" "*.eot" "*.ico" "*.mp4"

  popd > /dev/null

  echo "==> Created new archive ${DST_DIR}/${archive}"
  echo

  rm -Rf "${TMP_DIR}"
done
