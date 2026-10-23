# UI guidelines

How the interface is built. Follow these rules when adding or editing screens so
spacing, sizing and behaviour stay predictable.

## No build step, no external requests

The interface is styled by a single hand-written stylesheet at
`public/css/app.css`. There is **no** Tailwind, Vite or CDN dependency, and no
external font. This is deliberate: the app must render on a slow connection and
must never depend on a build succeeding to look right.

If you add a component, add it to `app.css` under the matching numbered section,
and document it there.

## Do not use inline `style="…"`

Inline styles are what caused the earlier problem where some boxes looked bigger
or smaller than others. Use the utility and component classes instead:

| Instead of | Use |
|---|---|
| `style="margin-top:12px"` | `class="mt-3"` |
| `style="margin-top:16px"` | `class="mt-4"` |
| `style="margin-bottom:0"` | `class="mb-0"` |
| `style="text-align:center"` | `class="text-center"` |
| `style="max-width:560px"` | `class="narrow"` / `class="auth-card"` |

The only accepted inline styles are ones that carry **data**, not layout — for
example a meter width computed in PHP such as
`style="width: {{ $percent }}%"`. Everything else belongs in the stylesheet.

## Spacing scale

Use the design tokens, never raw pixel values: `--space-1` (4px) through
`--space-7` (44px). The utility classes `mt-0…mt-6`, `mb-0…mb-5` map onto them.
For vertical rhythm between a group of siblings, prefer `stack`, `stack-sm` or
`stack-lg` over stacking margins by hand.

## Cards

Cards are flex columns, so:

- Put action buttons in a `.card-actions` block. It pins to the bottom of the
  card, which keeps buttons on the same line across a row of uneven cards.
- Use `.card-head` for a title + status badge row.
- Never set a fixed height on a card. Let the grid equalise the row.

## Grids

- `.grid` — responsive auto-fill, minimum 270px per column.
- `.grid-2` / `.grid-3` — two or three columns on tablet and up.
- `.row` — form field rows; becomes one column on phones, two at 560px, three
  at 900px. Add `.cols-2` for a two-column row at most.
- Never use a fixed pixel width that can exceed a 320px-wide phone viewport.

## Buttons

Use `.btn` plus one modifier: `.amber` (primary call to action), `.ghost`
(secondary), `.danger`, `.sm` (smaller), `.block` (full width). All buttons and
form controls share the same minimum height, so they always line up.

## Status badges

Every piece of data carries a status badge, and the class always matches the
`statusLabel()` / `DataStatus` value: `b-officially_verified`, `b-admin_verified`,
`b-third_party`, `b-estimated`, `b-unverified`, `b-stale`, `b-unavailable`,
`b-demo`. Never invent a colour that implies more certainty than the data has.

## Responsive checkpoints

Test every change at these widths before calling it done:

| Width | Represents |
|---|---|
| 320px | Small phone |
| 390px | Common phone |
| 768px | Tablet portrait |
| 1024px | Tablet landscape / small laptop |
| 1440px | Desktop |

Watch for: horizontal scrollbars, text overflowing a card, the sticky header
covering an anchor target, and buttons that wrap awkwardly. `main` is a fluid
container; nothing should ever cause the page to scroll sideways.

## Accessibility

- Every form control has a `<label>` and, where relevant, `aria-describedby`.
- Dynamic regions use `role="status"` or `role="alert"` and `aria-live`.
- Respect `prefers-reduced-motion` (the stylesheet already does).
- Keep the skip link and visible focus ring. Never remove focus outlines.
- Colour is never the only signal — status is always written in words too.
