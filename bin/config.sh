#!/bin/sh

DIR="../appconfig/files/udb3/docker"
if [ -d "$DIR" ]; then
  cp -R "$DIR"/udb3-search-service/* .
  cp "$DIR"/udb3-backend/public-auth0.pem .
  else
  echo "Error: missing appconfig see docker.md prerequisites to fix this."
  exit 1
fi

DIR="../geojson-data/output"
if [ -d "$DIR" ]; then
  cp "$DIR"/facet_mapping_regions.yml .
else
  echo "Error: missing geojson data see docker.md prerequisites to fix this."
  exit 1
fi