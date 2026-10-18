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
- The working ground is grey (`--app`); panels, stats and forms are white fields with no border.
- Data tables keep a tone row divider, which is the one place a line is allowed.
- Navigation: the active item is an ink pill with a lime icon. Tabs are a segmented pill.

## Logo
- The supplied PNG is used as is on light grounds.
- `fritz-logo-dark.png` recolours only the grey «SMART SOLUTIONS» line, for dark grounds and the ink footer.

## Motion
- Easing is cubic-bezier(.16,1,.3,1). Hover is a 200ms tone step. The skeleton is a flat opacity pulse.
- Motion turns off under `prefers-reduced-motion`.
