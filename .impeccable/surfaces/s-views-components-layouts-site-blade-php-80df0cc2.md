---
version: 1
slug: "s-views-components-layouts-site-blade-php-80df0cc2"
primary_target: "web/resources/views/components/layouts/site.blade.php"
related_targets: ["web/resources/views/catalog/home.blade.php","web/resources/views/catalog/category.blade.php","web/resources/views/catalog/product.blade.php"]
---

# Public catalog (Operate, with a Persuade home)

Scope: every public page (`/`, `/c/*`, `/p/*`, search, compare, finder, about). Replaces the «استاندارد پریمیوم» direction of `project/D-Home` and `M-*` by the client's explicit request («خود اپلیکیشنم ری دیزاین کن», ClickUp as the reference).
Audience: homeowners and installers looking for smart-home equipment, mostly on a phone; installers on desktop compare by code.
Job: find a product (by need, category, code), read its specs, compare up to 3, send one WhatsApp inquiry.
Constraints (unchanged): no price, stock or cart; inquiry by WhatsApp; KNX/Zigbee/Modbus only; Kalameh; lime only for the one primary action, the inquiry count and the active state; light default + dark; AA; Latin codes isolated; no invented images, testimonials or numbers.

## Direction contract
seed: none (direction pinned by the user: ClickUp-style work app, as in the admin)

THESIS: The catalog is an app you work in, not a brochure you scroll: the categories live as a tree beside the products, search is one Ctrl+K away, any list reads as cards or rows, and a product reads like a task with its properties panel and one action. It refuses the long marketing scroll and the one-column phone page stretched onto a desktop.

OWN-WORLD: Same materials as the admin: grey app ground, sidebar on the ground with the logo, the category tree (pictogram, name, count) and the brands; one white work surface (12px radius, hairline ring); compact pills and 8px-radius controls; filled lime only on the primary action, counts and the active row.

STORY: land → see every category with its count and the finder → open a category from the tree → filter, switch cards/rows → open a product → add to inquiry from the properties panel → send on WhatsApp.

FIRST VIEWPORT: sidebar tree + top bar (Ctrl K search, inquiry with lime count) + a home of widgets: headline with search and the finder action, the category grid, featured products, brands.

SIGNATURE: the category tree with each category's own product pictogram, shared with the admin, and the product page as a task view with a properties panel.

RISK: a work-app look can feel cold to homeowners; the home keeps a warm headline and the finder as the one lime action. On phones the sidebar becomes a drawer and a bottom tab bar carries home, categories, search, compare and the inquiry count.
