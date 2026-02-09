<?php

namespace Tests\Feature;

use App\Filament\Resources\ReligiousStudies\Pages\CreateReligiousStudyEvent;
use App\Filament\Resources\ReligiousStudies\Pages\EditReligiousStudyEvent;
use App\Models\ReligiousStudyEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReligiousStudyEventToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_toggle_info_clears_fields_and_allows_empty_submission()
    {
        // Setup user
        $user = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com',
        ]);

        Livewire::actingAs($user)
            ->test(CreateReligiousStudyEvent::class)
            ->fillForm([
                'title' => 'Test Event',
                'location' => 'Masjid',
                'theme' => 'Kajian Rutin',
                'speaker' => 'Ustadz Fulan',
                'event_at' => now()->addDay(),
                'notify_at' => now()->addDay()->subHour(),
                'is_info' => false,
            ])
            ->assertFormSet([
                'location' => 'Masjid',
                'theme' => 'Kajian Rutin',
                'speaker' => 'Ustadz Fulan',
            ])
            // Toggle Info ON
            ->fillForm(['is_info' => true])
            // Assert fields are cleared (reactive behavior)
            ->assertFormSet([
                'location' => null,
                'theme' => null,
                'speaker' => null,
                'event_at' => null,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('religious_study_events', [
            'title' => 'Test Event',
            'is_info' => true,
            'location' => null,
            'theme' => null,
            'speaker' => null,
            'event_at' => null,
        ]);
    }

    public function test_validation_fails_when_info_is_off_and_fields_empty()
    {
        $user = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($user)
            ->test(CreateReligiousStudyEvent::class)
            ->fillForm([
                'title' => 'Test Event',
                'is_info' => false,
                'location' => null, // Should be required
            ])
            ->call('create')
            ->assertHasFormErrors(['location' => 'required']);
    }

    public function test_edit_existing_record_with_toggle()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $event = ReligiousStudyEvent::create([
            'title' => 'Existing Event',
            'location' => 'Masjid',
            'theme' => 'Old Theme',
            'speaker' => 'Old Speaker',
            'event_at' => now()->addDay(),
            'notify_at' => now()->addDay()->subHour(),
            'is_info' => false,
            'message' => 'Test message',
            'departemen_ids' => [],
            'jabatan_ids' => [],
        ]);

        Livewire::actingAs($user)
            ->test(EditReligiousStudyEvent::class, ['record' => $event->getRouteKey()])
            ->assertFormSet([
                'is_info' => false,
                'location' => 'Masjid',
            ])
            ->fillForm(['is_info' => true])
            ->assertFormSet([
                'is_info' => true,
                'location' => null,
                'theme' => null,
                'speaker' => null,
                'event_at' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('religious_study_events', [
            'id' => $event->id,
            'is_info' => true,
            'location' => null,
            'theme' => null,
            'speaker' => null,
            'event_at' => null,
        ]);
    }
}
