#!/bin/sh

DIR="../udb3-vagrant/config/"
if [ -d "$DIR" ]; then
  cp "$DIR"/udb3-search-service/config.yml .
  cp "$DIR"/udb3-search-service/facet_mapping_facilities.yml .
  cp "$DIR"/udb3-search-service/facet_mapping_themes.yml .
  cp "$DIR"/udb3-search-service/facet_mapping_types.yml .
  cp "$DIR"/udb3-search-service/features.yml .
  cp "$DIR"/keys/public-auth0.pem .

  sed -i '' 's!http://elasticsearch.dev:9200!elasticsearch!g' config.yml
  sed -i '' 's!127.0.0.1!host.docker.internal!g' config.yml
else
  echo "Error: missing udb3-vagrant see docker.md prerequisites to fix this."
  exit 1
fi

DIR="../geojson-data/output/"
if [ -d "$DIR" ]; then
  cp "$DIR"/facet_mapping_regions.yml .
else
  echo "Error: missing geojson data see docker.md prerequisites to fix this."
  exit 1
fi