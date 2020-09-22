# udb3-search-service (aka SAPI3)

SAPI3 is a search API built on top of UDB3's json-ld documents using ElasticSearch.

## Technical documentation

### General info

The SAPI3 application consists of 5 major parts:

1. Value-objects, service interfaces and abstract classes for indexing and searching events, places and organizers (`src`)
2. An ElasticSearch implementation of those interfaces and abstract classes (`src/ElasticSearch`)
3. An HTTP layer (`src/Http`)
4. A web app to bootstrap and run everything (`app`, `web/index.php`)
5. A console app to run specific operations on a CLI (`bin/app.php`, `app/Console`)

