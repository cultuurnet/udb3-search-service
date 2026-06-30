# Project coding guidelines

See the  docs/calendar-indexing.md for the information about the calendar indexing.

## Running tests and tooling

Always run tests, PHPStan and code style **inside the `search` Docker container** via
the `Makefile` targets — do not run `vendor/bin/*` directly on the host (the host PHP
version differs and PHPStan/PHPUnit can fail or behave differently there).

- `make test` — run the full PHPUnit suite (`composer test`).
- `make test-filter filter=<pattern>` — run a subset, e.g. `make test-filter filter=OfferSearchControllerTest`.
- `make stan` — run PHPStan (`composer phpstan`).
- `make cs` / `make cs-fix` — check / autofix code style.
- `make ci` — run the full CI pipeline (PHPStan + code style + tests); run this before pushing.

Each target shells into the container (`docker compose exec -it search ...`). Use
`docker compose exec -T search composer <script>` when invoking non-interactively.
Note: this project does not install the `phpstan-phpunit` extension, so PHPUnit
assertions (`assertNotNull`, `assertInstanceOf`, ...) do **not** narrow types for
PHPStan — avoid dereferencing a nullable return in tests (e.g. assert against a value
object instead of calling a method on a `?Type` result).

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
