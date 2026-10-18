# FRitZ — Design

Built world: a work app in the ClickUp manner, in FRitZ's own materials, chosen by the client for both the catalog and the admin (it replaced the earlier «استاندارد پریمیوم» canon). Brand commitments below still hold.

## Principles
- One shell everywhere: the catalog tree beside a white work surface (12px radius, 1px hairline ring) on the grey app ground; a sticky top bar; Ctrl+K search.
- Hairlines (`--line`) separate regions, cards, widgets and rows. Shadows only on things that float: palette, side panels, drawer, toast, compare bar, menus.
- Shapes: pills for buttons and chips; 8px for fields and small controls; 10–14px for cards, widgets and panels.
- Lime #AFCB08 appears in three places only: the single primary action, the inquiry count, and the active state (tree row `acS`, tab underline, tab-bar icon). Text on lime is always ink.

## Catalog
- **Sidebar (≥1024px):** logo, «محصول یا کد مدل Ctrl K», pages (خانه، راهنمای انتخاب، مقایسه، درباره و تماس), every category under its parent with its pictogram and published count, brands (collapsed), a WhatsApp line and the theme switch. Under 1024px it is a drawer.
- **Top bar:** breadcrumb (کاتالوگ › دسته › محصول), the search field, the ink «لیست استعلام» pill with the lime count. Phones: menu or back, title, theme; a bottom tab bar carries خانه، دسته‌ها، جست‌وجو، مقایسه، استعلام with counts, hidden on pages with their own sticky action.
- **Home:** widgets, not a long scroll: headline with the search field and the finder (the page's one lime action), all categories as tiles, the finder's three steps, a line to a specialist, featured products, brands.
- **Category:** page head with the category pictogram, a view bar (کارت / ردیف tabs, sort, filter with its count) sticky under the top bar; filters open as a side panel on desktop and a sheet on phones.
- **Product:** a task view: gallery, identity, specs and description in the main column; a sticky properties panel with the one action (add to inquiry), datasheet, compare and the properties (brand, code, category, protocol, origin). Phones keep the sticky action bar.
- Overlays: on desktop sheets become a side panel on the inline end (left); on phones they stay bottom sheets.

## Type
- Kalameh (300–900, self-hosted from the user's files) is the only typeface. Its own Latin glyphs set brand names and model codes.
- Persian letter-spacing stays 0 so the letters still join.
- Display: 38 mobile / 68–72 desktop, weight 800. Section heading: 28 / 44. Text: 16–19.
- Model codes are LTR-isolated and use tabular numbers.

## Colour (light / dark)
| Token | Light | Dark |
|---|---|---|
| bg | #FFFFFF | #0F0F0E |
| sf2 | #F3F3F1 | #1C1C1A |
| sf3 | #E7E7E3 | #282826 |
| tx | #141414 | #F3F3EF |
| tx2 | #4D4D49 | #BDBDB6 |
| tx3 | #62625D | #9C9C95 |
| ac | #AFCB08 | #AFCB08 |
| acT | #566400 | #C5DE33 |

All text pairs pass AA. Control borders (bd2) reach 3:1 against sf2.

## Admin
A work app in the ClickUp manner (client's request), in FRitZ's own materials. The catalog rules above (no lines, square surfaces) do not apply inside `/admin`.
- **Shell:** ink icon rail (72px, `--rail` #141414 / #080807) with the logo and section icons; active section = lime icon on a raised ink tile; the new-inquiry count is a lime badge. Beside it the catalog tree (248px) on the grey app ground: saved views (همه، ناقص‌ها، پیش‌نویس‌ها، حذف‌شده‌ها) and every category with its own product pictogram, count and completeness meter. The tree can be closed and remembers it.
- **Work surface:** one white field (`--surf`, dark #141413), 12px radius, 1px hairline ring, inset 8px from the ground. Top bar: breadcrumb, «جست‌وجو یا فرمان… Ctrl K», ink «+ جدید» menu, account menu.
- **Ctrl+K palette:** actions, sections, categories and products (lookup) in one list; arrow keys and Enter.
- **Lines:** hairlines (`--line`) separate regions, table rows and panels. Panels are hairline-ringed fields with a 10px radius. Shadows only on things that float: menus, palette, peek, modal, toast.
- **Density:** 40px table rows, 13–14px text, 36px controls under a mouse (44px on touch).
- **Status chips:** filled, 6px radius, white text: published `--okF` #2F6B3A, warning `--wnF` #8A5D00, deleted `--erF` #B42318; draft is grey (`sf3` with ink). Each chip on a product is also its menu.
- **Products:** three views of the same filtered list (لیست grouped and collapsible by status, category or brand; جدول paged; بُرد columns with drag between statuses). A row opens in a side peek (left in RTL, bottom sheet on phones); Ctrl+click opens the full form.
- **Lime in admin:** the page's one primary action, counts, the active rail item, the active tree row (`acS`), the active view tab underline. The global «+ جدید» is ink, never lime, so a page never shows two lime buttons.
- Under 900px the rail and tree become one drawer; list rows drop secondary columns and show the model code under the name.

## Logo
- The supplied PNG is used as is on light grounds.
- `fritz-logo-dark.png` recolours only the grey «SMART SOLUTIONS» line, for dark grounds and the ink footer.

## Motion
- Easing is cubic-bezier(.16,1,.3,1). Hover is a 200ms tone step. The skeleton is a flat opacity pulse.
- Motion turns off under `prefers-reduced-motion`.
