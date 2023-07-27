# UDB3 Search Service with Docker

## Prerequisite
- Install [Docker Desktop](https://www.docker.com/products/docker-desktop/)
- GEO JSON Data: you'll have to clone [geojson-data](https://github.com/cultuurnet/geojson-data) in the same folder as where you will clone [udb3-search-service](https://github.com/cultuurnet/udb3-search-service)
- Appconfig: you'll have to clone [appconfig](https://github.com/cultuurnet/appconfig) in the same folder.

## Configure

### Configuration files.

Run the script `./bin/config.sh`, this will copy a couple of configuration yml files, placing them in the root of the udb3-search-service project, and modify the config.yml for local development.

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