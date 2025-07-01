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

### Index versioning

We have a command that will migrate the ElasticSearch index to a new version _if it's necessary_:

```bash
./bin/app.php elasticsearch:migrate
```

An actual migration will only occur if the script detects a new version number in the document mapping. At that point, it will create a new index and configure it with the latest mapping, and then re-index all documents from the old index by looping over them and fetching the latest JSON-LD for each one from UDB3 and indexing that in the new index.

This means that this command is idempotent. You can run it as much as you want without doing any checks beforehand, e.g. after every deploy or git pull.

**How it works**

To keep the search index live while re-indexing in production (and other environments), we work with two aliases:

- `udb3_core_read`
- `udb3_core_write`

The actual index has a versioned name, for example `udb3_core_v20191008132400`

Most of the time, these two aliases will point to the same index, i.e. the latest one.

When the migration script detects it needs to do a migration because there's a new version number, it will first create the new index and move the `udb3_core_write` alias to the new index. This way, new documents will already get indexed in the new index.

In the meantime, the `udb3_core_read` alias still points to the old index so users don't suddenly get a massive drop in search results while the re-indexation is happening in the new index.

After the new index is re-indexed, the migration script will move the `udb3_core_read` alias to the new index as well.

With this approach the only side effect of migrating is that users might get some outdated search results while the new indexation is happening.

### JSON document structure

The structure of the JSON documents in ElasticSearch for events, places and organizers is different from the JSON-LD structure in UDB3.

This is intentional, because we might have to index a field with multiple analyzers and/or make changes to the data structure before indexing.

So the JSON-LD returned by SAPI3 in HTTP responses is not the JSON document that's indexed inside ElasticSearch. Instead, we also index the original JSON-LD as an un-analyzed field and use that to return the original JSON-LD in HTTP responses.  

### Indexing a new field

The field mapping of all documents can be found in `src/ElasticSearch/Operations/json` as `mapping_*.json` files.

