<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Local or SSH-capable hosts: create or reset a panel user. On DirectAdmin without SSH use /admin/setup.
Artisan::command('fritz:user {email} {--name=} {--role=admin} {--password=}', function (string $email) {
    $password = $this->option('password') ?: $this->secret('رمز عبور (دست‌کم ۱۰ نویسه)');
    if (mb_strlen((string) $password) < 10) {
        $this->error('رمز باید دست‌کم ۱۰ نویسه باشد.');

        return 1;
    }
    $user = User::updateOrCreate(['email' => $email], [
        'name' => $this->option('name') ?: $email,
        'password' => $password,
        'role' => in_array($this->option('role'), ['admin', 'editor'], true) ? $this->option('role') : 'admin',
        'status' => 'active',
    ]);
    $this->info("کاربر {$user->email} با نقش {$user->role} آماده است.");
})->purpose('Create or reset a FRitZ panel user');

// Purge products deleted more than 30 days ago (DirectAdmin cron runs schedule:run every minute).
Schedule::command('model:prune', ['--model' => [Product::class]])->daily();
