# UI Design System — "Aurora"

The interface was redesigned around one stylesheet and a handful of shared Blade pieces, so every
page — landing, auth, the four role consoles and the commuter screens — shares one look.

| Piece | Where | What it does |
| --- | --- | --- |
| Tokens + restyle | `public/css/theme.css` | Colour, radius, shadow and type tokens (light and dark). Re-points the Tailwind utilities used across the views (greys, tiny type, boxy radii, accent stripes) at the tokens. |
| Theme partial | `resources/views/partials/theme.blade.php` | Loads Plus Jakarta Sans, `theme.css`, and the Tailwind palette (`blue` = the indigo brand). Included by `head-scripts`, `commuter-head-scripts`, the landing page and the auth shell. |
| Console navigation | `components/layout/sidebar.blade.php` | Floating dark rail (collapsible), user card, dark-mode switch; floating dock + drawer on mobile. |
| Auth shell | `components/auth/shell.blade.php` | Split-screen layout shared by login, register, driver registration, forgot/reset password and the email screens. |
| Landing page | `home.blade.php` | Hero with product preview, feature bento, role cards, call-to-action. |
| Error pages | `resources/views/errors/*` | Branded 403 / 404 / 419 / 429 / 500 / 503. |

## Design choices

* **Palette** — indigo → violet brand on cool neutrals; dark mode is deep navy rather than pure black.
  Each menu area keeps its hue (see `config/menu.php`) so colour still means the same thing everywhere.
* **Type** — Plus Jakarta Sans. Micro-labels (7–11 px, ALL CAPS, wide tracking) became 11–13.5 px sentence
  case. The size scale is generated into the bottom of `theme.css`.
* **Shape** — 16–30 px radii, soft layered shadows, no hard 4 px accent stripes (they glow from the card corner).
* **Accessibility** — contrast rules in `partials/accessible-colors.blade.php` still apply (≥ 4.5:1);
  keyboard focus is always visible; motion respects `prefers-reduced-motion`.

## Changing the look

Edit the tokens at the top of `public/css/theme.css` (`--sc-brand`, `--sc-grad`, `--sc-bg`, …). To change
the brand hue everywhere also update `window.SC_COLORS.blue` in `partials/theme.blade.php` and the
`--cc-blue*` values in `partials/accessible-colors.blade.php`.
