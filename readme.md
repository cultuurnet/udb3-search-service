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

This conversion happens in the `CopyJson` class located in:

- `src/ElasticSearch/JsonDocument/CopyJsonOffer.php` (for events and places)
- `src/ElasticSearch/Organizer/CopyJsonOrganizer.php`

After adding the logic to copy the property from the one format to the other, you can run the migration script to re-index all the documents in your index with the new field(s).

### Adding a filter

#### URL parameters

[TODO]

#### The `q` parameter

The `q` URL parameter ([usage documentation](https://documentatie.uitdatabank.be/content/search_api_3/latest/reference/advanced-queries.html)) is basically an ElasticSearch ["query string" query](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html).

This query also supports the [Lucene query syntax](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html#query-string-syntax) to query specific fields.

So we don't process this query ourselves, we only pass it through to ElasticSearch. 
To "add" a way to filter on a specific field in the `q` parameter, you simply need to index the field with the right analyzer(s) for the intended purpose. 
**For this reason, it's best to stick to a _similar_ naming (if not the same) for the indexed fields as in the JSON-LD documents.**

