<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = [
            [
                'name' => 'John Doe',
                'email' => 'johndoe@gmail.com',
                'status' => 'active',
                'password' => Hash::make('#@1Password'),
                'role' => 'Admin',
                'lastlogin' => now(),
            ],
            [
                'name' => 'Kimera Farouk',
                'email' => 'kimerafarouk8@gmail.com',
                'status' => 'active',
                'nin_no' => 'NIN1234567890',
                'password' => Hash::make('#@1Password'),
                'phone_number' => '0700000001',
                'role' => 'Admin',
                'lastlogin' => now(),
            ],
            [
                'name' => 'Bloody Kheeng',
                'email' => 'bloodykheeng@gmail.com',
                'status' => 'active',
                'nin_no' => 'NIN1234567891',
                'password' => Hash::make('#@1Password'),
                'phone_number' => '0700000002',
                'role' => 'Vendor',
                'lastlogin' => now(),
            ],
            [
                'name' => 'Health Star',
                'email' => 'healthstarug@gmail.com',
                'status' => 'active',
                'nin_no' => 'NIN1234567892',
                'password' => Hash::make('#@1Password'),
                'phone_number' => '0700000003',
                'role' => 'Seller',
                'lastlogin' => now(),
            ],
            [
                'name' => 'Music Revanced',
                'email' => 'musicrevanced6@gmail.com',
                'status' => 'active',
                'nin_no' => 'NIN1234567893',
                'password' => Hash::make('#@1Password'),
                'phone_number' => '0700000004',
                'role' => 'Buyer',
                'lastlogin' => now(),
            ],
            [
                'name' => 'Monitoring Portal',
                'email' => 'monitoringportal1@gmail.com',
                'status' => 'active',
                'nin_no' => 'NIN1234567894',
                'password' => Hash::make('#@1Password'),
                'phone_number' => '0700000005',
                'role' => 'Inspector',
                'lastlogin' => now(),
            ],

            // Add additional users as needed
        ];

        $existingUsers = [];
        $createdUsers = [];

        foreach ($users as $userData) {
            // Remove 'role' from the array before creating the user
            $roleName = $userData['role'];
            unset($userData['role']);

            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );

            if ($user->wasRecentlyCreated) {
                $createdUsers[] = $user->email;

                // Check if the role exists and assign it to the user
                if (Role::where('name', $roleName)->exists()) {
                    $user->assignRole($roleName);
                }
            } else {
                $existingUsers[] = $user->email;
            }
        }

        // Output to the console
        $this->command->info('Existing Users: ' . implode(', ', $existingUsers));
        $this->command->info('Created Users: ' . implode(', ', $createdUsers));
    }
}