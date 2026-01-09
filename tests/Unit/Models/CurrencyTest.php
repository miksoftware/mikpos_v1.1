<?php

namespace Tests\Unit\Models;

use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_currency_has_fillable_attributes(): void
    {
        $fillable = ['name', 'code', 'symbol', 'is_active'];
        
        $currency = new Currency();
        
        $this->assertEquals($fillable, $currency->getFillable());
    }

    public function test_currency_casts_is_active_to_boolean(): void
    {
        $currency = Currency::factory()->create(['is_active' => 1]);
        
        $this->assertIsBool($currency->is_active);
        $this->assertTrue($currency->is_active);
    }

    public function test_currency_can_be_created_with_factory(): void
    {
        $currency = Currency::factory()->create();
        
        $this->assertInstanceOf(Currency::class, $currency);
        $this->assertDatabaseHas('currencies', [
            'id' => $currency->id,
            'code' => $currency->code,
        ]);
    }

    public function test_inactive_currency_factory_state(): void
    {
        $currency = Currency::factory()->inactive()->create();
        
        $this->assertFalse($currency->is_active);
    }
}