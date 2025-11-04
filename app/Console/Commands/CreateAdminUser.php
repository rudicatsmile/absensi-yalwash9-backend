<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create admin user for testing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $user = User::firstOrCreate(
                ['email' => 'admin@test.com'],
                [
                    'name' => 'Admin Test',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
            
            $this->info('Admin user created/found:');
            $this->info('Email: admin@test.com');
            $this->info('Password: password');
            
        } catch (\Exception $e) {
            $this->error('Error creating admin user: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
