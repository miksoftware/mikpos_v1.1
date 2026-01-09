<?php

namespace Tests\Unit\Models;

use App\Models\TaxDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_tax_document_has_fillable_attributes(): void
    {
        $fillable = ['dian_code', 'description', 'abbreviation', 'is_active'];
        
        $taxDocument = new TaxDocument();
        
        $this->assertEquals($fillable, $taxDocument->getFillable());
    }

    public function test_tax_document_casts_is_active_to_boolean(): void
    {
        $taxDocument = TaxDocument::factory()->create(['is_active' => 1]);
        
        $this->assertIsBool($taxDocument->is_active);
        $this->assertTrue($taxDocument->is_active);
    }

    public function test_tax_document_can_be_created_with_factory(): void
    {
        $taxDocument = TaxDocument::factory()->create();
        
        $this->assertInstanceOf(TaxDocument::class, $taxDocument);
        $this->assertDatabaseHas('tax_documents', [
            'id' => $taxDocument->id,
            'dian_code' => $taxDocument->dian_code,
        ]);
    }

    public function test_inactive_tax_document_factory_state(): void
    {
        $taxDocument = TaxDocument::factory()->inactive()->create();
        
        $this->assertFalse($taxDocument->is_active);
    }
}