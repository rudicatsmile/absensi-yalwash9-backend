<?php

namespace Tests\Unit;

use App\Models\ReligiousStudyEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReligiousStudyEventOptionalFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_without_departemen_and_jabatan_is_valid(): void
    {
        $event = ReligiousStudyEvent::create([
            'title' => 'Kajian Opsional',
            'event_at' => now()->addDay(),
            'notify_at' => now(),
            'departemen_id' => null,
            'jabatan_id' => null,
        ]);

        $this->assertNotNull($event->id);
        $this->assertNull($event->departemen_id);
        $this->assertNull($event->jabatan_id);
        $this->assertNull($event->departemen);
        $this->assertNull($event->jabatan);
    }

    public function test_update_to_null_departemen_and_jabatan_is_valid(): void
    {
        $event = ReligiousStudyEvent::create([
            'title' => 'Kajian Update',
            'event_at' => now()->addDay(),
            'notify_at' => now(),
            'departemen_id' => null,
            'jabatan_id' => null,
        ]);

        $event->departemen_id = null;
        $event->jabatan_id = null;
        $event->save();

        $this->assertNull($event->fresh()->departemen_id);
        $this->assertNull($event->fresh()->jabatan_id);
    }
}

