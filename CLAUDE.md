# Project guidelines

## Projections

### Always emit optional fields in projections

When a property transformer reads an optional field from the source JSON, it
must still write the field to the indexed document — using a sensible default
when the source value is absent. Do not omit the field "because it isn't there
yet" or "because it equals the default".

Why:
- Filters and queries rely on the field being present on every document.
  A `term` filter on `false` does not match documents where the field is
  missing, so omission silently produces wrong (usually empty) result sets.
- The indexed document is the contract: consumers should not have to guess
  whether absence means `false`, `null`, or "unknown".

How to apply:
- In `Properties\*Transformer::transform`, always set `$draft[<field>] = ...`.
  Use `?? <default>` for the missing case rather than wrapping the assignment
  in an `if`.
- Mirror this in the test fixtures: every `tests/.../data/**/indexed*.json`
  carries the field even when its value is the default.
- Add the field to the ES mapping JSONs under `src/ElasticSearch/Operations/json/`.

See `ChildrenOnlyTransformer` for a minimal reference implementation.
