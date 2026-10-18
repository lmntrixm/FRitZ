# پایگاه داده (`database`)

ساختار جدول‌ها (migration) و داده اولیه (seeder). روی هاست، `/_deploy` این‌ها را اجرا می‌کند؛ روی کامپیوتر خودتان `php artisan migrate --seed`.

## فایل‌ها

| فایل | کار |
|---|---|
| `migrations/0001_01_01_00000*` | جدول‌های پایه Laravel: کاربران، کش، صف. |
| `migrations/2026_10_18_000100_create_catalog_tables.php` | کاتالوگ: `media`، `brands`، `protocols`، `categories`، `category_attributes`، `attribute_options`، `products`، `product_protocol`، `product_values`، `product_custom_specs`، `product_relations` («سازگار با» و «مرتبط»)، `product_media`، `needs`، `need_category`، `showcase_projects`. |
| `migrations/2026_10_18_000200_create_operations_tables.php` | کار روزانه: `inquiries`، `inquiry_items`، `updates`، `activity_log`، `user_invites`، `settings`. |
| `seeders/DatabaseSeeder.php` | ترتیب اجرا: اول داده مرجع، بعد محصولات نمونه اگر `FRITZ_DEMO_DATA=true`. |
| `seeders/CatalogSeeder.php` | داده مرجع: ۱۴ دسته و فیلدهایشان، ۱۰ برند، ۵ پروتکل (۳ فعال)، نیازهای راهنمای انتخاب، قالب پیام واتساپ و تنظیمات پیش‌فرض. |
| `seeders/DemoProductSeeder.php` | ۲۲ محصول نمونه طراحی. **فقط برای دامنه تست**؛ کدهای مدل جز `ZVIZ50A` و `R20A` نمونه‌اند. |
| `factories/UserFactory.php` | ساخت کاربر آزمایشی برای آزمون‌ها. |
| `.gitignore` | فایل `database.sqlite` محلی در گیت نمی‌رود. |

## انجام‌شده
- همه جدول‌های طرح، سازگار با MySQL/MariaDB هاست و SQLite محلی.

## باقی‌مانده
- پیش از راه‌اندازی اصلی: `FRITZ_DEMO_DATA=false`، پاک کردن محصولات نمونه و وارد کردن محصولات واقعی از اکسل.
- ویرایش داده مرجع (دسته‌ها، فیلترها، برندها) از پنل به‌جای `CatalogSeeder`.
