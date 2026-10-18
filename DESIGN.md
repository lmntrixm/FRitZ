# FRitZ — Design

Built world: the category canon («استاندارد پریمیوم»), played straight. Reference bar: Gira, Busch-Jaeger, Loxone product sites.

## Principles
- The product is the page. One dominant product image per view, on a grey field.
- Tone steps instead of lines. No 1px outline boxes, no shadows at rest. Shadows only on things that float: sheet, drawer, toast, compare bar, the stuck search bar.
- Two shapes. Full pills for everything you can touch; square corners for surfaces and images.
- Lime #AFCB08 appears in three places only: the single primary action, the inquiry count, and the active state. Text on lime is always ink.

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
