<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Logs the click on «ارسال استعلام در واتساپ». Delivery happens in WhatsApp and is not tracked. */
class InquiryController extends Controller
{
    public function log(Request $request)
    {
        $data = json_decode((string) $request->input('payload'), true);
        if (! is_array($data) || ! is_array($data['items'] ?? null) || ! $data['items']) {
            return response()->noContent(422);
        }

        $lines = collect($data['items'])->take(100)
            ->map(fn ($i) => ['id' => (int) ($i['id'] ?? 0), 'qty' => max(1, min(9999, (int) ($i['qty'] ?? 1)))])
            ->filter(fn ($i) => $i['id'] > 0)->unique('id');
        $products = Product::published()->whereKey($lines->pluck('id'))->get()->keyBy('id');
        $lines = $lines->filter(fn ($i) => $products->has($i['id']));
        if ($lines->isEmpty()) {
            return response()->noContent(422);
        }

        $clip = fn ($v, $n) => ($s = trim(mb_substr((string) $v, 0, $n))) === '' ? null : $s;

        DB::transaction(function () use ($request, $data, $lines, $products, $clip) {
            $inq = Inquiry::create([
                'name' => $clip($data['name'] ?? '', 120),
                'project' => $clip($data['project'] ?? '', 200),
                'item_count' => $lines->count(),
                'message' => (string) $clip($data['message'] ?? '', 4000),
                'page_url' => $clip($data['page'] ?? '', 255),
                'ip_hash' => hash('sha256', $request->ip().config('app.key')),
                'user_agent' => $clip($request->userAgent(), 255),
            ]);
            foreach ($lines as $l) {
                $p = $products[$l['id']];
                $inq->items()->create(['product_id' => $p->id, 'name_snapshot' => $p->name,
                    'code_snapshot' => $p->model_code, 'qty' => $l['qty']]);
            }
        });

        return response()->noContent();
    }
}
