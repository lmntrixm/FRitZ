# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Stack
Design canvas only at this stage (Design artifact, .dc.html artboards). Production hosting is DirectAdmin shared hosting (the client switched from cPanel). Proposed stack: see docs/PLAN.md, awaiting approval.

## Users
- End customers (homeowners, villa and apartment owners, people renovating) looking for smart-home equipment for their own project, mostly on a phone. Job: find the right product, read its specs, compare a few, and ask for a price.
- Installers and project contractors comparing models by code and protocol.
- Internal FRitZ team (two roles: «مدیر کل» and «ویرایشگر محتوا») who maintain ~400 products, categories, per-category filters, media, encrypted-file updates and inquiries in an admin panel.

## Product Purpose
An online product catalog for FRitZ Smart Solutions that turns browsing into a price inquiry. No prices, no stock status, no cart: the visitor builds an inquiry list and sends it as a ready-made WhatsApp message. Success = a well-formed inquiry (product names, model codes, quantities, optional name and project) arriving on FRitZ's WhatsApp.

## Positioning
FRitZ is one supplier that covers many brands and protocols side by side, is an official representative of brands, gives specialist technical advice on which equipment fits a project, and carries the job through to complete installation and commissioning. A single-brand dealer or a marketplace cannot truthfully claim all four at once.

## Operating Context
- ~400 products, 10 brands (Zennio, Loxone, Schneider, Orvibo, ABB, ITC, Ountech, Panasonic, Akuvox, Akubela).
- Active protocols: KNX, Zigbee, Modbus. IP and Wi-Fi were removed from the public catalog (shown as inactive in admin).
- 14 categories (touch panel, smart switch, thermostat, relay & actuator, behind-switch relay, dimmer & driver, sensor, server, interface module, I/O, power supply, curtain, intercom & door entry, audio). Each category has its own filters defined in the database.
- Inquiry goes out through WhatsApp; the catalog logs the click, not the delivery.
- Product updates arrive as an AES-encrypted package (.fritz) or Excel, with diff preview, backup and rollback.
- Persian, RTL; brand names, model codes and technical terms stay Latin/LTR. Mobile-first.

## Capabilities and Constraints
- Never show prices or stock on the public catalog; internal price exists only in admin, labeled «فقط داخلی».
- Compare up to 3 products. Category-specific filters. Product-finder wizard: need → protocol → brand → suggestions with a small scoped filter (gangs, colour).
- Sticky search + filter on the catalog home.
- Admin: dangerous actions always confirmed with their effect explained.

## Brand Commitments
- Name: FRitZ Smart Solutions. Brand colour #AFCB08 (lime), used for emphasis only. Light theme default with a dark theme.
- Typeface: Kalameh (supplied by the user) for the whole interface.
- Tone: technical and premium, not discount retail.
- Standing direction: the client replaced the first direction («استاندارد پریمیوم», Gira / Busch-Jaeger / Loxone as the bar) with a work app in the ClickUp manner for both the catalog and the admin, keeping FRitZ's brand (Kalameh, logo, lime rules). See DESIGN.md.

## Evidence on Hand
- FRitZ logo supplied (lime wordmark «FRitZ» with grey «SMART SOLUTIONS»; PNG, transparent). A dark-ground variant recolours the grey line only.
- The user also has real product photographs and photographs of completed projects, not yet uploaded; image slots wait for them. Do not fabricate them.
- No testimonials, client names, press or business statistics have been provided; do not invent any.
- Model codes other than ZVIZ50A and R20A in the design are samples.

## Product Principles
1. The inquiry is the conversion: every screen should make adding to the list and sending it effortless.
2. Model code and protocol are first-class: experts search by code, beginners are guided by need.
3. One honest source across brands: comparison stays neutral, specs come from the datasheet.
4. Trust through craft and real work: completed projects and real product photos over claims.

## Accessibility & Inclusion
WCAG AA contrast in both themes, visible focus, keyboard support, 44px touch targets on mobile, correct bidi for Latin codes inside Persian text.
