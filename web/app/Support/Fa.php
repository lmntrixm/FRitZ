<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/** Persian text helpers: digits for display, and one normal form for search. */
class Fa
{
    private const FA = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

    private const AR = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

    /** Latin digits → Persian digits, for counts and Persian copy (never for model codes). */
    public static function digits(string|int|float|null $s): string
    {
        return str_replace(range(0, 9), self::FA, (string) $s);
    }

    /** A number without trailing zeros, Persian digits and «٫» as the decimal mark. */
    public static function num(float|int|null $n): string
    {
        if ($n === null) {
            return '';
        }
        $s = rtrim(rtrim(number_format((float) $n, 3, '.', ''), '0'), '.');

        return self::digits(str_replace('.', '٫', $s));
    }

    /** Normal form used on both sides of a search: Arabic letters, digits and case folded. */
    public static function normalize(?string $s): string
    {
        $s = (string) $s;
        $s = str_replace(self::FA, range(0, 9), $s);
        $s = str_replace(self::AR, range(0, 9), $s);
        $s = str_replace(['ي', 'ى', 'ك', 'ة', 'ۀ', 'أ', 'إ', "\u{200C}"], ['ی', 'ی', 'ک', 'ه', 'ه', 'ا', 'ا', ' '], $s);
        $s = mb_strtolower($s);

        return trim(preg_replace('/\s+/u', ' ', $s));
    }

    /** Model code without separators, so «zvi z50», «ZVIZ-50A» and «zviz50a» meet. */
    public static function compact(?string $s): string
    {
        return preg_replace('/[\s\-_\/.]+/u', '', self::normalize($s));
    }

    /** Solar Hijri date, e.g. «شنبه ۲۵ مهر ۱۴۰۵». Falls back to Gregorian when intl is missing. */
    public static function date(\DateTimeInterface $d, string $pattern = 'EEEE d MMMM y'): string
    {
        if (! class_exists(\IntlDateFormatter::class)) {
            return self::digits($d->format('Y-m-d'));
        }
        $f = new \IntlDateFormatter('fa_IR@calendar=persian', \IntlDateFormatter::FULL, \IntlDateFormatter::NONE,
            config('app.display_timezone', 'Asia/Tehran'), \IntlDateFormatter::TRADITIONAL, $pattern);

        return self::digits($f->format($d));
    }

    /** «۲۰ دقیقه پیش»، «۲ ساعت پیش»، «دیروز، ۱۸:۱۰»، «۱۸ مهر، ۱۰:۰۵». */
    public static function ago(\DateTimeInterface $d): string
    {
        $c = Carbon::instance($d)->setTimezone(config('app.display_timezone', 'Asia/Tehran'));
        $now = now(config('app.display_timezone', 'Asia/Tehran'));
        $min = (int) $c->diffInMinutes($now);

        return match (true) {
            $min < 1 => 'همین حالا',
            $min < 60 => self::digits($min).' دقیقه پیش',
            $c->isSameDay($now) => self::digits((int) $c->diffInHours($now)).' ساعت پیش',
            $c->isSameDay($now->copy()->subDay()) => 'دیروز، '.self::digits($c->format('H:i')),
            default => self::date($c, 'd MMMM').'، '.self::digits($c->format('H:i')),
        };
    }
}
