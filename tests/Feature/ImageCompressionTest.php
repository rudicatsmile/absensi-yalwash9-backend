<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\LeaveType;
use App\Models\PermitType;
use App\Models\LeaveBalance;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Services\ImageCompressionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageCompressionTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test that the service correctly compresses and resizes a large image.
     */
    public function test_service_compresses_and_resizes_large_image()
    {
        Storage::fake('public');

        // Create a large image (2000x2000)
        $file = UploadedFile::fake()->image('large_image.jpg', 2000, 2000);

        $service = new ImageCompressionService();
        // Compress with max width 1000
        $path = $service->compressAndSave($file, 'test_uploads', quality: 75, maxWidth: 1000);

        // Assert file exists
        Storage::disk('public')->assertExists($path);

        // Verify resizing
        $content = Storage::disk('public')->get($path);
        $manager = new ImageManager(new Driver());
        $image = $manager->read($content);

        // Width should be scaled down to 1000
        $this->assertEquals(1000, $image->width());
        // Height should be proportional (1000)
        $this->assertEquals(1000, $image->height());
    }

    /**
     * Test that non-image files are stored without compression.
     */
    public function test_service_handles_non_image_files()
    {
        Storage::fake('public');

        // Create a fake PDF
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $service = new ImageCompressionService();
        $path = $service->compressAndSave($file, 'test_uploads');

        Storage::disk('public')->assertExists($path);
        // Should be stored as is (likely with hash name but extension usually preserved if stored properly,
        // but UploadedFile::store() generates hash name with extension)
        // Let's check if we can open it as image (should fail) or just check existence.

        // Since we are mocking storage, we just verify it exists.
        $this->assertTrue(Storage::disk('public')->exists($path));
    }

    /**
     * Test LeaveController integration.
     */
    public function test_leave_controller_uses_compression()
    {
        Storage::fake('public');

        // Setup dependencies
        $user = User::factory()->create();

        $leaveType = LeaveType::create([
            'name' => 'Cuti Tahunan',
            'quota_days' => 12,
            'is_paid' => true
        ]);

        // Seed LeaveBalance
        LeaveBalance::updateOrCreate(
            [
                'employee_id' => $user->id,
                'leave_type_id' => $leaveType->id,
                'year' => now()->year,
            ],
            [
                'remaining_days' => 12,
                'quota_days' => 12,
                'used_days' => 0,
                'carry_over_days' => 0,
                'last_updated' => now()
            ]
        );

        // Create a large image
        $file = UploadedFile::fake()->image('leave_attachment.jpg', 1500, 1500);

        $response = $this->actingAs($user)->postJson('/api/leaves', [
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->addDay()->format('Y-m-d'),
            'end_date' => now()->addDays(2)->format('Y-m-d'),
            'reason' => 'Testing Compression',
            'attachment' => $file,
        ]);

        $response->assertStatus(201); // Created

        // Verify attachment_url in response
        $attachmentUrl = $response->json('data.attachment_url');
        $this->assertNotNull($attachmentUrl);
        Storage::disk('public')->assertExists($attachmentUrl);

        // Verify it was resized (default max 1280)
        $content = Storage::disk('public')->get($attachmentUrl);
        $manager = new ImageManager(new Driver());
        $image = $manager->read($content);

        $this->assertLessThanOrEqual(1280, $image->width());
    }

    /**
     * Test PermitController integration.
     */
    public function test_permit_controller_uses_compression()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $permitType = PermitType::create([
            'name' => 'Izin Sakit',
            'quota_days' => 5,
            'urut' => 1,
        ]);

        $file = UploadedFile::fake()->image('permit_attachment.png', 1600, 1200);

        $response = $this->actingAs($user)->postJson('/api/permits', [
            'permit_type_id' => $permitType->id,
            'start_date' => now()->addDay()->format('Y-m-d'),
            'end_date' => now()->addDay()->format('Y-m-d'),
            'reason' => 'Testing Permit Compression',
            'attachment' => $file,
            'shift_kerja_id' => 1
        ]);

        $response->assertStatus(201);

        $attachmentUrl = $response->json('data.attachment_url');
        Storage::disk('public')->assertExists($attachmentUrl);

        // Verify resizing
        $content = Storage::disk('public')->get($attachmentUrl);
        $manager = new ImageManager(new Driver());
        $image = $manager->read($content);

        $this->assertLessThanOrEqual(1280, $image->width());
    }
}
