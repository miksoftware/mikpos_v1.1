<?php

namespace Tests\Unit\Models;

use App\Models\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_method_has_fillable_attributes(): void
    {
        $fillable = ['dian_code', 'name', 'is_active'];
        
        $paymentMethod = new PaymentMethod();
        
        $this->assertEquals($fillable, $paymentMethod->getFillable());
    }

    public function test_payment_method_casts_is_active_to_boolean(): void
    {
        $paymentMethod = PaymentMethod::factory()->create(['is_active' => 1]);
        
        $this->assertIsBool($paymentMethod->is_active);
        $this->assertTrue($paymentMethod->is_active);
    }

    public function test_payment_method_can_be_created_with_factory(): void
    {
        $paymentMethod = PaymentMethod::factory()->create();
        
        $this->assertInstanceOf(PaymentMethod::class, $paymentMethod);
        $this->assertDatabaseHas('payment_methods', [
            'id' => $paymentMethod->id,
            'dian_code' => $paymentMethod->dian_code,
        ]);
    }

    public function test_inactive_payment_method_factory_state(): void
    {
        $paymentMethod = PaymentMethod::factory()->inactive()->create();
        
        $this->assertFalse($paymentMethod->is_active);
    }
}