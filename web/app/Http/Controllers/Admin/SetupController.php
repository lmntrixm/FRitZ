<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

/**
 * Creates the first «مدیر کل» on a fresh install. The host has no SSH, so this replaces
 * an artisan command. Open only while there are no users, and only with the setup key
 * (DEPLOY_TOKEN from .env), so nobody else can claim a freshly deployed site.
 */
class SetupController extends Controller
{
    public function show()
    {
        abort_if(User::query()->exists(), 404);

        return view('admin.setup', ['needsKey' => $this->key() !== '']);
    }

    public function store(Request $request)
    {
        abort_if(User::query()->exists(), 404);
        abort_if($this->key() === '' && app()->isProduction(), 403, 'DEPLOY_TOKEN در فایل .env تنظیم نشده است.');

        $data = $request->validate([
            'key' => $this->key() !== '' ? ['required', 'string'] : ['nullable'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'confirmed', Password::min(10)],
        ], [], ['key' => 'کلید راه‌اندازی', 'name' => 'نام', 'email' => 'ایمیل', 'password' => 'رمز عبور']);

        if ($this->key() !== '' && ! hash_equals($this->key(), (string) $data['key'])) {
            return back()->withInput($request->except('password', 'password_confirmation', 'key'))
                ->withErrors(['key' => 'کلید راه‌اندازی درست نیست.']);
        }

        $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password'], 'role' => 'admin']);
        Auth::login($user);
        $request->session()->regenerate();
        Activity::log('setup', $user);

        return redirect()->route('admin.overview')->with('ok', 'حساب مدیر کل ساخته شد. خوش آمدید.');
    }

    private function key(): string
    {
        return (string) config('fritz.deploy_token');
    }
}
