<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

/** Sections designed but not built yet: an honest page instead of a dead link. */
class SoonController extends Controller
{
    public const SECTIONS = [
        'categories' => ['دسته‌ها و فیلترها', 'folder'],
        'brands' => ['برندها و پروتکل‌ها', 'tag'], 'media' => ['رسانه', 'image'],
        'inquiries' => ['استعلام‌ها', 'chat'], 'update' => ['به‌روزرسانی', 'upload'],
        'users' => ['کاربران', 'users'], 'activity' => ['گزارش فعالیت', 'activity'],
    ];

    public function __invoke(string $section)
    {
        abort_unless(isset(self::SECTIONS[$section]), 404);
        if (in_array($section, ['update', 'users'], true)) {
            abort_unless(request()->user()->isAdmin(), 403, 'این بخش فقط برای مدیر کل است.');
        }

        return view('admin.soon', ['key' => $section, 'title' => self::SECTIONS[$section][0], 'icon' => self::SECTIONS[$section][1]]);
    }
}
