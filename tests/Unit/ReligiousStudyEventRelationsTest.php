<?php

namespace Tests\Unit;

use App\Models\Departemen;
use App\Models\Jabatan;
use App\Models\ReligiousStudyEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReligiousStudyEventRelationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_belongs_to_departemen_and_jabatan(): void
    {
        $dept = Departemen::create(['name' => 'IT', 'description' => '']);
        $jab = Jabatan::create(['name' => 'Engineer', 'description' => '']);

        $event = ReligiousStudyEvent::create([
            'title' => 'Kajian',
            'event_at' => now()->addDay(),
            'notify_at' => now(),
            'departemen_id' => $dept->id,
            'jabatan_id' => $jab->id,
        ]);

        $this->assertEquals('IT', $event->departemen?->name);
        $this->assertEquals('Engineer', $event->jabatan?->name);
    }

    public function test_filtering_by_departemen_and_jabatan_ids(): void
    {
        $deptA = Departemen::create(['name' => 'HR', 'description' => '']);
        $deptB = Departemen::create(['name' => 'Finance', 'description' => '']);
        $jabA = Jabatan::create(['name' => 'Manager', 'description' => '']);
        $jabB = Jabatan::create(['name' => 'Staff', 'description' => '']);

        ReligiousStudyEvent::create(['title' => 'A', 'event_at' => now()->addDay(), 'departemen_id' => $deptA->id, 'jabatan_id' => $jabA->id]);
        ReligiousStudyEvent::create(['title' => 'B', 'event_at' => now()->addDay(), 'departemen_id' => $deptB->id, 'jabatan_id' => $jabB->id]);

        $byDeptA = ReligiousStudyEvent::query()->where('departemen_id', $deptA->id)->get();
        $this->assertCount(1, $byDeptA);
        $this->assertEquals('A', $byDeptA->first()->title);

        $byJabB = ReligiousStudyEvent::query()->where('jabatan_id', $jabB->id)->get();
        $this->assertCount(1, $byJabB);
        $this->assertEquals('B', $byJabB->first()->title);
    }
}

