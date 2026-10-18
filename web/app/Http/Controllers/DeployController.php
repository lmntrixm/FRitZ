<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

/**
 * Post-deploy hook for hosting without SSH (DirectAdmin).
 * Builds or updates the database and rebuilds caches. Two callers:
 * - the GitHub workflow after an FTP upload (header X-Deploy-Token, JSON back);
 * - a person after a manual zip upload: GET shows a one-field form, POST with the key.
 * Registered outside the web middleware group: no session or cache table exists before the first migration.
 * Disabled (404) unless DEPLOY_TOKEN is set in .env.
 */
class DeployController extends Controller
{
    public function __invoke(Request $request)
    {
        $token = (string) config('fritz.deploy_token');
        abort_if($token === '', 404);

        if ($request->isMethod('get')) {
            return response($this->page('<form method="post"><label for="t">کلید راه‌اندازی (<code>DEPLOY_TOKEN</code> در فایل <code>.env</code>)</label>'
                .'<input id="t" name="token" type="password" autocomplete="off" required dir="ltr"><button>ساخت و به‌روزرسانی دیتابیس</button></form>'));
        }

        $given = (string) ($request->header('X-Deploy-Token') ?? $request->input('token', ''));
        abort_unless(hash_equals($token, $given), 404);

        $out = [];
        $ok = true;
        foreach ([
            ['migrate', ['--force' => true]],
            ['optimize:clear', []],
            ['config:cache', []],
            ['route:cache', []],
            ['view:cache', []],
        ] as [$cmd, $args]) {
            try {
                $code = Artisan::call($cmd, $args);
                $out[] = ['command' => $cmd, 'exit' => $code, 'output' => trim(Artisan::output())];
            } catch (\Throwable $e) {
                $out[] = ['command' => $cmd, 'exit' => 1, 'output' => $e->getMessage()];
                $code = 1;
            }
            if ($code !== 0) {
                $ok = false;
                break;
            }
        }

        // first deploy only: reference data (and the sample products when FRITZ_DEMO_DATA=true).
        // Never re-run later: it would overwrite what the team edits in the admin.
        if ($ok && Schema::hasTable('categories') && Category::query()->doesntExist()) {
            $code = Artisan::call('db:seed', ['--force' => true]);
            $out[] = ['command' => 'db:seed', 'exit' => $code, 'output' => trim(Artisan::output())];
            $ok = $code === 0;
        }

        if ($request->header('X-Deploy-Token') !== null || $request->expectsJson()) {
            return response()->json($out, $ok ? 200 : 500);
        }

        $rows = collect($out)->map(fn ($r) => '<li class="'.($r['exit'] === 0 ? 'ok' : 'er').'"><b dir="ltr">'.e($r['command']).'</b>'
            .($r['exit'] === 0 ? ' انجام شد' : ' خطا داد').'<pre dir="ltr">'.e(mb_substr($r['output'], 0, 1500)).'</pre></li>')->join('');
        $msg = $ok
            ? '<p class="ok">دیتابیس آماده است. حالا <a href="'.e(url('/admin')).'">پنل مدیریت</a> را باز کنید و حساب مدیر کل را بسازید.</p>'
            : '<p class="er">یکی از مراحل خطا داد. متن خطا را برای پشتیبان بفرستید. معمولاً نام، کاربر یا رمز دیتابیس در <code>.env</code> اشتباه است.</p>';

        return response($this->page($msg.'<ul>'.$rows.'</ul>'), $ok ? 200 : 500);
    }

    /** Minimal standalone page: no layout, no database, no session. */
    private function page(string $body): string
    {
        return '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            .'<meta name="robots" content="noindex"><title>راه‌اندازی FRitZ</title><style>'
            .'body{font-family:Kalameh,Tahoma,sans-serif;max-width:560px;margin:48px auto;padding:0 20px;line-height:1.9;color:#141414;background:#fff}'
            .'h1{font-size:24px}label{display:block;font-weight:700;margin:16px 0 6px}input{width:100%;box-sizing:border-box;min-height:48px;padding:0 14px;border:0;border-bottom:2px solid #8A8A84;background:#F3F3F1;font-size:16px}'
            .'button{margin-top:16px;min-height:48px;padding:0 24px;border:0;border-radius:999px;background:#AFCB08;color:#141414;font-weight:700;font-size:15px;cursor:pointer}'
            .'ul{list-style:none;padding:0}li{padding:10px 0;border-bottom:1px solid #E7E7E3}pre{white-space:pre-wrap;font-size:12px;color:#4D4D49;margin:4px 0 0;text-align:left}'
            .'.ok{color:#2F6B3A}.er{color:#B42318}a{color:#566400;font-weight:700}code{direction:ltr;unicode-bidi:isolate}'
            .'@font-face{font-family:Kalameh;src:url('.e(asset('fonts/kalameh-regular.woff2')).') format("woff2")}'
            .'</style></head><body><h1>راه‌اندازی دیتابیس FRitZ</h1>'.$body.'</body></html>';
    }
}
