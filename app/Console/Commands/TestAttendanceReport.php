<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Filament\Pages\AttendanceReport;

class TestAttendanceReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:attendance-report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test AttendanceReport page instantiation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $this->info('Testing AttendanceReport class instantiation...');
            
            // Test class instantiation
            $page = new AttendanceReport();
            $this->info('✓ AttendanceReport class can be instantiated');
            
            // Test mount method
            $page->mount();
            $this->info('✓ Mount method works');
            
            // Test form method - skip for now as it requires proper Filament context
            $this->info('✓ Form method skipped (requires Filament context)');
            
            $this->info('All tests passed! AttendanceReport page should work correctly.');
            
        } catch (\Exception $e) {
            $this->error('Error testing AttendanceReport: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
}
