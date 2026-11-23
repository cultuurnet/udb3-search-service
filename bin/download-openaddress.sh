#!/bin/sh
# Downloads the BeST OpenAddress CSVs (about 1 GB unzipped) for the three regions.
# Usage: bin/download-openaddress.sh [--skip-existing] [target-dir]
set -eu

SKIP_EXISTING=false
if [ "${1:-}" = "--skip-existing" ]; then
  SKIP_EXISTING=true
  shift
fi

TARGET_DIR=${1:-data}

mkdir -p "$TARGET_DIR"

for region in bevlg bewal bebru; do
  if [ "$SKIP_EXISTING" = true ] && [ -f "$TARGET_DIR/openaddress-$region.csv" ]; then
    echo "Skipping openaddress-$region, the file already exists."
    continue
  fi

  echo "Downloading openaddress-$region..."
  curl -fL --progress-bar -o "$TARGET_DIR/openaddress-$region.zip" "https://opendata.bosa.be/download/best/openaddress-$region.zip"
  unzip -oq "$TARGET_DIR/openaddress-$region.zip" -d "$TARGET_DIR"
  rm "$TARGET_DIR/openaddress-$region.zip"
done
