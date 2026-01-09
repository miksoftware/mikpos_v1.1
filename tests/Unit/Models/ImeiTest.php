<?php

namespace Tests\Unit\Models;

use App\Models\Imei;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImeiTest extends TestCase
{
    use RefreshDatabase;

    public function test_imei_can_be_created(): void
    {
        $imei = Imei::factory()->create([
            'imei' => '123456789012345',
            'imei2' => '987654321098765',
            'status' => 'available',
            'notes' => 'Test IMEI device',
        ]);

        $this->assertDatabaseHas('imeis', [
            'imei' => '123456789012345',
            'imei2' => '987654321098765',
            'status' => 'available',
            'notes' => 'Test IMEI device',
        ]);
    }

    public function test_imei_can_be_created_without_imei2(): void
    {
        $imei = Imei::factory()->create([
            'imei' => '123456789012345',
            'imei2' => null,
            'status' => 'available',
        ]);

        $this->assertDatabaseHas('imeis', [
            'imei' => '123456789012345',
            'imei2' => null,
            'status' => 'available',
        ]);
        
        $this->assertNull($imei->imei2);
    }

    public function test_imei_status_values(): void
    {
        $availableImei = Imei::factory()->available()->create();
        $this->assertEquals('available', $availableImei->status);

        $soldImei = Imei::factory()->sold()->create();
        $this->assertEquals('sold', $soldImei->status);

        $reservedImei = Imei::factory()->reserved()->create();
        $this->assertEquals('reserved', $reservedImei->status);
    }

    public function test_imei_fillable_attributes(): void
    {
        $imei = new Imei();
        $expected = ['imei', 'imei2', 'status', 'notes'];
        
        $this->assertEquals($expected, $imei->getFillable());
    }

    public function test_imei_number_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Imei::create([
            'status' => 'available',
            'notes' => 'Test notes',
        ]);
    }

    public function test_imei_can_have_dual_imei(): void
    {
        $imei = Imei::factory()->withImei2()->create([
            'imei' => '111111111111111',
        ]);

        $this->assertNotNull($imei->imei2);
        $this->assertEquals(15, strlen($imei->imei2));
    }

    public function test_imei_can_be_created_with_minimal_data(): void
    {
        $imei = Imei::create([
            'imei' => '123456789012345',
            'status' => 'available',
        ]);

        $this->assertDatabaseHas('imeis', [
            'imei' => '123456789012345',
            'status' => 'available',
        ]);
        
        $this->assertNull($imei->imei2);
        $this->assertNull($imei->notes);
    }
}