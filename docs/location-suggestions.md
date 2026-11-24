# Location Suggestions Indexing

This document describes the index behind `GET /location-suggestions`. While a user types, the endpoint suggests streets, addresses, municipalities, sub-municipalities, postcodes and UiTdatabank places. Each suggestion has a `searchFilter`, the filter a client passes to Search API 3 when the user picks it.

"Location" means any kind of location here, not only a UiTdatabank place.

The mapping lives in `src/ElasticSearch/Operations/json/mapping_location_suggestions.json`.

---

## The types

| Type | Example label | Suggested when | Label from | `searchFilter` | `searchFilter` from | `location` from | `address_count` from |
|---|---|---|---|---|---|---|---|
| `street` | Goorbaan, 2230 Herselt | always | CSV: `streetname_nl/fr/de`, `postcode`, `postname_nl/fr`, `municipality_name_nl/fr/de` | `{ "coordinates": "51.04715,4.92321" }` | CSV: average `EPSG:4326_lat/lon` of its addresses | CSV: average of its addresses | CSV: house numbers per `street_id` and `postcode` |
| `address` | Goorbaan 59, 2230 Herselt | only when a house number is typed | CSV: as for a street, plus `house_number` | `{ "coordinates": "51.04940,4.91743" }` | CSV: `EPSG:4326_lat/lon` | CSV: `EPSG:4326_lat/lon` | none |
| `municipality` | Leuven | always | CSV: `municipality_name_nl/fr/de` | `{ "regions": ["nis-24062"] }` | region list: `nis-<municipality_id>`, or the municipality with the same name | CSV: average of its addresses | CSV: addresses per `municipality_id` |
| `submunicipality`, returned as `municipality` | Kessel-Lo (3010) | always, and only these when only numbers are typed (`30` shows Leuven (3000) and Kessel-Lo (3010)) | region list: the name of a child of the municipality, CSV: the first `postcode` of its addresses | `{ "regions": ["nis-24062C"] }` | region list: the child whose name is the CSV `postname_nl/fr` | CSV: average of its addresses | CSV: addresses whose `postname_nl/fr` is the sub-municipality |
| `postalCode` | 9050 Gentbrugge/Ledeberg | not built: a postcode now suggests its sub-municipalities (`9050` shows Gentbrugge (9050) and Ledeberg (9050)) | CSV: `postcode`, `postname_nl/fr` | `{ "regions": ["nis-44021F", "nis-44021G"] }` | region list: the sub-municipalities named in the CSV `postname_nl/fr` | CSV: average of its addresses | CSV: addresses per `postcode` |
| `place` | Yper Museum (Grote Markt 34, 8900 Ieper) | always | UiTdatabank place: `name`, `address` | `{ "locationId": "<uuid>" }` | UiTdatabank place: its id | UiTdatabank place: `geo` | none |

`regions` is an array, because the `regions` parameter of Search API 3 takes several regions.

Every suggestion has `type`, `label` and `searchFilter`. It has no coordinates of its own: a client searches with the `searchFilter`, and for an area the average point in `location` would be the wrong filter. `location` is used for the `coordinates` filter of a point and later to order by distance.

A sub-municipality is returned with the type `municipality`. Both are an area with a `regions` filter, so a client doesn't need to tell them apart, and leaving out `municipality` leaves out both. The label does tell them apart: `Leuven` is the whole municipality, `Leuven (3000)` its centre.

### Sources

- CSV: the BOSA BeST address register, OpenAddress export, one file per region (`openaddress-bevlg.csv`, `openaddress-bewal.csv`, `openaddress-bebru.csv`) from https://data.gov.be/nl/datasets/fpsbosa-dis-best-csv-deriv, downloaded with `bin/download-openaddress.sh`. Only rows with `status` `current` are used. It has one row per address, so streets, municipalities and postcodes are grouped from those rows.
- Region list: `output/facet_mapping_regions.php` from https://github.com/cultuurnet/geojson-data, the same list that names the region facets of Search API 3. A tree of province, region, municipality and sub-municipality, with names in nl, fr and de. The `geoshapes` index behind the `regions` filter is built from the same repository.
- UiTdatabank place: the place in UiTdatabank.

