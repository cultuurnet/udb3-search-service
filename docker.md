# UDB3 Search Service with Docker

## Prerequisite
- Install Docker Desktop 
- GEO JSON Data: you'll have to clone [geojson-data](https://github.com/cultuurnet/geojson-data) in the same folder as where you will clone [udb3-search-service](https://github.com/cultuurnet/udb3-search-service)

## Configure

### Configuration files.

You will have to copy a couple of configuration yml files, place them in the root of the udb3-search-service project.

Copy the latest `config.yml` from https://github.com/cultuurnet/udb3-vagrant/tree/main/config/udb3-search-service to the root

In your `config.yml` file, you have to change the elasticsearch host to work with Docker instead of Vagrant.

You'll need to change the following lines to work with docker hosts:
```
elasticsearch.host: 
  elasticsearch
```
```
amqp.host: 
  host.docker.internal
```

Copy all the latest facet files from https://github.com/cultuurnet/udb3-vagrant/tree/main/config/udb3-search-service

- facet_mapping_facilities.yml
- facet_mapping_themes.yml
- facet_mapping_types.yml

Copy the feature file from https://github.com/cultuurnet/udb3-vagrant/tree/main/config/udb3-search-service/features.yml

Copy all the regions from https://github.com/cultuurnet/geojson-data/blob/main/output/facet_mapping_regions.yml

Copy the `public-auth0.pem` from https://github.com/cultuurnet/udb3-vagrant/blob/main/config/keys/public-auth0.pem

### RabbitMQ

Login to the management console on http://host.docker.internal:15672/ with username `vagrant` and password `vagrant`

## Migration
Run `make migrate`

## Start

### Docker

Start the docker containers with the following command. Make sure to execute this inside the root of the project.
```
$ make up
```

Stop the docker containers with the following command. Make sure to execute this inside the root of the project.
```
$ make down
```

### Composer install

To install all composer packages, run the:
```
$ make install
```

### ElasticSearch Migrations

To run the ElasticSearch migrations, run the following command:
```
$ make migrate
```

### CI

To execute all CI tasks, run the following command:
```
$ make ci
```