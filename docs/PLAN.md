# نقشه پیاده‌سازی کاتالوگ FRitZ و پنل مدیریت

> وضعیت: **استک تأیید شد.** مرحله ۰ و ۱ (کاتالوگ موبایل) ساخته شده‌اند. کد در پوشه `web/` است.
> هاست: اشتراکی **DirectAdmin**، PHP فعلی 8.1 (باید به 8.2 یا بالاتر برسد)، بدون SSH. راهنمای نصب: `docs/DEPLOY.md`.
> دامنه تست: `wooksmart.com`. پنل مدیریت: `/admin`.

---

## ۱) استک پیشنهادی

| لایه | انتخاب | چرا |
|---|---|---|
| زبان سرور | **PHP 8.3** (حداقل 8.2) | روی هر هاست DirectAdmin بدون تنظیم خاص اجرا می‌شود. Node.js روی هاست اشتراکی معمولاً یا نیست یا ناپایدار است. |
| فریم‌ورک | **Laravel 12** | احراز هویت، نقش‌ها، CSRF، اعتبارسنجی، migration و صف/کران آماده دارد. برای پنل با دو نقش، به‌روزرسانی رمزنگاری‌شده و بازگردانی، از صفر نوشتنش وقت تلف‌کردن است. |
| دیتابیس | **MariaDB / MySQL** (`utf8mb4_unicode_ci`) | در همه هاست‌های DirectAdmin هست. ۴۰۰ محصول برای MySQL هیچ است. |
| رندر صفحات | **Blade** (HTML سمت سرور) | سئوی واقعی برای صفحه محصول و دسته. روی موبایل سریع است و ساخت فرانت‌اند روی سرور لازم ندارد. |
| تعامل سمت کاربر | **Alpine.js** (حدود ۱۵ کیلوبایت) | برای شیت فیلتر، کشوی استعلام، مقایسه، ویزارد و تم تیره کافی است. SPA و React لازم نیست. |
| CSS | پورت مستقیم `fz.css` و `adm.css` | طبق CLAUDE.md: «پورت کن، از نو اختراع نکن». بدون Tailwind. |
| ساخت فایل‌ها | **بدون مرحله build** | CSS و JS فایل ساده در `public/` هستند و Alpine.js هم داخل همان پوشه است. Node نه روی هاست لازم است، نه در CI. (به‌جای Vite که اول پیشنهاد شده بود.) |
| اکسل | **OpenSpout** | سبک و کم‌حافظه. PhpSpreadsheet روی هاست اشتراکی به سقف حافظه می‌خورد. |
| رمزنگاری `.fritz` | `openssl` در PHP با **AES-256-GCM** | همان الگوریتمی که در طراحی A-Settings آمده. افزونه اضافه نمی‌خواهد. |
| تصویر | GD یا Imagick برای ساخت **WebP** در چند اندازه | عکس‌های واقعی سنگین‌اند. هر عکس یک بار هنگام آپلود کوچک می‌شود. |

### استقرار روی DirectAdmin
- ساختار پوشه‌ها:
  - کد Laravel در `domains/<site>/fritz-app` (بیرون از وب) قرار می‌گیرد.
  - محتوای `public/` در `public_html` قرار می‌گیرد.
  - `index.php` خودش مسیر برنامه را پیدا می‌کند؛ symlink لازم نیست.
  - فایل‌های آپلودی در `public_html/media` ذخیره می‌شوند.
- انتقال کد: **GitHub Actions** پس از هر push روی شاخه `main`:
  - تست‌ها را اجرا می‌کند و `composer install --no-dev` می‌زند.
  - خروجی را با **FTP** روی هاست می‌فرستد (SSH نداریم).
  - بعد آدرس محافظت‌شده `POST /_deploy` را صدا می‌زند تا migration و cache اجرا شوند.
  - جزئیات در `docs/DEPLOY.md`.
