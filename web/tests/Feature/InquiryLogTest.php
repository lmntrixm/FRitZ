<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InquiryLogTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function send(array $payload)
    {
        return $this->post('/inquiry', ['payload' => json_encode($payload)]);
    }

    public function test_click_is_logged_with_snapshots(): void
    {
        $p = Product::where('model_code', 'ZVIZ50A')->first();

        $this->send(['items' => [['id' => $p->id, 'qty' => 3]], 'name' => 'علی', 'project' => 'ویلا', 'message' => 'سلام', 'page' => '/'])
            ->assertNoContent();

        $inq = Inquiry::with('items')->sole();
        $this->assertSame('علی', $inq->name);
        $this->assertSame(1, $inq->item_count);
        $this->assertSame('ZVIZ50A', $inq->items[0]->code_snapshot);
        $this->assertSame(3, $inq->items[0]->qty);
        $this->assertNotNull($inq->ip_hash);
    }

    public function test_unknown_or_draft_products_are_rejected(): void
    {
        $draft = Product::where('model_code', 'R20A')->first();
        $draft->update(['status' => 'draft']);

        $this->send(['items' => [['id' => 99999, 'qty' => 1], ['id' => $draft->id, 'qty' => 1]]])->assertStatus(422);
        $this->send(['items' => []])->assertStatus(422);
        $this->post('/inquiry', ['payload' => 'not json'])->assertStatus(422);
        $this->assertSame(0, Inquiry::count());
    }

    public function test_quantities_and_text_are_clamped(): void
    {
        $p = Product::first();
        $this->send(['items' => [['id' => $p->id, 'qty' => 1000000]], 'name' => str_repeat('ا', 500), 'message' => 'x'])->assertNoContent();

        $inq = Inquiry::with('items')->sole();
        $this->assertSame(9999, $inq->items[0]->qty);
        $this->assertSame(120, mb_strlen($inq->name));
    }
}
