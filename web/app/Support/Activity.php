<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** Writes the admin activity log (A-Activity). */
class Activity
{
    public static function log(string $action, ?Model $subject = null, array $changes = []): void
    {
        DB::table('activity_log')->insert([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'changes' => $changes ? json_encode($changes, JSON_UNESCAPED_UNICODE) : null,
            'created_at' => now(),
        ]);
    }
}