Add your new field mapping in those files as [per the ElasticSearch documentation](https://www.elastic.co/guide/en/elasticsearch/reference/6.8/mapping.html). 
As noted above, you don't have to follow the JSON-LD structure or naming 100%, since it would make querying very hard in some situations.
For example, because it's hard to do a range query on separate `availableFrom` and `availableTo` fields, we instead index them as a single `availableRange` field.

After adding your field(s) to the mapping, update the `UDB3_CORE` version number in `src/ElasticSearch/Operations/SchemaVersions.php`

An example of a valid version number is `20191008132400`. This is simply the current date time in the `YYYYMMDDHHIISS` format (year, month, day, hour, minute, second without anything in-between).

This change would make the migration script see the new mapping and create a new index for it. However, we're still missing a way to convert the property from the JSON-LD document to the property on the ElasticSearch document.

This conversion happens in the `JsonTransformer` implementations located in:

- `src/ElasticSearch/JsonDocument/EventTransformer.php`
- `src/ElasticSearch/JsonDocument/PlaceTransformer.php`
- `src/ElasticSearch/JsonDocument/OrganizerTransformer.php`

When copying a nested property from the JSON-LD to the ElasticSearch JSON, don't copy the whole object in which it's nested. 
Only copy the (sub-)properties for which we have explicit mapping.
Otherwise, the other sub-properties like name will also be indexed with automated mapping which would expose it in the q parameter used for advanced queries.

For example, events in JSON-LD have a `production` property like this:

```json
{
  "@type": "Event",
  "@id": "https://io.uitdatabank.dev/events/bcd9242d-ef85-4a32-8ad0-01af6f675634",
  ...
  "production": {
    "id": "08314739-ab47-4e89-a80c-ce46ef07ba1d",
    "name": "Test production",
    "otherEvents": [ ... ]
  }
}
```

When we want to index the production id, but not necessarily the production name, we have to make the resulting ElasticSearch JSON look like this:

```json
{
  ...
  "production": {
    "id": "08314739-ab47-4e89-a80c-ce46ef07ba1d"
  }
}
```

After adding the logic to copy the property from the one format to the other, you can run the migration script to re-index all the documents in your index with the new field(s).

### Adding a filter

#### URL parameters

To keep the ElasticSearch implementation of SAPI3 separate from the rest of the code, we work with the concept of query builders.

There's a query builder for offers, and one for organizers. Each of these has an interface, with an ElasticSearch implementation.

The HTTP controllers and other code only depend on the query builder interfaces. 
When bootstrapping and running the app we inject the actual ElasticSearch implementation classes.
This way we could theoretically swap out ElasticSearch with another search engine.

So to add a new URL parameter to filter on, you need to make the following changes:

1. Add a new method on the relevant query builder interface(s) (offer and/or organizer)
2. Implement the new method on the ElasticSearch implementation class(es)
3. Change the HTTP controller(s) to look for a new query parameter and use that to call the new query builder method(s)

**Query builder interface(s)**

The query builder interfaces are located at:

- `src/Offer/OfferQueryBuilderInterface.php`
- `src/Organizer/OrganizerQueryBuilderInterface.php`

Note that the implementations are supposed to be immutable, so we use chain-able `with` methods that return a copy of the called object with a new property.

**ElasticSearch implementations**

The ElasticSearch implementations of the query builder interfaces are located at:

- `src/ElasticSearch/Offer/ElasticSearchOfferQueryBuilder`
- `src/ElasticSearch/Organizer/ElasticSearchOrganizerQueryBuilder`

These classes use the `ongr/elasticsearch-dsl` package to build queries. 
Note however that they both extend a `AbstractElasticSearchQueryBuilder` class which provides a lot of convenience methods for common queries like match, term, etc.

**HTTP controllers**

The HTTP controllers are located at:

- `src/Http/OfferSearchController.php`
- `src/Http/OrganizerSearchController.php`

In the past, the controllers did a lot of parsing of query parameters themselves. 
However, we later introduced the concept of "request parsers" that take an API request object and query builder object, and then look for a specific query parameter and add the necessary filters on the query builder object.
This way we can better divide the responsibilities of each class.

If you need to add logic for a completely new URL parameter, start by creating a request parser in either:
 
- `src/Http/Offer/RequestParser`
- `src/Http/Organizer/RequestParser`

Then, add it to the collection of request parsers in the app's controller providers, so it gets injected in the relevant controller:

- `app/Offer/OfferSearchControllerFactory.php` (We need to make multiple instances of this controller for the `/offers/`, `/events/` and `/places/` endpoints, thus a factory.)
- `app/Organizer/OrganizerServiceProvider.php`

Note that you will also need to change the unit tests to include the new request parser(s) in the controller(s).

**If you need to make changes to an existing URL parameter and there's no request parser for it yet, it's best to move the logic from the controller to a request parser first!** 

Lastly, add your new URL parameter(s) to the list(s) of supported query parameters:

- `src/Http/Parameters/OfferSupportedParameters.php`
- `src/Http/Parameters/OrganizerSupportedParameters.php`

#### The `q` parameter

The `q` URL parameter ([usage documentation](https://documentatie.uitdatabank.be/content/search_api_3/latest/reference/advanced-queries.html)) is basically an ElasticSearch ["query string" query](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html).

This query also supports the [Lucene query syntax](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html#query-string-syntax) to query specific fields.

So we don't process this query ourselves, we only pass it through to ElasticSearch. 
To "add" a way to filter on a specific field in the `q` parameter, you simply need to index the field with the right analyzer(s) for the intended purpose. 
**For this reason, it's best to stick to a _similar_ naming (if not the same) for the indexed fields as in the JSON-LD documents.**

### Regression tests

For major upgrades, we have provided a [list of regression tests](https://docs.google.com/spreadsheets/d/1jm2JAcI8WvxdmqQGtBlJlEkmoV-kSYyoVdn8bu7UAkY/edit?usp=sharing), 
some but not all cases are covered by the acceptance tests.