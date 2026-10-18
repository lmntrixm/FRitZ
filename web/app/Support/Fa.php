<?php

namespace App\Support;

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
}
