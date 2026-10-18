# استقرار خودکار (`.github/workflows`)

| فایل | کار |
|---|---|
| `deploy.yml` | با هر push همه آزمون‌ها را روی PHP 8.2 اجرا می‌کند. روی شاخه `main`، اگر آزمون‌ها سبز باشند: کتابخانه‌ها را بدون ابزار توسعه نصب می‌کند، برنامه را به `fritz-app` و ریشه وب را به `public_html` جدا می‌کند، با FTP روی هاست می‌فرستد و در پایان `/_deploy` را با کلید صدا می‌زند تا جدول‌ها و کش‌ها به‌روز شوند. |

## برای فعال شدن لازم است (در تنظیمات مخزن گیت‌هاب)
- Secrets: `FTP_SERVER`، `FTP_USERNAME`، `FTP_PASSWORD`، `DEPLOY_TOKEN`.
- Variables: `FTP_APP_DIR` (مثلاً `domains/wooksmart.com/fritz-app/`)، `FTP_WEB_DIR` (`domains/wooksmart.com/public_html/`)، `SITE_URL`.

## وضعیت
- فایل آماده است ولی هنوز **فعال نشده**: Secrets روی گیت‌هاب تعریف نشده و کار روی شاخه `claude/great-lovelace-00c16o` است، نه `main`. تا آن موقع به‌روزرسانی هاست با فایل zip و `/_deploy` انجام می‌شود. جزئیات: `docs/DEPLOY.md`.