### From addresses to municipalities and sub-municipalities

The CSV only has addresses. Streets, municipalities and sub-municipalities are groups of those addresses: their `address_count` is the number of addresses in the group, their `location` the average coordinate. The region list has no addresses. It only says which municipalities and sub-municipalities exist and what their region is.

The CSV has no sub-municipality id, only the `postname` of the postcode. The import links an address to a sub-municipality through it: the child of the municipality in the region list with the same name. Leuven with five addresses:

| Address | `postcode` | `postname_nl` |
|---|---|---|
| Martelarenplein 12 | 3000 | Leuven |
| Bondgenotenlaan 5 | 3000 | Leuven |
| Lentedreef 27 | 3010 | Kessel-Lo |
| Lentedreef 17 | 3010 | Kessel-Lo |
| Naamsesteenweg 1 | 3001 | Heverlee |

| Suggestion | Region | Addresses | `address_count` |
|---|---|---|---|
| Leuven | `nis-24062` | all five, by `municipality_id` | 5 |
| Leuven (3000) | `nis-24062A` | postname Leuven | 2 |
| Kessel-Lo (3010) | `nis-24062C` | postname Kessel-Lo | 2 |
| Heverlee (3001) | `nis-24062-Z` | postname Heverlee | 1 |
| Wilsele | `nis-24062-Y` | none | not suggested |

Where it doesn't line up:

- A postname can name several sub-municipalities: 3271 is `Averbode/Zichem`, and a row doesn't say which of the two the address is in. Each of them counts its share, half an address here, and both get the average coordinate of all of 3271. That also keeps a sub-municipality below its municipality.
- Some postnames have no sub-municipality (Wijgmaal, Zeebrugge), and some sub-municipalities match no postname (Berendrecht-Zandvliet-Lillo, Jupille-sur-Meuse against the postname Jupille). On all of Belgium 2,561 of the 2,664 sub-municipalities match. The others are not suggested, but their streets are still found by their postname.
- Antwerpen (`nis-11002-Z`) has seven postcodes. All of them are in its `name` and `search_text`, the label shows the first: `Antwerpen (2000)`.

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
| `type` | `street`, `address`, `municipality`, `submunicipality` or `place` |
| `label` | The text the user sees, like `Lentedreef, 3010 Kessel-Lo`. Not searchable. |
| `name` | The street or municipality name, like `Bisschoppenhof`. In Brussels both names, like `Henegouwenkaai` and `Quai du Hainaut`. A sub-municipality also has its postcodes, like `Kessel-Lo` and `3010`. Not on addresses, only used for the exact match. |
| `search_text` | Every word someone might type to find this document |
| `location` | The coordinates |
| `address_count` | The number of house numbers on the street, in the municipality or in the sub-municipality. Not on addresses. |
| `regions` | The Search API 3 region of a municipality or sub-municipality, like `nis-24062`. Not searchable. |

### search_text

The query requires every typed word to appear in `search_text`. Putting all words in one field keeps that to one simple query.

| type | label | search_text |
|---|---|---|
| street | Lentedreef, 3010 Kessel-Lo | `Lentedreef 3010 Kessel-Lo Leuven` |
| address | Lentedreef 12, 3010 Kessel-Lo | `Lentedreef 12 3010 Kessel-Lo Leuven` |
| municipality | Leuven | `Leuven Louvain Löwen` |
| submunicipality | Kessel-Lo (3010) | `Kessel-Lo 3010` |
| place | Het Depot (Martelarenplein 12, 3000 Leuven) | `Het Depot Martelarenplein 12 3000 Leuven` |

The municipality is in `search_text` even when the label shows a sub-municipality. That is why `lentedreef leuven` finds the Lentedreef in Kessel-Lo.

