#!/bin/sh

DIR="../appconfig/files/udb3/docker"
if [ -d "$DIR" ]; then
  cp "$DIR"/udb3-search-service/config.yml .
  cp "$DIR"/udb3-search-service/facet_mapping_facilities.yml .
  cp "$DIR"/udb3-search-service/facet_mapping_themes.yml .
  cp "$DIR"/udb3-search-service/facet_mapping_types.yml .
  cp "$DIR"/udb3-search-service/features.yml .
  cp "$DIR"/udb3-backend/public-auth0.pem .
  else
  echo "Error: missing udb3-vagrant see docker.md prerequisites to fix this."
  exit 1
fi

DIR="../geojson-data/output"
if [ -d "$DIR" ]; then
  cp "$DIR"/facet_mapping_regions.yml .
else
  echo "Error: missing geojson data see docker.md prerequisites to fix this."
  exit 1
fi