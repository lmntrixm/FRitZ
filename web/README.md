# برنامه FRitZ (`web/`)

برنامه اصلی کاتالوگ و پنل مدیریت: **Laravel 12** (PHP 8.2 به بالا)، **Blade**، **Alpine.js** و **MySQL/MariaDB** روی هاست (SQLite روی کامپیوتر خودتان). مرحله build ندارد: CSS و JS فایل‌های ساده در `public/` هستند.

نقشه کل پروژه، مرحله‌ها و پیشنهادها: [`../PROJECT-STATUS.md`](../PROJECT-STATUS.md).

## پوشه‌ها و راهنمای هر کدام

| پوشه | کار | راهنما |
|---|---|---|
| `app/Http/Controllers` | پاسخ به صفحه‌های عمومی کاتالوگ و نصب بدون SSH | [README](app/Http/Controllers/README.md) |
| `app/Http/Controllers/Admin` | همه صفحه‌های پنل مدیریت | [README](app/Http/Controllers/Admin/README.md) |
| `app/Http/Middleware` | نگهبان نقش‌ها و حساب‌های غیرفعال | [README](app/Http/Middleware/README.md) |
| `app/Models` | جدول‌های پایگاه داده به شکل کلاس | [README](app/Models/README.md) |
| `app/Support` | منطق اصلی: فیلتر، جست‌وجو، اکسل، رمزنگاری، تکمیل اطلاعات و… | [README](app/Support/README.md) |
| `config` و `bootstrap` | تنظیمات برنامه و چیدمان DirectAdmin | [README](config/README.md) |
| `database` | ساختار جدول‌ها و داده اولیه | [README](database/README.md) |
| `lang/fa` | پیام‌های فارسی خطا و ورود | [README](lang/README.md) |
| `resources/views` | همه صفحه‌ها (HTML) | [README](resources/views/README.md) |
| `routes` | آدرس‌های سایت و دستورهای خط فرمان | [README](routes/README.md) |
| `tests` | ۵۱ آزمون خودکار | [README](tests/README.md) |
| `public` | ریشه وب (روی هاست: `public_html`) | پایین همین صفحه |

## فایل‌های همین پوشه

| فایل | کار |
|---|---|
| `.env.example` | الگوی تنظیمات سرور (پایگاه داده، `APP_KEY`، `DEPLOY_TOKEN`، `FRITZ_DEMO_DATA`). روی هاست به `.env` تغییر نام می‌دهد. |
| `artisan` | ابزار خط فرمان Laravel. |
| `composer.json` / `composer.lock` | فهرست کتابخانه‌ها با نسخه دقیق. سکوی PHP روی 8.2.0 قفل شده تا با هاست بخواند. |
| `phpunit.xml` | تنظیم آزمون‌ها (SQLite در حافظه). |
| `scripts/local-env.php` | ساخت `.env` محلی با SQLite و داده نمونه، برای `composer setup`. |
| `.editorconfig`، `.gitattributes`، `.gitignore` | قالب کد و فایل‌هایی که در گیت نمی‌روند (`.env`، `vendor`، فونت‌ها). |

## پوشه `public` (ریشه وب)

این پوشه روی هاست `public_html` است، پس راهنمای آن اینجاست، نه داخل خود پوشه (هرچه در `public` باشد برای همه قابل دیدن است).

| فایل | کار |
|---|---|
| `index.php` | ورودی همه درخواست‌ها. خودش برنامه را در `../` (محلی) یا `../fritz-app` (هاست) پیدا می‌کند؛ symlink لازم نیست. |
| `.htaccess` | هدایت همه آدرس‌ها به `index.php` روی Apache/LiteSpeed. |
| `.user.ini` | سقف آپلود روی هاست: ۲۴ مگابایت فایل، ۶۴ مگابایت فرم. |
| `robots.txt` | پیش‌فرض اجازه خزیدن؛ تا «اجازه ایندکس» در تنظیمات روشن نشود، صفحه‌ها `noindex` دارند. |
| `css/fz.css` | توکن‌ها و اجزای پایه، پورت `project/fz.css` (رنگ، فونت، دکمه، کارت، شیت). |
| `css/site.css` | پوسته کاتالوگ به سبک ClickUp: منوی کناری، نوار بالا، نوار پایین موبایل، Ctrl+K، خانه، صفحه محصول. |
| `css/adm.css` | اجزای پنل، پورت `project/adm.css`. |
| `css/admin.css` | پوسته پنل به سبک ClickUp: نوار آیکون، درخت، نمای لیست/جدول/بُرد، پنل کناری. |
| `js/fz.js` | وضعیت مرورگر: لیست استعلام، مقایسه، تم، پیام کوتاه، جست‌وجوی Ctrl+K کاتالوگ. |
| `js/admin.js` | پنجره تأیید کارهای خطرناک، Ctrl+K پنل، تغییر وضعیت در جا، بُرد، پنل کناری. |
| `js/alpine*.min.js` | کتابخانه Alpine.js و افزونه focus (محلی، بدون اینترنت). |
| `img/` | لوگوی FRitZ برای زمینه روشن و تیره. |
| `fonts/` | فونت Kalameh (در گیت نیست؛ مجوز خصوصی). |
| `media/` | عکس و دیتاشیت‌های آپلودی محصولات. |

## اجرا و بررسی

```bash
composer setup              # بار اول: .env محلی، پایگاه داده و داده نمونه
php artisan serve           # http://127.0.0.1:8000
php artisan fritz:user you@example.com --name="نام" --role=admin   # ساخت مدیر
php artisan test            # آزمون‌ها
./vendor/bin/pint           # قالب کد
```

استقرار روی هاست: [`../docs/DEPLOY.md`](../docs/DEPLOY.md).