`index_prefixes` also stores the first 1 to 19 letters of every word. So `lented` already finds `lentedreef` while the user is still typing. It was chosen after comparing five methods in the POC (see [CONCLUSIONS.md](https://github.com/cultuurnet/poc-autocomplete-boa/blob/main/CONCLUSIONS.md)).

Only the last typed word may match the beginning of a word. A number at the end must match whole, otherwise `kerkstraat 90` would also match postcode 9000.

Only numbers is the exception to the exception: that is a postcode being typed, so it matches the beginning again, and only sub-municipalities are suggested. `30` shows Leuven (3000) and Kessel-Lo (3010), `3010` only Kessel-Lo (3010). Someone who types only a postcode means the area, not its streets, and a house number on its own means nothing.

### name

When the whole typed text equals a name, the suggestion gets a boost. So `bisschoppenhof` shows Bisschoppenhof, 3150 Haacht above the Bisschoppenhoflaan streets, which also start with what was typed.

`location_normalizer` folds the name like the analyzer does (punctuation, capitals, accents), but keeps it as one value, so `o l v ten steenstraat` equals `O.L.V. ten Steenstraat` and `bisschoppenhof` does not equal `Bisschoppenhoflaan`.

### address_count

The text score is multiplied by `log1p(0.5 × address_count)`, so of the streets with the same name the one with the most addresses comes first: `lentedreef` shows the Lentedreef in Menen (49 addresses) above the one in Oud-Turnhout (28).

The logarithm keeps that difference small, so a big city doesn't bury a small-town match. Bisschoppenhof, 3150 Haacht (8 addresses) stays above Bisschoppenhoflaan, 2100 Deurne (487), because the exact name counts for more.

A box number doesn't count as an address, so a block of flats counts once.

Addresses have no count. The query treats them as a street with one address, so they keep the order of their text score. Giving them the count of their street would need a second pass over the CSVs (about 1.5 minutes per import) and barely changed the order on real data.

### regions

A municipality and a sub-municipality are an area, so their `searchFilter` is their region and not a coordinate. The region comes from the same list Search API 3 uses for its region facets (`facet_mapping_regions.php` from https://github.com/cultuurnet/geojson-data), so it matches the regions of events and places.

The address CSV has the municipality's NIS code, so Leuven (24062) gets `nis-24062`. In 2019 eleven municipalities in Hainaut got a new NIS code, and geojson-data still has the old one (Enghien: 51067 in the CSV, `nis-55010` in Search API 3). Those are found by name. A municipality the list doesn't have is left out with a warning, because its filter would find nothing.

A sub-municipality is a child of its municipality in the same list, like `nis-24062C` for Kessel-Lo (see [From addresses to municipalities and sub-municipalities](#from-addresses-to-municipalities-and-sub-municipalities)).

## Acceptance test fixture

`tests/acceptance/openaddress.csv` holds real OpenAddress rows for the streets in the udb3-backend feature `features/search/location-suggestions.feature`. `make acc-test-migrate` indexes it, so the acceptance tests don't download 1 GB.

It keeps every row of those streets, competitors in other municipalities included, because the scenarios check which suggestion comes first. Add rows when a scenario needs a new street, and don't remove rows a scenario depends on. When the number of addresses doesn't matter for a scenario, one row per street is enough (the Bisschoppenhof streets). Bisschoppenhoflaan in Deurne has two, so it has more addresses than Bisschoppenhof in Haacht and only the exact name puts Haacht first.

Groenplaats is there for the `ant` scenario: it gives Antwerpen three addresses against two for Bisschoppenhoflaan in Deurne. Prefix matches all score the same, so without it the two tie and the order isn't fixed.

The Sint-Niklaas and Sint-Gillis rows are there for the `sint` scenario: of the municipalities that start with `sint`, the bigger one comes first. Sint-Niklaas has 75 addresses, more than O.L.V. ten Steenstraat in Tienen (54), which also matches `sint` through its postname Sint-Margriete-Houtem. Sint-Gillis has two.

The Nieuwstraat rows in 3271 are there for the `3271` scenario: the postname `Averbode/Zichem` gives two sub-municipalities. In the fixture Kessel-Lo (3010) has more addresses than Leuven (3000), whose Martelarenplein rows are mostly box numbers, so `30` shows Kessel-Lo first and `300` Leuven (3000).
