<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductUpdate;
use App\Models\Setting;
use App\Support\Activity;
use App\Support\Fa;
use App\Support\FritzPackage;
use App\Support\ProductSheet;
use App\Support\Xlsx;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/** Product updates from an Excel sheet or an encrypted .fritz package (A-Update, A-FlowUpdate). «مدیر کل» only. */
class UpdateController extends Controller
{
    private const MAX_KB = 20480; // the host's upload limit (.user.ini) is 24 MB

    public function index(Request $request)
    {
        return view('admin.update.index', [
            'tab' => in_array($request->query('tab'), ['fritz', 'xlsx', 'history'], true) ? $request->query('tab') : 'fritz',
            'hasKey' => FritzPackage::password() !== null,
            'keyMeta' => Setting::get('fritz_key_meta'),
            'pending' => ProductUpdate::where('status', 'preview')->latest('id')->first(),
            'history' => ProductUpdate::query()->with('uploader')->where('status', '!=', 'preview')->latest('id')->limit(50)->get(),
        ]);
    }

    public function upload(Request $request)
    {
        $kind = $request->input('kind') === 'fritz' ? 'fritz' : 'xlsx';
        $request->validate(['file' => ['required', 'file', 'max:'.self::MAX_KB]],
            ['file.max' => 'فایل بزرگ‌تر از حد مجاز است. حداکثر حجم ۲۰ مگابایت است؛ آن را در چند فایل تهیه کنید.', 'file.required' => 'فایلی انتخاب نشد.'],
            ['file' => 'فایل']);
        $file = $request->file('file');
        $back = fn (string $m) => redirect()->route('admin.update.index', ['tab' => $kind])->withErrors(['file' => $m]);

        $ext = strtolower($file->getClientOriginalExtension());
        if ($kind === 'xlsx' && $ext !== 'xlsx') {
            return $back('فقط فایل .xlsx پذیرفته می‌شود. فایل .xls یا .csv را در اکسل با «Save As → Excel Workbook» دوباره ذخیره کنید.');
        }

        $bytes = (string) file_get_contents($file->getRealPath());
        if ($kind === 'fritz') {
            $password = FritzPackage::password();
            if ($password === null) {
                return $back('هنوز رمز بسته در «تنظیمات» تعیین نشده است.');
            }
            try {
                $bytes = FritzPackage::decrypt($bytes, $password);
            } catch (RuntimeException $e) {
                return $back(match ($e->getMessage()) {
                    FritzPackage::WRONG_KEY => 'این فایل با رمز دیگری ساخته شده است. رمز بسته در «تنظیمات» با رمز سازنده فایل یکی نیست. هیچ تغییری اعمال نشد.',
                    FritzPackage::TAMPERED => 'فایل مخدوش یا دستکاری‌شده است. امضای فایل با محتوا هم‌خوانی ندارد و هیچ تغییری اعمال نشد. فایل را دوباره از منبع اصلی دریافت کنید.',
                    default => 'این فایل بستهٔ .fritz نیست. اگر فایل اکسل است، از تب «ورود از اکسل» استفاده کنید.',
                });
            }
        }

        $tmp = tempnam(sys_get_temp_dir(), 'fz').'.xlsx';
        file_put_contents($tmp, $bytes);
        try {
            $analysis = (new ProductSheet)->analyse(Xlsx::read($tmp));
        } catch (RuntimeException $e) {
            return $back($e->getMessage());
        } finally {
            @unlink($tmp);
        }

        // drop earlier unapplied previews: only one analysis is pending at a time
        ProductUpdate::where('status', 'preview')->get()->each(function ($u) {
            Storage::disk('local')->delete('updates/'.$u->id.'-'.$u->file_name);
            $u->delete();
        });
        $u = ProductUpdate::create(['file_name' => $file->getClientOriginalName(), 'kind' => $kind, 'uploaded_by' => $request->user()->id,
            'status' => 'preview', 'diff' => $analysis, 'added' => $analysis['counts']['new'], 'modified' => $analysis['counts']['mod']]);
        Storage::disk('local')->put('updates/'.$u->id.'-'.$u->file_name, (string) file_get_contents($file->getRealPath()));

        return redirect()->route('admin.update.show', $u);
    }

    public function show(ProductUpdate $update)
    {
        return view('admin.update.show', ['u' => $update->load('uploader'), 'canRollback' => $update->canRollback()]);
    }

