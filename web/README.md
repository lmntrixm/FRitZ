# FRitZ catalog — web app

Laravel 12 (PHP 8.2+), Blade, Alpine.js and MySQL/MariaDB. No front-end build step: CSS and JS are plain files in `public/`.

- Design source: `../project/` (artboards) and `../DESIGN.md`. `public/css/fz.css` is a port of `../project/fz.css`. Change tokens there; the production layout goes in `public/css/site.css`.
- Local: `composer setup`, then `php artisan serve`. Copy the Kalameh `.woff2` files into `public/fonts/` (they are git-ignored: proprietary font, public repo).
- Tests: `php artisan test`. Style: `./vendor/bin/pint`.
- Deploy: `../docs/DEPLOY.md` (DirectAdmin over FTP; no SSH).
