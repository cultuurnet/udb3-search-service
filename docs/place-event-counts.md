# Events per place

This document describes how `GET /events/places` finds the places with the most events. A map uses it to show those places with the number of events on each.

The endpoint takes the same filters as `/events` and returns at most 50 places.

---

## The idea in SQL

If the events were in a SQL database, the whole feature would be this one statement. The Elasticsearch request below does the same.

```sql
SELECT   location_main_id, COUNT(*) AS eventCount,
         ANY_VALUE(location_iri), ANY_VALUE(location_name), ANY_VALUE(geo)
FROM     events
WHERE    -- the same filters as /events
  AND    geo IS NOT NULL                                              -- only places on the map
  AND    location_main_id <> '00000000-0000-0000-0000-000000000000'  -- skip online events
GROUP BY location_main_id
ORDER BY eventCount DESC
LIMIT    50
```

---

## The field to group on

Elasticsearch can only group on a field with doc values: a column with one value per document. A `text` field has no doc values, a `keyword` field has.

`location.mainId` holds the bare UUID of the place and is a `text` field. It gets a `keyword` subfield, and the grouping uses `location.mainId.keyword`. Searches keep using `location.mainId`.

The grouping does not use `location.id`, because that field also holds the ids of duplicate places. One event would then count for several places.

Existing documents only get the subfield when they are indexed again. The index name is a hash of the mapping file, so this change creates a new index and needs a full reindex.

---

## The request

Read the request from the outside in. Each layer works on what the layer around it hands over.

| Layer | Name | What it does |
|---|---|---|
| Query | | The `/events` filters. `size: 0`, because only the summary is needed. |
| Filter aggregation | `placeEventCounts` | Keeps the events with a `geo_point`. A place without coordinates can't be shown on the map, so it must not take one of the 50 spots. |
| Terms aggregation | `places` | Puts every event in the bucket of its place, counts the bucket (`doc_count`) and keeps the 50 biggest. `exclude` skips the dummy place that online events point to. |
| Top hits | `place` | Returns one event per bucket, cut down to the place URL, name and coordinates. Every event carries a copy of its place, so any event will do. |

```json
{
  "size": 0,
  "track_total_hits": true,
  "query": { "…": "the /events filters" },
  "aggregations": {
    "placeEventCounts": {
      "filter": { "exists": { "field": "geo_point" } },
      "aggregations": {
        "places": {
          "terms": {
            "field": "location.mainId.keyword",
            "size": 50,
            "exclude": ["00000000-0000-0000-0000-000000000000"]
          },
          "aggregations": {
            "place": {
              "top_hits": {
                "size": 1,
                "_source": ["location.@id", "location.name", "geo_point"]
              }
            }
          }
        }
      }
    }
  }
}
```

Counting is cheap: it reads one value per matching event from the doc values. Fetching the name and coordinates loads one stored event per place, so it happens 50 times at most, however many events match.

---

## The response

```json
{
  "totalEvents": 7,
  "places": [
    {
      "@id": "https://io.uitdatabank.be/place/<uuid>",
      "name": { "nl": "Het Depot" },
      "geo": { "latitude": 50.88, "longitude": 4.70 },
      "eventCount": 3
    }
  ]
}
```

| API field | Comes from |
|---|---|
| `totalEvents` | `hits.total.value`: all matching events, online events included |
| `places[].@id` | the example event's `location.@id` |
| `places[].name` | the example event's `location.name`, or `{}` when it has none |
| `places[].geo` | the example event's `geo_point` |
| `places[].eventCount` | the bucket's `doc_count` |

---

## Good to know

- **Counts can be slightly off.** Each shard sends its own top list and Elasticsearch merges them. A place just below the top 50 on one shard can end up with a count that is a bit too low.
- **`totalEvents` is not the sum of the counts.** It also counts online events, events without coordinates and events at places outside the top 50.
- **Grouping by production is refused.** `groupBy=productionId` only merges events in a result list. It would not change the counts, so the endpoint rejects it.
- **The limit is fixed.** `start`, `limit`, `sort` and facets have no effect on this endpoint.
