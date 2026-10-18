<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * Post-deploy hook for hosting without SSH (DirectAdmin over FTP).
 * The GitHub workflow calls it after uploading; it runs migrations and rebuilds caches.
 * Disabled unless DEPLOY_TOKEN is set in .env.
 */
class DeployController extends Controller
{
    public function __invoke(Request $request)
    {
        $token = (string) config('fritz.deploy_token');
        abort_if($token === '' || ! hash_equals($token, (string) $request->header('X-Deploy-Token')), 404);

        $out = [];
        foreach ([
            ['migrate', ['--force' => true]],
            ['optimize:clear', []],
            ['config:cache', []],
            ['route:cache', []],
            ['view:cache', []],
        ] as [$cmd, $args]) {
            $code = Artisan::call($cmd, $args);
            $out[] = ['command' => $cmd, 'exit' => $code, 'output' => trim(Artisan::output())];
            if ($code !== 0) {
                return response()->json($out, 500);
            }
        }

        // first deploy only: reference data (and the sample products when FRITZ_DEMO_DATA=true).
        // Never re-run later: it would overwrite what the team edits in the admin.
        if (Category::query()->doesntExist()) {
            $code = Artisan::call('db:seed', ['--force' => true]);
            $out[] = ['command' => 'db:seed', 'exit' => $code, 'output' => trim(Artisan::output())];
        }

        return response()->json($out);
    }
}
