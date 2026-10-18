<?php

namespace Tests\Unit;

use App\Support\Fa;
use PHPUnit\Framework\TestCase;

class FaTest extends TestCase
{
    public function test_digits_and_numbers(): void
    {
        $this->assertSame('۴۲ محصول', Fa::digits('42 محصول'));
        $this->assertSame('۷', Fa::num(7.0));
        $this->assertSame('۷٫۵', Fa::num(7.5));
    }

    public function test_normalize_and_compact(): void
    {
        $this->assertSame('کلید ۳ پل', str_replace('3', '۳', Fa::normalize('كليد ۳  پل')));
        $this->assertSame('zviz50a', Fa::compact('ZVIZ-50A'));
        $this->assertSame('sas2416', Fa::compact('SA/S 24.16'));
    }
}
