# Formatting rules

The rules every calendar summary follows. They exist so the four languages and the two formats stay
recognisable as one product. When a new field needs a place in the summary, it follows these rules
instead of inventing its own.

See the [README](README.md) for the sizes and calendar types themselves.

## Braces

Braces mark an aside: something that is not the date itself.

| Rule | Example |
| --- | --- |
| An availability is always between braces | `Maandag 28 november 2022 van 20:00 tot 21:00 (Volzet of uitverkocht)` |
| A childcare that stands on its own line or list item is between braces | `(Opvang van 8:00 tot 18:00)` |
| A childcare that continues the sentence of the date is not | `Maandag 13 juli 2026 van 00:30 tot 01:15 met overnachting, opvang van 10:00 tot 16:00` |
| A summary that every day shares is not, it is a statement of its own | `Elke dag opvang van 8:00 tot 17:00` |
| A description of an adjusted or closed period is between braces | `Maandag 2 november 2026 tot en met zaterdag 7 november 2026 (Herfstvakantie)` |

The plain text output keeps its braces everywhere, including around a shared childcare and around a
week scheme on one line. That is its own house style, and it is deliberate.

## Capitals

| Rule | Why |
| --- | --- |
| A line starts with a capital | it is a sentence |
| Something that continues the previous line does not | it is not a new sentence |
| Days of the week keep their capital in German and English | those languages always capitalise them |
| Days of the week lose it mid-sentence in Dutch and French | those languages only capitalise them at the start |

The last two rules are why `(Montag von 9:00 bis 12:00)` and `(maandag van 9:00 tot 12:00)` differ.
It is a property of the language, not of the formatter: `Translator::capitalizesDaysOfWeek()`.

## Joining

| Situation | Rule | Example |
| --- | --- | --- |
| A day is open more than once | one line, joined with the translation of `and` | `Vrijdag van 8:00 tot 12:00 en van 17:00 tot 19:00` |
| That day has childcare per opening | one mention, joined the same way | `(opvang van 7:00 tot 13:00 en van 16:00 tot 20:00)` |
| The joined childcares are of a different kind | the wording repeats, the kind changed | `(Vooropvang vanaf 12:30 en naopvang tot 21:45)` |
| Every day has the same childcare | mentioned once for the whole week | `Elke dag opvang van 8:00 tot 17:00` |
| Days share the same hours | the days collapse into a range | `Maandag - dinsdag van 9:00 tot 12:00` |

Never repeat what did not change. A day's childcare belongs to the day, not to each of its openings.

## Order

Within one day or date:

1. the day or the date
2. the hours
3. the availability, which closes the date
4. the childcare, on its own line or list item

The availability stays with the date it belongs to. The childcare follows after it, so the date and
its availability never get split apart.

Within a summary:

1. the period or the date
2. the opening hours per day
3. a childcare that every day shares
4. the adjusted days (`xl`) or the warning that they exist (`lg`)
5. the closed days (`xl`): first the days of the week without opening hours (`Elke zondag gesloten`),
   then the closed periods. In `xl` those days are left out of the week scheme, which lists the
   open days in week order, one per line in plain text.

## Availability of a permanent offer

A permanent offer has no date to close, so an unavailable one shows its availability instead of the
whole summary, without braces and with a capital. This holds in every size and in both formats,
including a place that is open but fully booked: `Volzet of uitverkocht`. A place that is closed reads
`Permanent gesloten` or `Tijdelijk gesloten` instead of `geannuleerd` or `uitgesteld`.
`AvailabilityParityTest` keeps the HTML and the plain text output in line.

## Empty is empty

| Input | Output |
| --- | --- |
| no childcare | nothing about childcare |
| `childcare: {}` | nothing |
| `childcare: {"start": ""}` | nothing, an empty hour is not an hour |
| `overnight: false` | nothing |
| opening hours that are an empty list | nothing, not a week of closed days |

Never report the absence of something. A date without childcare says nothing about childcare, even
when another date of the same offer has it.

## Plain text

| Rule | Example |
| --- | --- |
| One statement per line | |
| A childcare line is indented with one space | `␣(opvang van 8:00 tot 18:00)` |
| Blocks are separated by an empty line | between the week scheme, the adjusted days and the closed days |
| A block starts with its label on a line of its own | `Behalve tijdens` |

## HTML

| Element | Class | Holds |
| --- | --- | --- |
| `<p>` | `cf-period` | the period of a periodic offer |
| `<p>` | `cf-openinghours` | the `open at` caption |
| `<ul>` | `list-unstyled` | the week scheme |
| `<li>` | `openingHoursSpecification` | one day |
| `<span>` | `cf-days` | the day or the range of days |
| `<span>` | `cf-time` | one hour |
| `<span>` | `cf-from` `cf-to` `cf-at` `cf-meta` | the words between the hours |
| `<span>` `<li>` | `cf-childcare` | a childcare |
| `<span>` | `cf-status` | an availability |
| `<span>` | `cf-date` | a date |
| `<span>` | `cf-description` | the description of an adjusted or closed period |
| `<details>` | `cf-adjusted-days` | the adjusted days, collapsible |
| `<details>` | `cf-closed-days` | the closed days, collapsible |

The `itemprop` attributes are schema.org microdata. Keep them on the elements that already have
them, so the markup stays readable by search engines.

## Languages

Four languages, always: `nl`, `fr`, `de`, `en`. Every label comes from `Translator`, never from a
string in a formatter.

| Meaning | nl | fr | de | en |
| --- | --- | --- | --- | --- |
| open at | open op | ouvert le | geöffnet | open at |
| except during | behalve tijdens | sauf pendant | außer während | except during |
| closed | gesloten | fermé | geschlossen | closed |
| childcare | opvang | garderie | Kinderbetreuung | childcare |
| childcare before | vooropvang | garderie du matin | Frühbetreuung | early childcare |
| childcare after | naopvang | garderie du soir | Spätbetreuung | late childcare |
| overnight stay | met overnachting | avec nuitée | mit Übernachtung | with overnight stay |
| every day | elke dag | chaque jour | jeden Tag | every day |
| every (day of the week) | elke | chaque | jeden | every |
| and | en | et | und | and |
| to (a period) | tot en met | au | bis | to |

Two things to watch, both of which went wrong before:

- A word can be right in one phrase and wrong in another. French ends a period with `au` but an hour
  range with `à`, so `to` and `till_hour` are separate keys.
- A rule that fits Dutch is not automatically right elsewhere. Lowercasing a day of the week fits
  Dutch and French, and breaks German and English.

## Hours

| Rule | Example |
| --- | --- |
| No leading zero on the hour | `9:00`, not `09:00` |
| Minutes keep their zero | `9:05` |
| A whole day is not shown as hours | `Maandag 13 juli 2026`, not `van 0:00 tot 23:59` |

## Adding something new

1. Put the wording in `Translator`, in all four languages.
2. Check whether an existing rule already covers where it goes. It usually does.
3. Add an example file for every combination it appears in, in both formats.
4. Add it to the tables above, and to the README if it is a new field or size.
