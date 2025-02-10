<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdminRole = Role::create(['name' => 'superAdmin']);
        $deliveryRole = Role::create(['name' => 'delivery']);
        $clientRole = Role::create(['name' => 'client']);

        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super-admin@super.com',
            'phone' => '0123456789',
            'password' => bcrypt('12345678'),
        ]);

        $delivery = User::create([
            'name' => 'delivery',
            'email' => 'delivery@delivery.com',
            'phone' => '0123456789',
            'password' => bcrypt('12345678'),
        ]);

        $client = User::create([
            'name' => 'Kimo',
            'email' => 'kareemhussen500@gmail.com',
            'phone' => '0123456789',
            'password' => bcrypt('12345678'),
        ]);


        $superAdmin->assignRole($superAdminRole);
        $delivery->assignRole($deliveryRole);
        $client->assignRole($clientRole);


    }
}
