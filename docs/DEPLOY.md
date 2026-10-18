# راهنمای نصب روی هاست DirectAdmin (بدون SSH)

کد با GitHub Actions ساخته می‌شود و با **FTP** روی هاست می‌رود. بعد از آپلود، workflow یک آدرس محافظت‌شده (`POST /_deploy`) را صدا می‌زند تا migrationها اجرا و cacheها ساخته شوند. روی هاست به ترمینال نیازی نیست.

## ساختار روی هاست

```
/home/<USER>/domains/wooksmart.com/
├── fritz-app/      ← کد Laravel (بیرون از وب؛ .env اینجاست)
└── public_html/    ← فقط فایل‌های عمومی: index.php، css، js، img، fonts، media
```

- `public_html/index.php` خودش پوشه `../fritz-app` را پیدا می‌کند و symlink لازم نیست.
- فایل‌های آپلودشده در پنل (عکس و دیتاشیت) در `public_html/media` ذخیره می‌شوند.

## کارهای یک‌باره

### ۱. نسخه PHP
- در DirectAdmin بخش **PHP Version Selector** (یا **Select PHP version**) را باز کنید.
- برای دامنه **PHP 8.2 یا بالاتر** (ترجیحاً 8.3) را انتخاب کنید.
  - Laravel 12 روی 8.1 اجرا نمی‌شود.
  - خود PHP 8.1 هم از پایان ۲۰۲۵ دیگر به‌روزرسانی امنیتی نمی‌گیرد.
- این افزونه‌ها باید روشن باشند: `pdo_mysql`، `mbstring`، `openssl`، `intl`، `gd`، `fileinfo`، `zip`.

### ۲. دیتابیس
1. در **MySQL Management** یک دیتابیس و یک کاربر بسازید.
2. نام دیتابیس، نام کاربر و رمز را یادداشت کنید.

### ۳. اکانت FTP
- از **FTP Management** یک اکانت بسازید که به `/home/<USER>` دسترسی داشته باشد. اکانت اصلی هم کار می‌کند.
- اگر هاست FTPS (FTP امن) ندارد، در `.github/workflows/deploy.yml` مقدار `protocol: ftps` را به `ftp` تغییر دهید.

### ۴. فایل `.env`
1. با File Manager پوشه `domains/wooksmart.com/fritz-app/` را بسازید.
2. محتوای `web/.env.example` را در فایلی به نام `.env` در همان پوشه بگذارید.
3. این مقدارها را پر کنید:

| کلید | مقدار |
|---|---|
| `APP_KEY` | یک کلید `base64:` با ۳۲ بایت تصادفی. روی سیستم خودتان با `php artisan key:generate --show` بسازید. |
| `APP_URL` | `https://wooksmart.com` |
| `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | از مرحله ۲ |
| `FRITZ_DEMO_DATA` | روی دامنه تست `true` (محصولات نمونه طراحی). روی سایت اصلی **حتماً** `false`. |
| `DEPLOY_TOKEN` | یک رشته تصادفی بلند (۴۰ نویسه یا بیشتر) |

### ۵. فونت Kalameh
- فونت مالکیتی است و در مخزن عمومی GitHub نیست.
- فایل‌های `kalameh-*.woff2` را یک بار با File Manager در `public_html/fonts/` آپلود کنید.
- آپلودهای بعدی آن‌ها را پاک نمی‌کنند.

### ۶. تنظیمات مخزن GitHub
در مخزن به **Settings → Secrets and variables → Actions** بروید و این‌ها را اضافه کنید.

**Secrets:**
- `FTP_SERVER`: آدرس FTP، مثلاً `ftp.wooksmart.com`
- `FTP_USERNAME`
- `FTP_PASSWORD`
- `DEPLOY_TOKEN`: همان مقداری که در `.env` نوشتید

**Variables:**
- `FTP_APP_DIR`: `domains/wooksmart.com/fritz-app/`
- `FTP_WEB_DIR`: `domains/wooksmart.com/public_html/`
- `SITE_URL`: `https://wooksmart.com`

اگر FTP شما مستقیم داخل `domains/wooksmart.com` باز می‌شود، دو مسیر را به `fritz-app/` و `public_html/` کوتاه کنید.

### ۷. SSL
در DirectAdmin بخش **SSL Certificates** را باز کنید و گواهی رایگان Let's Encrypt بگیرید.

## هر بار انتشار
- هر push یا merge روی شاخه `main` این مراحل را اجرا می‌کند:
  1. تست‌ها
  2. ساخت
  3. آپلود FTP
  4. migration
- بار اول آپلود پوشه `vendor` چند دقیقه طول می‌کشد. دفعه‌های بعد فقط فایل‌های تغییرکرده آپلود می‌شوند.
- در اولین اجرا، اگر دیتابیس خالی باشد، دسته‌ها، برندها، پروتکل‌ها و تنظیمات پایه ساخته می‌شوند.
  - بعد از آن دیگر دوباره اجرا نمی‌شود تا ویرایش‌های پنل از بین نرود.
- برای اجرای دستی از تب **Actions** گزینه **Run workflow** را بزنید.

## Cron Job (برای پنل مدیریت، مرحله ۳ به بعد)
در DirectAdmin بخش **Cron Jobs** یک کار هر دقیقه با این دستور بسازید:

```
/usr/local/bin/php /home/<USER>/domains/wooksmart.com/fritz-app/artisan schedule:run >> /dev/null 2>&1
```

مسیر PHP ممکن است در هاست شما فرق کند. آن را از پشتیبانی هاست بپرسید، مثلاً `/usr/local/php83/bin/php`.

## اجرای محلی
```
cd web
cp ../fonts/*.woff2 public/fonts/   # فونت‌ها از zip طراحی
composer setup                       # .env، کلید، دیتابیس SQLite و داده نمونه
php artisan serve                    # http://127.0.0.1:8000
php artisan test
```