    public function apply(Request $request, ProductUpdate $update)
    {
        abort_unless($update->status === 'preview', 409, 'این به‌روزرسانی قبلاً اعمال یا لغو شده است.');
        $delete = $request->boolean('delete_absent');
        if ($delete && ! $request->boolean('delete_ack')) {
            return back()->withErrors(['delete_ack' => 'برای حذف محصولات غایب، «می‌دانم … محصول حذف می‌شود» را تیک بزنید.'])->withInput();
        }
        if (($update->diff['counts']['new'] ?? 0) + ($update->diff['counts']['mod'] ?? 0) === 0 && ! $delete) {
            return back()->withErrors(['apply' => 'این فایل تغییری در کاتالوگ ایجاد نمی‌کند.']);
        }

        try {
            $report = (new ProductSheet)->apply($update, $delete, $request->boolean('ignore_price'));
            $update->fill(['status' => 'applied', 'applied_at' => now(), 'added' => $report['added'], 'modified' => $report['modified'],
                'diff' => array_merge($update->diff, ['report' => $report + ['ignore_price' => $request->boolean('ignore_price')]])])->save();
            Activity::log('update.apply', $update, ['file' => $update->file_name, 'added' => $report['added'], 'modified' => $report['modified'], 'deleted' => $report['deleted']]);

            return redirect()->route('admin.update.show', $update)->with('ok', 'به‌روزرسانی اعمال شد.');
        } catch (\Throwable $e) {
            report($e);
            $update->fill(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 1000)])->save();

            return redirect()->route('admin.update.show', $update);
        }
    }

    public function rollback(ProductUpdate $update)
    {
        abort_unless($update->canRollback(), 409, 'فقط آخرین به‌روزرسانی اعمال‌شده قابل بازگردانی است.');
        (new ProductSheet)->rollback($update);
        $update->fill(['status' => 'rolled_back', 'rolled_back_at' => now()])->save();
        Activity::log('update.rollback', $update, ['file' => $update->file_name]);

        return redirect()->route('admin.update.show', $update)->with('ok', 'کاتالوگ به وضعیت پیش از این به‌روزرسانی برگشت.');
    }

    public function discard(ProductUpdate $update)
    {
        abort_unless($update->status === 'preview', 409);
        Storage::disk('local')->delete('updates/'.$update->id.'-'.$update->file_name);
        $update->delete();

        return redirect()->route('admin.update.index', ['tab' => $update->kind])->with('ok', 'فایل کنار گذاشته شد؛ تغییری اعمال نشد.');
    }

    public function file(ProductUpdate $update)
    {
        $path = 'updates/'.$update->id.'-'.$update->file_name;
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, $update->file_name);
    }

    /** The whole catalog as the sheet people edit and upload back; ?empty=1 gives the bare template. */
    public function export(Request $request)
    {
        $tmp = tempnam(sys_get_temp_dir(), 'fz').'.xlsx';
        $empty = $request->boolean('empty');
        (new ProductSheet)->export($tmp, ! $empty);
        $name = $empty ? 'fritz-template.xlsx' : 'fritz-products-'.now()->format('Y-m-d').'.xlsx';

        return response()->download($tmp, $name)->deleteFileAfterSend();
    }

    /** Offline tool for whoever prepares the files: .xlsx + password → .fritz, in the browser. */
    public function packer()
    {
        $html = view('admin.update.packer', ['fp' => Setting::get('fritz_key_meta')['fp'] ?? null, 'site' => parse_url(url('/'), PHP_URL_HOST)])->render();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="fritz-packer.html"',
        ]);
    }

    public function setKey(Request $request)
    {
        $current = FritzPackage::password();
        $data = $request->validateWithBag('key', [
            'current' => $current !== null ? ['required', 'string'] : ['nullable'],
            'password' => ['required', 'string', 'min:12', 'max:200', 'confirmed'],
        ], [], ['current' => 'رمز فعلی', 'password' => 'رمز جدید']);
        if ($current !== null && ! hash_equals($current, (string) $data['current'])) {
            return redirect()->to(route('admin.settings').'#key')->withErrors(['current' => 'رمز فعلی درست نیست.'], 'key');
        }
        FritzPackage::setPassword($data['password']);
        Activity::log('settings.key', null, ['fp' => FritzPackage::fingerprint($data['password'])]);

        return redirect()->to(route('admin.settings').'#key')->with('ok', 'رمز بسته تغییر کرد. اثر انگشت جدید: '.FritzPackage::fingerprint($data['password']).'. رمز را از راه امن به سازنده فایل‌ها بدهید.');
    }

    public static function size(int $bytes): string
    {
        return $bytes >= 1048576 ? Fa::num(round($bytes / 1048576, 1)).' مگابایت' : Fa::num(max(1, round($bytes / 1024))).' کیلوبایت';
    }
}
