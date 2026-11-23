# Location Suggestions Indexing

This document describes the index behind `GET /location-suggestions`. While a user types, the endpoint suggests streets, addresses and UiTdatabank places, each with coordinates.

"Location" means any kind of location here, not only a UiTdatabank place.

The mapping lives in `src/ElasticSearch/Operations/json/mapping_location_suggestions.json`.

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
