# UDB3 Search Service with Docker

Local development runs the same shape the servers do: `php-fpm` behind an nginx sidecar, with the AMQP consumers as separate containers.

`docker-compose.yml` describes that shape and is what the acceptance-test pipeline runs on its own, against the image published to ECR.
`docker-compose.override.yml`, which Compose loads automatically, adapts it for development: it builds the image from this repo's Dockerfile rather than pulling it and mounts your working tree into it.

The pipeline publishes an amd64 image for the servers and nothing else, which is
why development builds its own. Both come from the same Dockerfile: development
builds the `dev` target, which is the production image's own runtime stage plus
composer, so the PHP build, extensions and ini settings match what runs when
deployed. What development does not get is the published artifact itself, byte for
byte — note that `php:8.1-fpm` is a moving base tag, so a fresh local build can
carry newer upstream packages than an image built weeks ago. The acceptance tests
do run the real artifact.

## Prerequisites

- Install [Docker Desktop](https://www.docker.com/products/docker-desktop/)
- Clone [geojson-data](https://github.com/cultuurnet/geojson-data) and
  [appconfig](https://github.com/cultuurnet/appconfig) in the same folder as this repo

No ECR access is needed for local development.

## Configure

Run `make config`. This copies the configuration files out of `appconfig` and `geojson-data` into the root of the project, and adds `search uitdatabank.local` to your `/etc/hosts` if it is missing (which needs `sudo`).

### RabbitMQ

Login to the management console on http://host.docker.internal:15672/ with username`vagrant` and password `vagrant`

## Migration
Run `make migrate`

## Start

```
$ make up        # builds the image if absent, waits until the stack is healthy
$ make install   # installs composer dependencies, including dev, into vendor/
$ make migrate   # runs the Elasticsearch migrations
$ make down
```

The service answers on http://localhost:8080, and on http://search.uitdatabank.local from other containers on the `uitdatabank` network.
Elasticsearch is on http://localhost:9200.

`make build` rebuilds the image and recreates the containers on it. It is only needed after a change to the Dockerfile, since the application code and `vendor/` are bind-mounted, so editing PHP needs nothing, and changing a dependency needs `make install`, not a rebuild. On a fresh clone you do not need it either: `make up` builds the image when it is missing. Add `--pull` to also pick up a newer `php:8.1-fpm` base.

To run the published artifact locally instead of your own build (e.g. to reproduce
something that only happens with a deployed image) skip the override file and give
it a tag:

## Acceptance-test stack

The acceptance tests run the same base file, but against the Dockerfile's
*production* target — vendor/ baked in with `--no-dev`, no composer, no source
mount. The image is built from the checkout rather than pulled, so whichever branch
the pipeline was given is the code under test:

```
$ make acc-test-build
$ make acc-test-up
$ make acc-test-migrate
$ make acc-test-logs
$ make acc-test-down
```

On main that build is equivalent to the image in ECR — same Dockerfile, same target,
same commit, and `composer.lock` pins every PHP dependency — but not identical to
it: `php:8.1-fpm` is a mutable tag and the `apt`/`pecl` installs are unpinned, so a
later build can carry newer upstream packages.

To run a published artifact instead, point `SEARCH_IMAGE` at an ECR tag and skip the
build step — `acc-test-up` pulls what is not already present:

```
$ aws ecr get-login-password --region eu-west-1 \
    | docker login --username AWS --password-stdin 757200591793.dkr.ecr.eu-west-1.amazonaws.com
$ export SEARCH_IMAGE=757200591793.dkr.ecr.eu-west-1.amazonaws.com/uitdatabank/search-api:acceptance
$ make acc-test-up
```

Those tags are mutable, so to refresh one you already have locally:
`docker compose -f docker-compose.yml pull`.

## Logs

The containers log to stdout, as they do when deployed, so there are no
`log/<channel>.log` files to tail:

```
$ make logs
```

## Tests and QA

These run inside the `search` container, and need `make install` to have put the dev dependencies in `vendor/`. 

```
$ make test
```
