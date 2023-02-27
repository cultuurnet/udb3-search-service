# UDB3 Search Service with Docker

## Prerequisite
- Install Docker Desktop 
- GEO JSON Data: you'll have to clone [geojson-data](https://github.com/cultuurnet/geojson-data) in the same folder as where you will clone [udb3-search-service](https://github.com/cultuurnet/udb3-search-service)

## Configure

### config.yml file

Copy the latest `config.yml` from https://github.com/cultuurnet/udb3-vagrant/tree/main/config/udb3-search-service to the root

In your `config.yml` file, you have to change some of the hosts to work with Docker instead of Vagrant.

You'll need to change the following lines to work with docker hosts:
- elasticsearch.host: 
    - `elasticsearch`
- geoshapes_index.indexation.path:
    - `/geojson-data/output/elasticsearch`

### ElasticSearch
To setup ElasticSearch you'll have to clone [geojson-data](https://github.com/cultuurnet/geojson-data)
in your [udb3-search-service](https://github.com/cultuurnet/udb3-search-service) Folder
and run `make migrate-force`

### RabbitMQ

In the current setup only cloud providers are supported. For example https://www.cloudamqp.com/

You'll have to update your `config.yml` file accordingly with the values of your provider:
- amqp.host
- amqp.port
- amqp.user
- amqp.password
- amqp.vhost

To read messages from [udb3-backend](https://github.com/cultuurnet/udb3-backend):
- Create an exchange `udb3.x.domain-events` in your RabbitMQ provider
- Make the url of the docker-container of [udb3-backend](https://github.com/cultuurnet/udb3-backend) available
to the docker container of [udb3-search-service](https://github.com/cultuurnet/udb3-search-service)
  (one possible way is using `host.docker.internal` as `url` in [udb3-backend](https://github.com/cultuurnet/udb3-backend)
and adding `host.docker.internal` to your `/etc/hosts`)

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