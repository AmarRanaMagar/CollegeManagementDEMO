<?php

namespace Database\Seeders;

use App\Models\SchoolSession;
use Illuminate\Database\Seeder;

class SchoolSessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        SchoolSession::firstOrCreate([
            'session_name' => 'Demo Academic Session',
        ]);
    }
}
