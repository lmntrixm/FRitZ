<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Activity;
use Illuminate\Http\Request;

/** Settings (A-Settings), «مدیر کل» only: WhatsApp and message, contact details, SEO. */
class SettingsController extends Controller
{
    public const LABELS = [
        'whatsapp_number' => 'شماره واتساپ', 'whatsapp_name' => 'نام نمایشی', 'message_template' => 'قالب پیام',
        'phone' => 'تلفن', 'email' => 'ایمیل', 'hours' => 'ساعات پاسخ‌گویی', 'address' => 'نشانی',
        'seo_title' => 'عنوان سایت', 'seo_description' => 'توضیح متا', 'allow_indexing' => 'اجازه ایندکس',
    ];

    public function show()
    {
        return view('admin.settings', [
            's' => collect(array_keys(self::LABELS))->mapWithKeys(fn ($k) => [$k => Setting::get($k)])->all(),
        ]);
    }

    public function update(Request $request)
    {
        $request->merge(['whatsapp_number' => self::normalizePhone($request->input('whatsapp_number'))]);
        $data = $request->validate([
            'whatsapp_number' => ['nullable', 'regex:/^[1-9]\d{9,14}$/'],
            'whatsapp_name' => ['nullable', 'string', 'max:60'],
            'message_template' => ['required', 'string', 'max:1000', 'regex:/\{اقلام\}/u'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:190'],
            'hours' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:300'],
            'seo_title' => ['required', 'string', 'max:120'],
            'seo_description' => ['nullable', 'string', 'max:300'],
        ], [
            'whatsapp_number.regex' => 'شماره را با کد کشور بنویسید، مثلاً ۹۸۹۱۲۰۰۰۰۰۰۰ یا ۰۹۱۲۰۰۰۰۰۰۰.',
            'message_template.regex' => 'قالب باید متغیر {اقلام} را داشته باشد؛ بدون آن فهرست محصولات در پیام نمی‌آید.',
        ], self::LABELS);
        $data['allow_indexing'] = $request->boolean('allow_indexing');

        $changed = [];
        foreach ($data as $k => $v) {
            $v = is_string($v) ? trim(str_replace("\r\n", "\n", $v)) : $v;
            if (Setting::get($k) !== $v) {
                Setting::put($k, $v);
                $changed[] = self::LABELS[$k];
            }
        }
        if ($changed) {
            Activity::log('settings.update', null, ['fields' => $changed]);
        }

        return redirect()->route('admin.settings')->with('ok', $changed ? 'تنظیمات ذخیره شد.' : 'تغییری برای ذخیره نبود.');
    }

    /** «+98 912 000 0000»، «۰۹۱۲…»، «0098…» → 98912… (wa.me wants digits with country code). */
    public static function normalizePhone(?string $v): ?string
    {
        $d = preg_replace('/\D+/', '', strtr((string) $v, ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9']));
        if ($d === '') {
            return null;
        }
        if (str_starts_with($d, '00')) {
            $d = substr($d, 2);
        } elseif (str_starts_with($d, '0')) {
            $d = '98'.substr($d, 1); // Iranian local format
        }

        return $d;
    }
}
