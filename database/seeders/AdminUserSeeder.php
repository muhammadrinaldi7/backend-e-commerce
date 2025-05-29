<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            'id' => Str::uuid(),
            'name' => 'Super Admin',
            'email' => 'admin@zedgroup.test',
            'password' => Hash::make('admin123'), // Gantilah password sesuai kebutuhan
            'is_admin' => true,
            'avatar' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
