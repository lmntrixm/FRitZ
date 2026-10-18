# FRitZ catalog: project memory for Claude Code

This repo starts as a **design handoff**. The design was made on a Claude design canvas. The goal now is to build the real website and admin panel from it.

Read these first, in this order:
1. `PRODUCT.md`: who it is for, what it must do, and what must never appear.
2. `DESIGN.md`: the visual system (tokens, type, shape, depth, motion).
3. `.impeccable/surfaces/project-d-home-dc-html.md`: the direction contract for the catalog home.
4. `project/fz.css` (catalog) and `project/adm.css` (admin): the working token and component layer. Port it; do not reinvent it.

## Hard rules (from the client)
- Never show a price, stock status or cart anywhere public.
  - Internal price exists only in admin, labelled «فقط داخلی، عمومی نمی‌شود».
- The conversion is an inquiry list sent as a prefilled WhatsApp message.
  - The site logs the click, not the delivery.
- Protocols are KNX, Zigbee and Modbus only. IP and Wi-Fi are removed from the public catalog and appear only as inactive in admin.
- Language and type:
  - Persian, RTL, mobile-first.
  - Brand names, model codes and technical terms stay Latin and LTR-isolated (`unicode-bidi: isolate`).
  - Kalameh is the only typeface, self-hosted from `fonts/` (licence in `fonts/FontLicense.txt`).
    - The `.woff2` files are proprietary and git-ignored (public repo). Copy them into `fonts/` locally and upload them to the server by hand.
- Colour and themes:
  - Lime `#AFCB08` is used only for the single primary action, the inquiry count and the active state.
  - Text on lime is always ink.
  - Light theme is the default; a dark theme is included.
  - WCAG AA in both themes.
- Admin roles: `مدیر کل` (everything) and `ویرایشگر محتوا` (no settings, users or updates).
- Every dangerous admin action is confirmed, and the confirmation explains its effect.
- Hosting is **DirectAdmin** shared hosting (changed from cPanel by the client). The proposed stack is in `docs/PLAN.md` and waits for client approval before any code is written.

## Code layout (built)
- `web/`: the Laravel 12 app (PHP 8.2+, Blade, Alpine.js, MySQL; SQLite locally). It has no front-end build step.
  - `public/css/fz.css`: port of `project/fz.css`. `public/css/site.css`: the production layer (fixed overlays, page scroll).
  - `app/Support/Icons.php`: generated from `Icon.dc.html`. `resources/views/partials/art-sprite.blade.php`: generated from `ProductArt.dc.html`. Placeholder drawings only until real photos arrive.
  - Browser state (inquiry list, compare, theme) lives in `public/js/fz.js` as Alpine stores in localStorage.
  - `CatalogSeeder` holds the reference data. `DemoProductSeeder` holds the design's sample products, for the test domain only (`FRITZ_DEMO_DATA`).
- `docs/PLAN.md`: stack, schema and phases. `docs/DEPLOY.md`: DirectAdmin over FTP (no SSH), GitHub Actions in `.github/workflows/deploy.yml`.
- Checks: `cd web && php artisan test && ./vendor/bin/pint --test`. `node .claude/skills/design-taste-frontend/scripts/lint.mjs web/public/css web/public/js/fz.js web/resources/views`.

## How to read the design files (`project/`)
- `*.dc.html` files are design artboards, not production pages.
  - They need the canvas runtime (`support.js`), which is not included, so they will not run standalone.
  - Read them as precise markup and spec: structure, copy, states, spacing.
  - The data each screen shows sits in the `renderVals()` script at the bottom of each file.
- `canvas.json` lists every artboard with its title and page group.
- File prefixes:
  - `Main.dc.html`: clickable mobile flow (home → filter → product → inquiry → WhatsApp). Its logic shows the intended behaviour.
  - `M-*`: mobile 390 screens.
  - `D-*`: desktop 1440 screens.
  - `T-*`: tablet 834 screens.
  - `DesignSystem`, `Components`: the foundations and every component state.
  - `A-*`: admin panel. `A-FlowEdit` and `A-FlowUpdate` are the two clickable admin flows: edit → publish, and encrypted `.fritz` update → rollback.
- `Icon.dc.html` holds the icon set as single-path SVGs. `ProductArt.dc.html` holds placeholder product drawings.
- Logos:
  - `fritz-logo.png` is the client's logo.
  - `fritz-logo-dark.png` recolours only the grey «SMART SOLUTIONS» line, for dark grounds.
  - Ask the client for an SVG before launch.

## Content status
- Real product photos (6–10) and completed-project photos (4–6) have not been supplied yet. Keep labelled grey slots and never invent images.
- No testimonials, client names or statistics have been provided. Do not invent any.
- Model codes other than `ZVIZ50A` and `R20A` are samples.
- Phone, email, address and WhatsApp number are bracketed placeholders.

## Design skills in this repo
`.claude/skills/impeccable` and `.claude/skills/design-taste-frontend` are the two design skills this design was made with. Use them for any UI work and keep their findings green:
- `impeccable detect`
- the taste-skill `lint.mjs`

The admin panel is an "Operate" surface: precision over expression. Table row dividers are the one place a line is allowed.
