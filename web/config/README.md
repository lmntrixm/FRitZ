# تنظیمات و راه‌اندازی (`config`، `bootstrap`، `app/Providers`)

تنظیمات استاندارد Laravel، با چند تغییر برای هاست اشتراکی DirectAdmin. مقدارهای واقعی (رمزها، پایگاه داده) در `.env` هستند، نه این‌جا.

## فایل‌های تغییرکرده

| فایل | تغییر |
|---|---|
| `config/fritz.php` | تنظیمات مخصوص پروژه: `FRITZ_DEMO_DATA` (محصولات نمونه، فقط دامنه تست) و `DEPLOY_TOKEN` (کلید `/_deploy` و `/admin/setup`). |
| `config/app.php` | زبان پیش‌فرض فارسی (`fa`). منطقه زمانی هنوز `UTC` است. |
| `config/database.php` | موتور جدول‌ها `InnoDB` (قابل تغییر با `DB_ENGINE`)؛ هاست به‌طور پیش‌فرض MyISAM می‌سازد که تراکنش ندارد و برای به‌روزرسانی از فایل لازم است. |
| `config/filesystems.php` | دیسک `public` روی `public/media` (روی هاست `public_html/media`). |
| `session`، `cache`، `queue` | پیش‌فرض Laravel؛ در `.env.example` روی «فایل» و «هم‌زمان» گذاشته شده‌اند، چون هاست اشتراکی worker دائمی ندارد. |
| بقیه (`auth`، `logging`، `mail`، `services`) | پیش‌فرض Laravel. |
| `bootstrap/app.php` | ثبت مسیرها، نگهبان‌های `role` و `active`، قلاب `/_deploy` بیرون از گروه وب، و تشخیص چیدمان DirectAdmin (`../public_html` کنار برنامه). |
| `bootstrap/providers.php` | فهرست Provider ها. |
| `app/Providers/AppServiceProvider.php` | طول رشته ۱۹۱ برای کلیدهای MySQL قدیمی / MyISAM هاست. |

## انجام‌شده
- اجرای بی‌مشکل روی MySQL هاست (رفع خطای «Specified key was too long»).
- کش تنظیمات و مسیرها از طریق `/_deploy`.

## باقی‌مانده
- منطقه زمانی `Asia/Tehran` تا ساعت‌های «آخرین ویرایش» و گزارش‌ها به وقت ایران باشند (یک خط در `config/app.php`).
- تنظیم ایمیل (`MAIL_*` در `.env`) برای بازیابی رمز و دعوت کاربر.