- کران: یک Cron Job در DirectAdmin هر دقیقه `php artisan schedule:run` را اجرا می‌کند. این برای پاک‌سازی دعوت‌های منقضی و نسخه‌های پشتیبان قدیمی است.
- صف: روی هاست اشتراکی worker دائمی نداریم. کارهای سنگین، مثل اعمال فایل به‌روزرسانی، یا هم‌زمان (sync) اجرا می‌شوند یا با کران. برای ۴۰۰ محصول چند ثانیه بیشتر طول نمی‌کشد.
- پیش از شروع از هاست بپرسید:
  - نسخه PHP قابل انتخاب
  - دسترسی SSH
  - امکان symlink
  - سقف `upload_max_filesize` (دیتاشیت تا ۲۰ مگابایت)
  - SSL رایگان (Let's Encrypt)

### فونت
- Kalameh مالکیتی است و این مخزن GitHub **عمومی** است.
- برای همین فایل‌های `.woff2` در git نیستند (`.gitignore`). مستقیم روی هاست آپلود می‌شوند.
- کد ۶ رقمی لایسنس باید در `fonts/FontLicense.txt` درج شود.

---

## ۲) ساختار دیتابیس

```
users                 id, name, email*, password, role ENUM('admin','editor'),
                      status ENUM('active','disabled'), last_login_at, timestamps
user_invites          id, email, role, token_hash, invited_by→users, expires_at (۴۸ ساعت), used_at

brands                id, name (Latin), slug*, country, description, logo_media_id→media,
                      is_active, sort
protocols             id, name, code*, description, is_active, sort
                      (بذر اولیه: KNX، Zigbee، Modbus فعال؛ IP و Wi-Fi غیرفعال)

categories            id, parent_id→categories (گروه‌های درختی مثل «کنترل و رابط کاربری»),
                      name, slug*, icon, sort, is_active
category_attributes   id, category_id, key* (مثل display_size, box_size), label,
                      type ENUM('select','multi','number','bool','text'), unit,
                      num_min, num_max, num_step,
                      is_filter (در فیلتر عمومی), is_compare (در جدول مقایسه),
                      required_to_publish, sort
attribute_options     id, attribute_id, value, label, sort

products              id, name (فارسی), model_code* (یکتا؛ کلید تطبیق در به‌روزرسانی),
                      slug*, brand_id, category_id, country_of_origin, description,
                      status ENUM('draft','published','archived'), is_featured,
                      internal_price DECIMAL NULL  ← «فقط داخلی، عمومی نمی‌شود»؛ هرگز در API/صفحه عمومی
                      created_by, updated_by, published_at, timestamps, deleted_at
product_protocol      product_id, protocol_id
product_values        product_id, attribute_id, option_id NULL, num NULL, bool NULL, text NULL
                      (برای چندانتخابی، چند ردیف)
product_custom_specs  id, product_id, label, value, sort       ← «مشخصات دلخواه»
product_relations     product_id, related_id, type ENUM('compatible','related')

media                 id, path, original_name, mime, bytes, width, height, sha256,
                      kind ENUM('image','datasheet','project','logo'), alt, uploaded_by
product_media         product_id, media_id, role ENUM('image','datasheet'), sort, is_primary
showcase_projects     id, title, location, sort, is_active   (عکس پروژه‌های انجام‌شده، از media)

needs                 id, label, hint, icon, sort        ← مرحله ۱ ویزارد (روشنایی، پرده…)
need_category         need_id, category_id

inquiries             id, name NULL, project NULL, item_count, message, page_url,
                      ip_hash, user_agent, status ENUM('new','seen','done'), created_at
                      (فقط «کلیک ارسال» ثبت می‌شود، نه تحویل در واتساپ)
inquiry_items         inquiry_id, product_id NULL, name_snapshot, code_snapshot, qty

updates               id, file_name, kind ENUM('fritz','xlsx'), uploaded_by,
                      status ENUM('preview','applied','failed','rolled_back'),
                      added, modified, diff JSON, backup_path, applied_at, rolled_back_at
activity_log          id, user_id, action, subject_type, subject_id, changes JSON, created_at
settings              key*, value JSON
                      (شماره و قالب پیام واتساپ، اطلاعات تماس، لوگوها، سئو،
                       اثر انگشت کلید .fritz؛ خود کلید با APP_KEY رمز می‌شود)
```

نکته‌ها:
- **فیلترهای هر دسته داده‌اند، نه کد.**
  - `category_attributes` هم فرم «مشخصات فنی» پنل را می‌سازد (A-ProductEdit)، هم شیت فیلتر عمومی را (M-Filter)، هم ردیف‌های مقایسه را.
  - با عوض شدن دسته، فیلدهای فرم هم عوض می‌شوند.
- **قیمت** فقط در جدول `products` است.
  - مدل عمومی Laravel با `$hidden` و یک Resource جدا آن را حذف می‌کند.
  - یک تست خودکار تضمین می‌کند قیمت و موجودی هیچ‌وقت در خروجی عمومی نیایند.
- **جست‌وجو:** ستون نرمال‌شده `search_text` (یکسان‌سازی ی/ي، ک/ك، ارقام فارسی/لاتین و حذف فاصله و خط تیره در کد مدل) با `LIKE`. برای ۴۰۰ محصول سریع و دقیق است.
  - FULLTEXT در MySQL برای فارسی و کدهای کوتاه خوب کار نمی‌کند.
- **نقش‌ها:**
  - Policyهای Laravel: `editor` به تنظیمات، کاربران و به‌روزرسانی دسترسی ندارد.
  - در منو این بخش‌ها قفل نمایش داده می‌شوند (طبق طراحی).
- **عملیات خطرناک** (حذف، غیرفعال‌سازی، اعمال به‌روزرسانی، بازگردانی، تغییر کلید) همیشه یک مودال تأیید دارند که اثرشان را با عدد توضیح می‌دهد.
  - نمونه: «این برند در ۸۴ محصول استفاده شده است».

---

## ۳) نقشه مرحله‌به‌مرحله

| # | مرحله | خروجی |
|---|---|---|
| ۰ ✅ | راه‌اندازی | Laravel، migrationها، seed با داده نمونه طراحی (۱۴ دسته، ۱۰ برند، ۳ پروتکل)، پورت `fz.css`، فونت، لوگو، آیکون‌ها به‌صورت SVG sprite، تم تیره. |
| ۱ ✅ | **کاتالوگ موبایل** (اول طبق درخواست) | خانه با جست‌وجو و فیلتر چسبان ← لیست دسته با شیت فیلتر اختصاصی، چیپ‌های فعال، حالت خالی و نمای ۱ یا ۲ ستونه ← صفحه محصول ← کشوی استعلام و پیام واتساپ و ثبت کلیک ← مقایسه تا ۳ محصول ← جست‌وجو ← ویزارد ← درباره و تماس ← حالت‌ها (اسکلتون، خطا، ۴۰۴). مرجع: `Main.dc.html` و `M-*`. |
| ۲ | تبلت و دسکتاپ | `T-List` و `D-*`: هدر چسبان، فیلتر کناری، اورلی‌ها. |
| ۳ | پنل: پایه | ورود، پوسته و منو، نمای کلی، کاربران و دعوت، نقش‌ها، گزارش فعالیت. |
| ۴ | پنل: محتوا | لیست محصولات، فرم افزودن/ویرایش با فیلدهای پویای دسته و قوانین «مانع انتشار»، دسته‌ها و فیلترها، برندها و پروتکل‌ها، رسانه، استعلام‌ها. |
| ۵ | پنل: به‌روزرسانی | آپلود `.fritz` یا Excel ← رمزگشایی ← پیش‌نمایش تفاوت‌ها ← پشتیبان ← اعمال ← بازگردانی (`A-FlowUpdate`). تنظیمات و کلید. |
| ۶ | کیفیت و انتشار | تست‌ها (قانون «بدون قیمت عمومی» و نقش‌ها)، WCAG AA، `impeccable detect` و `lint.mjs` سبز، سئو (sitemap و schema.org Product بدون قیمت)، استقرار روی DirectAdmin. |

---

## پاسخ‌ها و سؤال‌های باز
1. استک: تأیید شد.
2. PHP هاست: 8.1 است و **باید به 8.2 یا بالاتر برسد** (Laravel 12 روی 8.1 اجرا نمی‌شود). SSH نداریم و استقرار با FTP انجام می‌شود.
3. دامنه تست: `wooksmart.com`. پنل در `/admin`.
4. فرمت `.fritz`: **هنوز جواب داده نشده.** پیش از مرحله ۵ لازم است. اگر فایلی وجود ندارد، فرمت را خودمان تعریف می‌کنیم.
5. شماره واتساپ و اطلاعات تماس: هنوز داده نشده‌اند. فعلاً جای‌نگهدار هستند و از تنظیمات قابل تغییرند. بدون شماره، واتساپ صفحه انتخاب مخاطب را باز می‌کند.
