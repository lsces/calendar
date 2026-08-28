# Calendar Package — Reference Manual

How the package actually works today. For the history of *why* — decisions, bugs found, wrong
turns — see `CLAUDE.md`'s dated session log instead; this file only tracks current behaviour.

## What this is

Month/week/weeklist/day grid view of `liberty_content` records across one or more registered
content types, shared infrastructure most packages that have a natural "date" for their content
(Food, Health, and originally a much older events-style usage) plug into rather than each building
their own date-range UI. `includes/classes/Calendar.php` (`Calendar extends LibertyContent`) does
the date-range math and record lookup; `index.php` is the general Calendar/Display-Options entry
point with the full type picker.

## Package view facility (`package_page.php`)

A package-specific entry point (`package_page.php?pkg=health|food`) for linking straight into a
calendar fixed to that package's own content type — no Calendar/Display-Options tab, no
Show-all/sort-mode links, a compact symmetric date-nav row instead of `index.php`'s stacked one.
Built as its own files (`package_page.php`, `templates/package.tpl`,
`templates/package_nav_inc.tpl`) — completely independent of `index.php`/`calendar.tpl`/
`calendar_nav_inc.tpl`, so nothing done here can affect the general calendar page or vice versa.

Each package gets its own session slot, `$_SESSION['calendar_pkg_<pkg>']`, distinct from
`index.php`'s `$_SESSION['calendar']` — view_mode/focus-date state never cross-contaminates
between the general calendar and any package's own fixed view, or between two different packages'
own fixed views.

**`pkg=>guid` mapping** is a small allowlist inside `package_page.php` itself — adding a new
package here means adding its entry to that array, nothing more elaborate.

**`extra_guid`/`extra_label`** (optional, per-package): a second content type offerable via a
checkbox alongside the page's primary type — e.g. food's primary guid is `foodday` (one tile per
day, the default), with `extra_guid=foodassembly`/`extra_label="Show individual meals"` letting a
user opt back into one cell per meal. Persisted per-package in session the same way `view_mode`
already is. A package with no `extra_guid` configured (health, currently) shows no checkbox at
all and its guid list is fixed.

## Per-day summary tiles (`getDayCellHtml()`)

A content type's handler class can implement `getDayCellHtml()` (an ordinary instance method) to
supply its own rendered cell markup instead of the plain title/link every other type gets.
`LibertyContent::getContentList()` (in `liberty`, shared — not calendar-specific) checks
`method_exists($handler_class, 'getDayCellHtml')` and, when present, stashes the result as
`$item.cell_html`. `package.tpl`'s (and `calendar.tpl`'s) day/weeklist/month cell blocks all check
`{if $item.cell_html}{$item.cell_html}{else}<plain title/link>{/if}` — purely additive, any type
that doesn't implement the method is completely unaffected.

`HealthDay::getDayCellHtml()` (health package) is the first real implementation — see
`health.md`/`health/MANUAL.md` for its own rollup logic.

## Package view facility — `Calendar::getEvents()` virtual-type hook

For a content type with **no `liberty_content` rows at all** (a pure computed summary, like
Food's `FoodDay` — see `food/MANUAL.md`), `getDayCellHtml()` alone isn't enough, since there's no
row for `getContentList()`'s normal SQL-against-`liberty_content` path to find in the first place.
`Calendar::getEvents()` handles this with a second, opt-in hook:

- For each selected `content_type_guid`, resolve its handler class and check whether it declares
  its own **static** `getContentList()` — checked via `(new ReflectionMethod($class,
  'getContentList'))->isStatic()`, since every real `LibertyContent` subclass already inherits a
  *non-static* method of the same name from the base class. Reflection is the only reliable way to
  tell "real content type using the normal path" from "virtual type opting into this hook" apart.
- A type that opts in gets called directly — `$class::getContentList($pListHash)` — instead of
  being included in the batch SQL query `getList()` runs for every remaining "real" type. Results
  are merged into the same `$bitEvents[$dayStartUtc][] = ...` shape `getList()` itself produces,
  so a virtual and a real type selected together on the same page behave identically to the
  caller.
- **Contract for `getContentList(array $pListHash): array`**: must accept
  `$pListHash['time_limit_start']`/`['time_limit_stop']` (UTC unix timestamps, the same
  display-offset-shifted range `getEvents()` computes for every other type) and return
  `[$dayStartUtc => [item, item, ...]]` — one array key per calendar day that actually has
  something to show; a day with nothing to show simply has no key (no empty-array placeholder
  needed). Each `item` follows whatever shape the consuming template expects (typically at least
  `cell_html` if the type also implements the `getDayCellHtml()`-equivalent rendering inline,
  since a virtual type has no per-row content object to call the method on after the fact).
- Virtual types bypass `prepGetList()`'s own `time_limit_start`/`stop` setup entirely (that only
  happens inside `getList()`, which they never reach) — `getEvents()` computes the same range
  itself via `doRangeCalculations()` whenever at least one virtual type is present in the
  selection.
- A type must still be **registered** in `liberty_content_types` (via the normal
  `LibertySystem::registerContentType()`, exactly like a real content type's own constructor does)
  for `getContentClassName()` to resolve it at all — a virtual type isn't exempt from the registry,
  only from having actual `liberty_content` rows.

**Guarded**: a selected `content_type_guid` that isn't currently registered at all (e.g. a stale
entry surviving in a user's persisted `calendar_default_guids` preference after a type was
deactivated or renamed) is dropped before any further lookup, rather than passing `null` into
`isPackageActive()`/`strtoupper()` — this was a real PHP 8.5 deprecation hit live, not a
theoretical guard.

## Range calculation gotchas (fixed, kept as reference)

- **Week-start convention**: `WEEK_OFFSET=0` means Monday-start throughout this package
  (`buildMonth()`/`setupDayNames()`'s base day-name array is already Monday-first) — any new
  range-math code touching `doRangeCalculations()` must convert PHP's native `wday` (0=Sunday)
  to that same Monday-first convention before applying `WEEK_OFFSET`, not assume Sunday=0.
- **Weeklist/month-boundary spillover**: every day cell shown in a week or weeklist view is
  in-range by definition — there is no "this day belongs to a different month, hide its items"
  concept in those views (only the month grid itself has real out-of-range spillover cells, and
  even those still show their own real data, just visually muted).

## Known limitations / not built

- No cross-package "which types does this domain actually have" auto-discovery — `package_page.php`'s
  `pkg=>guid` allowlist is hand-maintained.
- The general `index.php` Calendar/Display-Options page has no equivalent of `extra_guid`'s
  opt-in-second-type mechanism — that's `package_page.php`-only for now.
- `getEvents()`'s virtual-type hook was built specifically to unblock `FoodDay`; only one real
  consumer exists as of this writing, so the contract above is inferred from that one
  implementation, not yet stress-tested against a second, differently-shaped virtual type.
