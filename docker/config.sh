#!/bin/sh

# Add host.docker.internal to /etc/hosts
if ! grep -q "host.docker.internal" /etc/hosts; then
  echo "host.docker.internal has to be in your hosts-file, to add you need sudo privileges"
  sudo sh -c 'echo "127.0.0.1 host.docker.internal" >> /etc/hosts'
fi

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