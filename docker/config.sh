#!/bin/sh

UPDATE_HOSTS=${HAS_SUDO:-true}

if [ "$UPDATE_HOSTS" = "true" ] && ! grep -q "search.uitdatabank.local" /etc/hosts; then
  echo "search.uitdatabank.local has to be in your hosts-file, to add you need sudo privileges"
  sudo sh -c 'echo "127.0.0.1 search.uitdatabank.local" >> /etc/hosts'
fi

APPCONFIG_ROOTDIR=${APPCONFIG:-'../appconfig'}
GEOJSON_DATA_ROOTDIR=${GEOJSON_DATA:-'../geojson-data'}

DIR="${APPCONFIG_ROOTDIR}/files/uitdatabank/docker/udb3-search-service/"
if [ -d "$DIR" ]; then
  cp -R "$DIR"/* .
else
  echo "Error: missing appconfig. The appconfig repository must be cloned at ${APPCONFIG_ROOTDIR}."
  exit 1
fi

DIR="${APPCONFIG_ROOTDIR}/files/uitdatabank/docker/keys/"
if [ -d "$DIR" ]; then
  cp -R "$DIR"/* .
else
  echo "Error: missing appconfig. The appconfig repository must be cloned at ${APPCONFIG_ROOTDIR}."
  exit 1
fi

DIR="${GEOJSON_DATA_ROOTDIR}/output"
if [ -d "$DIR" ]; then
  cp "$DIR"/facet_mapping_regions.yml .
else
  echo "Error: missing geojson-data. The geojson-data repository must be cloned at ${GEOJSON_DATA_ROOTDIR}."
  exit 1
fi
