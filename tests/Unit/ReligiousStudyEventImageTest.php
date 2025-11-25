<?php

namespace Tests\Unit;

use App\Models\ReligiousStudyEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReligiousStudyEventImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_image_sets_path_and_file_exists(): void
    {
        Storage::fake('public');
        $event = ReligiousStudyEvent::create([
            'title' => 'Kajian Gambar',
            'event_at' => now()->addDay(),
            'notify_at' => now(),
            'cancelled' => false,
            'notified' => false,
        ]);

        $file = UploadedFile::fake()->image('poster.png', 600, 600);
        $ok = $event->storeImage($file);
        $this->assertTrue($ok);
        $this->assertNotEmpty($event->image_path);
        Storage::disk('public')->assertExists($event->image_path);
    }

    public function test_image_url_accessor_returns_public_url(): void
    {
        Storage::fake('public');
        $event = ReligiousStudyEvent::create([
            'title' => 'Kajian URL',
            'event_at' => now()->addDay(),
            'notify_at' => now(),
            'cancelled' => false,
            'notified' => false,
        ]);
        $file = UploadedFile::fake()->image('poster2.png');
        $event->storeImage($file);

        $url = $event->image_url;
        $this->assertNotNull($url);
        $this->assertTrue(Storage::disk('public')->exists($event->image_path));
    }
}
