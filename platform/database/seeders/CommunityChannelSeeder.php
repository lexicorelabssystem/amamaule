<?php

namespace Database\Seeders;

use App\Models\CommunityChannel;
use App\Models\Discipline;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CommunityChannelSeeder extends Seeder
{
    public function run(): void
    {
        CommunityChannel::firstOrCreate(
            ['slug' => 'general'],
            [
                'name' => 'General',
                'description' => 'Espacio com?n para anuncios, coordinaci?n y conversaci?n entre artistas AMA.',
            ]
        );

        Discipline::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->each(function (Discipline $discipline) {
                CommunityChannel::firstOrCreate(
                    ['slug' => 'disciplina-'.Str::slug($discipline->name)],
                    [
                        'discipline_id' => $discipline->id,
                        'name' => $discipline->name,
                        'description' => 'Canal de comunidad para artistas de '.$discipline->name.'.',
                    ]
                );
            });
    }
}
