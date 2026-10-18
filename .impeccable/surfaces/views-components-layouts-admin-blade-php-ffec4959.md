---
version: 1
slug: "views-components-layouts-admin-blade-php-ffec4959"
primary_target: "web/resources/views/components/layouts/admin.blade.php"
related_targets: ["web/resources/views/admin/products/index.blade.php"]
---

# Admin panel (Operate)

Scope: the whole FRitZ admin (`/admin`), shell first, then products. Mode: Operate.
Audience: the FRitZ team (مدیر کل، ویرایشگر محتوا), ~400 products, daily on desktop, sometimes on a phone.
Job: find a product fast, see what is incomplete, change status, run file updates, answer inquiries.
Constraints: FRitZ brand stays (Kalameh only; lime only for the one primary action, counts and active state; text on lime is ink; light default + dark; AA). Persian RTL, Latin codes isolated. Every dangerous action confirmed.
User pin: «شبیه ClickUp» for structure, workflow and visual feel; FRitZ brand kept. No ClickUp branding, purple or logo.

## Direction contract
seed: none (direction pinned by the user: ClickUp-style work app)

THESIS: The admin is one work app, not a stack of pages: the catalog lives as a tree beside the work, every list can be read as a grouped list, a table or a board, any row opens in a side peek without leaving the list, and everything is one Ctrl+K away. It refuses the brochure-admin of big headings, card grids and one page per click.

OWN-WORLD: Ink icon rail (#141414, dark #0A0A09) carrying the lime FRitZ mark and section icons; active = lime icon on a raised ink tile. Beside it a grey context panel (sf2) with the catalog tree: views and the 14 categories with their own product pictograms and counts. White work area, hairline dividers, compact 40px rows, 13–14px Kalameh. Status as filled pills with a coloured dot: green published, grey draft, amber incomplete, red deleted.

STORY: Open the panel → the tree shows where the catalog is thin → click a category → list grouped by status → click a row → peek shows completeness and quick actions → change status inline.

FIRST VIEWPORT: rail + tree + top bar (search «جست‌وجو یا فرمان… Ctrl K», lime «+ جدید») + products grouped list with collapsible status groups.

SIGNATURE: the category tree drawn with each category's own product pictogram and a completeness meter per category; the same pictograms reappear on board cards.

RISK: density on a phone; under 900px rail and tree collapse into one drawer and views fall back to the list.
