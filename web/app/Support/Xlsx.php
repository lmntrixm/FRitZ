<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Minimal .xlsx reader and writer (first sheet only), on ZipArchive + SimpleXML.
 * Enough for the product sheet: text and numbers, a header row, right-to-left view.
 * No dependency to ship to the host.
 */
class Xlsx
{
    private const MAX_XML_BYTES = 40 * 1024 * 1024; // guard against zip bombs

    private const MAX_ROWS = 10000;

    /** @return array<int, array<int, string>> rows of cell strings, 0-based columns, header included */
    public static function read(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('فایل اکسل باز نشد. فایل باید با پسوند .xlsx ذخیره شده باشد.');
        }
        try {
            $shared = [];
            if (($xml = self::entry($zip, 'xl/sharedStrings.xml')) !== null) {
                foreach (self::xml($xml)->si as $si) {
                    $shared[] = self::text($si);
                }
            }

            $sheet = self::entry($zip, self::firstSheetPath($zip));
            if ($sheet === null) {
                throw new RuntimeException('برگه‌ای در فایل اکسل پیدا نشد.');
            }

            $rows = [];
            foreach (self::xml($sheet)->sheetData->row as $row) {
                if (count($rows) >= self::MAX_ROWS) {
                    throw new RuntimeException('فایل بیش از '.self::MAX_ROWS.' ردیف دارد؛ آن را در چند فایل تهیه کنید.');
                }
                $cells = [];
                $next = 0;
                foreach ($row->c as $c) {
                    $col = isset($c['r']) ? self::colIndex((string) $c['r']) : $next;
                    $next = $col + 1;
                    $type = (string) ($c['t'] ?? 'n');
                    $cells[$col] = match ($type) {
                        's' => $shared[(int) $c->v] ?? '',
                        'inlineStr' => self::text($c->is),
                        'b' => ((string) $c->v) === '1' ? 'بله' : 'خیر',
                        'e' => '',
                        default => self::number((string) $c->v),
                    };
                }
                $r = isset($row['r']) ? (int) $row['r'] - 1 : count($rows);
                if ($cells) {
                    $max = max(array_keys($cells));
                    $rows[$r] = array_map(fn ($i) => trim((string) ($cells[$i] ?? '')), range(0, $max));
                }
            }
            ksort($rows);

            return $rows;
        } finally {
            $zip->close();
        }
    }

    /**
     * @param  array<int, string>  $header
     * @param  array<int, array<int, string|int|float|null>>  $rows
     */
    public static function write(string $path, array $header, array $rows, string $sheetName = 'محصولات'): void
    {
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('ساخت فایل اکسل ممکن نشد.');
        }
        $esc = fn ($s) => htmlspecialchars((string) $s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $cell = function ($v, int $col, int $row, bool $bold = false) use ($esc) {
            $ref = self::colName($col).$row;
            $style = $bold ? ' s="1"' : '';
            if ($v === null || $v === '') {
                return '';
            }
            if (is_int($v) || is_float($v)) {
                return '<c r="'.$ref.'"'.$style.'><v>'.$v.'</v></c>';
            }

            return '<c r="'.$ref.'" t="inlineStr"'.$style.'><is><t xml:space="preserve">'.$esc($v).'</t></is></c>';
        };

        $data = '';
        foreach (array_merge([$header], $rows) as $i => $r) {
            $data .= '<row r="'.($i + 1).'">';
            foreach (array_values($r) as $j => $v) {
                $data .= $cell($v, $j, $i + 1, $i === 0);
            }
            $data .= '</row>';
        }
        $cols = '';
        foreach ($header as $j => $h) {
            $w = in_array($h, ['نام', 'توضیحات'], true) ? 48 : (mb_strlen((string) $h) > 14 ? 24 : 18);
            $cols .= '<col min="'.($j + 1).'" max="'.($j + 1).'" width="'.$w.'" customWidth="1"/>';
        }

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="'.$esc($sheetName).'" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Tahoma"/></font><font><b/><sz val="11"/><name val="Tahoma"/></font></fonts><fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>');
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView rightToLeft="1" workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><cols>'.$cols.'</cols><sheetData>'.$data.'</sheetData></worksheet>');
        $zip->close();
    }

    private static function entry(ZipArchive $zip, string $name): ?string
    {
        $stat = $zip->statName($name);
        if ($stat === false) {
            return null;
        }
        if ($stat['size'] > self::MAX_XML_BYTES) {
            throw new RuntimeException('فایل اکسل بیش از حد بزرگ است.');
        }

        return $zip->getFromName($name) ?: null;
    }

    private static function firstSheetPath(ZipArchive $zip): string
    {
        $wb = self::entry($zip, 'xl/workbook.xml');
        $rels = self::entry($zip, 'xl/_rels/workbook.xml.rels');
        if ($wb && $rels) {
            $w = self::xml($wb);
            $sheet = $w->sheets->sheet[0] ?? null;
            $rid = $sheet ? (string) ($sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'] ?? '') : '';
            foreach (self::xml($rels)->Relationship as $rel) {
                if ((string) $rel['Id'] === $rid) {
                    $target = ltrim((string) $rel['Target'], '/');

                    return str_starts_with($target, 'xl/') ? $target : 'xl/'.$target;
                }
            }
        }

        return 'xl/worksheets/sheet1.xml';
    }

    private static function xml(string $s): \SimpleXMLElement
    {
        $x = simplexml_load_string($s, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_COMPACT);
        if ($x === false) {
            throw new RuntimeException('محتوای فایل اکسل خراب است.');
        }

        return $x;
    }

    /** Text of a shared/inline string, rich text runs included. */
    private static function text(?\SimpleXMLElement $node): string
    {
        if (! $node) {
            return '';
        }
        if (isset($node->t)) {
            return (string) $node->t;
        }
        $out = '';
        foreach ($node->r as $run) {
            $out .= (string) $run->t;
        }

        return $out;
    }

    /** 18400000 stays 18400000, 7.0 becomes 7 (Excel stores numbers as floats). */
    private static function number(string $v): string
    {
        if ($v === '' || ! is_numeric($v)) {
            return $v;
        }
        $f = (float) $v;

        return floor($f) == $f && abs($f) < 1e15 ? number_format($f, 0, '.', '') : rtrim(rtrim(sprintf('%.6F', $f), '0'), '.');
    }

    private static function colIndex(string $ref): int
    {
        $letters = preg_replace('/\d+/', '', strtoupper($ref));
        $n = 0;
        foreach (str_split($letters) as $ch) {
            $n = $n * 26 + (ord($ch) - 64);
        }

        return $n - 1;
    }

    private static function colName(int $i): string
    {
        $s = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $s = chr(65 + ($i - 1) % 26).$s;
        }

        return $s;
    }
}
