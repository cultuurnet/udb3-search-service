#!/bin/sh

if ! grep -q "host.docker.internal" /etc/hosts; then
  echo "host.docker.internal has to be in your hosts-file, to add you need sudo privileges"
  sudo sh -c 'echo "127.0.0.1 host.docker.internal" >> /etc/hosts'
fi

DIR="../appconfig/files/uitdatabank/docker/udb3-search-service/"
if [ -d "$DIR" ]; then
  cp -R "$DIR"/* .
else
  echo "Error: missing appconfig. The appconfig and udb3-search-service repositories must be cloned into the same parent folder."
  exit 1
fi

DIR="../geojson-data/output"
if [ -d "$DIR" ]; then
  cp "$DIR"/facet_mapping_regions.yml .
else
  echo "Error: missing geojson data. See docker.md prerequisites to fix this."
  exit 1
fi

DIR="../appconfig/files/uitdatabank/docker/keys/"
if [ -d "$DIR" ]; then
  cp -R "$DIR"/* .
else
  echo "Error: missing appconfig. The appconfig and udb3-search-service repositories must be cloned into the same parent folder."
  exit 1
fi