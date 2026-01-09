<?php

namespace Tests\Unit\Models;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Municipality;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_has_fillable_attributes(): void
    {
        $branch = new Branch();
        
        $this->assertEquals([
            'code',
            'name',
            'tax_id',
            'department_id',
            'municipality_id',
            'province',
            'city',
            'address',
            'phone',
            'email',
            'ticket_prefix',
            'invoice_prefix',
            'receipt_prefix',
            'credit_note_prefix',
            'activity_number',
            'authorization_date',
            'receipt_header',
            'show_in_pos',
            'is_active',
        ], $branch->getFillable());
    }

    public function test_branch_casts_booleans_correctly(): void
    {
        $branch = Branch::factory()->create([
            'show_in_pos' => 1,
            'is_active' => 1,
        ]);
        
        $this->assertIsBool($branch->show_in_pos);
        $this->assertIsBool($branch->is_active);
        $this->assertTrue($branch->show_in_pos);
        $this->assertTrue($branch->is_active);
    }

    public function test_branch_belongs_to_department(): void
    {
        $department = Department::factory()->create();
        $branch = Branch::factory()->create(['department_id' => $department->id]);
        
        $this->assertInstanceOf(Department::class, $branch->department);
        $this->assertEquals($department->id, $branch->department->id);
    }

    public function test_branch_belongs_to_municipality(): void
    {
        $municipality = Municipality::factory()->create();
        $branch = Branch::factory()->create(['municipality_id' => $municipality->id]);
        
        $this->assertInstanceOf(Municipality::class, $branch->municipality);
        $this->assertEquals($municipality->id, $branch->municipality->id);
    }

    public function test_branch_has_many_users(): void
    {
        $branch = Branch::factory()->create();
        $user1 = User::factory()->create(['branch_id' => $branch->id]);
        $user2 = User::factory()->create(['branch_id' => $branch->id]);
        
        $this->assertCount(2, $branch->users);
        $this->assertTrue($branch->users->contains($user1));
        $this->assertTrue($branch->users->contains($user2));
    }

    public function test_branch_has_many_active_users(): void
    {
        $branch = Branch::factory()->create();
        $activeUser = User::factory()->create([
            'branch_id' => $branch->id,
            'is_active' => true
        ]);
        $inactiveUser = User::factory()->create([
            'branch_id' => $branch->id,
            'is_active' => false
        ]);
        
        $activeUsers = $branch->activeUsers;
        
        $this->assertCount(1, $activeUsers);
        $this->assertTrue($activeUsers->contains($activeUser));
        $this->assertFalse($activeUsers->contains($inactiveUser));
    }

    public function test_branch_can_be_created_with_factory(): void
    {
        $branch = Branch::factory()->create([
            'code' => 'SUC001',
            'name' => 'Test Branch',
        ]);
        
        $this->assertDatabaseHas('branches', [
            'code' => 'SUC001',
            'name' => 'Test Branch',
            'is_active' => true,
        ]);
    }

    public function test_inactive_branch_factory_state(): void
    {
        $branch = Branch::factory()->inactive()->create();
        
        $this->assertFalse($branch->is_active);
    }
}