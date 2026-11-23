# Location Suggestions Indexing

This document describes the index behind `GET /location-suggestions`. While a user types, the endpoint suggests streets, addresses, municipalities, sub-municipalities, postcodes and UiTdatabank places. Each suggestion has a `searchFilter`, the filter a client passes to Search API 3 when the user picks it.

"Location" means any kind of location here, not only a UiTdatabank place.

The mapping lives in `src/ElasticSearch/Operations/json/mapping_location_suggestions.json`.

---

## The types

| Type | Example label | Suggested when | Label from | `searchFilter` | `searchFilter` from | `geo` from | `address_count` from |
|---|---|---|---|---|---|---|---|
| `street` | Goorbaan, 2230 Herselt | always | CSV: `streetname_nl/fr/de`, `postcode`, `postname_nl/fr`, `municipality_name_nl/fr/de` | `{ "coordinates": "51.04715,4.92321" }` | CSV: average `EPSG:4326_lat/lon` of its addresses | CSV: average of its addresses | CSV: house numbers per `street_id` and `postcode` |
| `address` | Goorbaan 59, 2230 Herselt | only when a house number is typed | CSV: as for a street, plus `house_number` | `{ "coordinates": "51.04940,4.91743" }` | CSV: `EPSG:4326_lat/lon` | CSV: `EPSG:4326_lat/lon` | none |
| `municipality` | Leuven | always | CSV: `municipality_name_nl/fr/de` | `{ "regions": ["nis-24062"] }` | region list: `nis-<municipality_id>`, or the municipality with the same name | CSV: average of its addresses | CSV: addresses per `municipality_id` |
| `submunicipality` | Kessel-Lo | always | region list: the name of a child of the municipality | `{ "regions": ["nis-24062C"] }` | region list: the id of that child | to be decided | none |
| `postcode` | 9050 Gentbrugge | always, a number on its own as the start of a postcode (`22` shows 2200, 2220, 2230) | CSV: `postcode`, `postname_nl/fr` | to be decided: `{ "postalCode": "9050" }` or `{ "regions": [...] }` | CSV: `postcode` | CSV: average of its addresses | CSV: addresses per `postcode` |
| `place` | Yper Museum (Grote Markt 34, 8900 Ieper) | always | UiTdatabank place: `name`, `address` | `{ "locationId": "<uuid>" }` | UiTdatabank place: its id | UiTdatabank place: `geo` | none |

`regions` is an array, because the `regions` parameter of Search API 3 takes several regions.

Every suggestion also has `type`, `label` and `geo`.

### Sources

- CSV: the BOSA BeST address register, OpenAddress export, one file per region (`openaddress-bevlg.csv`, `openaddress-bewal.csv`, `openaddress-bebru.csv`) from https://data.gov.be/nl/datasets/fpsbosa-dis-best-csv-deriv, downloaded with `bin/download-openaddress.sh`. Only rows with `status` `current` are used. It has one row per address, so streets, municipalities and postcodes are grouped from those rows.
- Region list: `output/facet_mapping_regions.php` from https://github.com/cultuurnet/geojson-data, the same list that names the region facets of Search API 3. A tree of province, region, municipality and sub-municipality, with names in nl, fr and de. The `geoshapes` index behind the `regions` filter is built from the same repository.
- UiTdatabank place: the place in UiTdatabank.

The CSV has no sub-municipality id. `postname` belongs to the postcode, so one row of 9050 says `Gentbrugge/Ledeberg` and doesn't tell which of the two the address is in. That is why sub-municipalities come from the region list and have no address count.

---

## The analyzer

`location_analyzer` makes searching ignore capitals, accents and punctuation. Elasticsearch runs it twice: on `search_text` when a document is indexed, and on the typed text when someone searches. Both sides always use the same rules.

| Step | What it does | Example |
|---|---|---|
| `location_char_filter` | Everything that isn't a letter or a digit becomes a space | `O.L.V.-straat` → `O L V straat` |
| `standard` tokenizer | Splits the text into words | `Rue de l Église` → `Rue`, `de`, `l`, `Église` |
| `lowercase` | Lowercases every word | `Église` → `église` |
| `asciifolding` | Removes accents | `église` → `eglise` |

The char filter matches letters with `\p{L}` and not `[a-z]`. It runs before the accents are removed, so `[a-z]` would delete the `É` of `Église`.

## The fields

| Field | What it holds |
|---|---|
| `type` | `street`, `address` or `place` |
| `label` | The text the user sees, like `Lentedreef, 3010 Kessel-Lo`. Not searchable. |
| `search_text` | Every word someone might type to find this document |
| `location` | The coordinates |

### search_text

The query requires every typed word to appear in `search_text`. Putting all words in one field keeps that to one simple query.

| type | label | search_text |
|---|---|---|
| street | Lentedreef, 3010 Kessel-Lo | `Lentedreef 3010 Kessel-Lo Leuven` |
| address | Lentedreef 12, 3010 Kessel-Lo | `Lentedreef 12 3010 Kessel-Lo Leuven` |
| place | Het Depot (Martelarenplein 12, 3000 Leuven) | `Het Depot Martelarenplein 12 3000 Leuven` |

The municipality is in `search_text` even when the label shows a sub-municipality. That is why `lentedreef leuven` finds the Lentedreef in Kessel-Lo.

`index_prefixes` also stores the first 1 to 19 letters of every word. So `lented` already finds `lentedreef` while the user is still typing. It was chosen after comparing five methods in the POC (see [CONCLUSIONS.md](https://github.com/cultuurnet/poc-autocomplete-boa/blob/main/CONCLUSIONS.md)).

Only the last typed word may match the beginning of a word. A number at the end must match whole, otherwise `kerkstraat 90` would also match postcode 9000.

## Acceptance test fixture

`tests/acceptance/openaddress.csv` holds real OpenAddress rows for the streets in the udb3-backend feature `features/search/location-suggestions.feature`. `make acc-test-migrate` indexes it, so the acceptance tests don't download 1 GB.

It keeps every row of those streets, competitors in other municipalities included, because the scenarios check which suggestion comes first. Add rows when a scenario needs a new street, and don't remove rows a scenario depends on.
