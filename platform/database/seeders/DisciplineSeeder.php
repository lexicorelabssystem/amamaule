<?php

namespace Database\Seeders;

use App\Models\Discipline;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DisciplineSeeder extends Seeder
{
    public function run(): void
    {
        $disciplines = [
            ['name' => 'Artes visuales', 'description' => 'Pintura, escultura, instalación, arte objeto.'],
            ['name' => 'Danza', 'description' => 'Danza contemporánea, clásica, folclórica y urbana.'],
            ['name' => 'Teatro', 'description' => 'Actuación, dirección, dramaturgia.'],
            ['name' => 'Música', 'description' => 'Interpretación, composición, producción musical.'],
            ['name' => 'Literatura', 'description' => 'Poesía, narrativa, ensayo, guionismo.'],
            ['name' => 'Fotografía', 'description' => 'Fotografía artística, documental y experimental.'],
            ['name' => 'Cine y video', 'description' => 'Realización audiovisual, documental, experimental.'],
            ['name' => 'Artesanía', 'description' => 'Oficios tradicionales y diseño artesanal.'],
            ['name' => 'Circo', 'description' => 'Artes circenses contemporáneas y tradicionales.'],
            ['name' => 'Performance', 'description' => 'Arte de acción e intervención.'],
            ['name' => 'Diseño', 'description' => 'Diseño gráfico, industrial, textil y multimedia.'],
            ['name' => 'Arquitectura', 'description' => 'Arquitectura, paisajismo y diseño espacial.'],
            ['name' => 'Comic y muralismo', 'description' => 'Ilustración, historieta, muralismo urbano.'],
            ['name' => 'Gastronomía y cocina', 'description' => 'Arte culinario y tradiciones alimentarias.'],
            ['name' => 'Otras disciplinas', 'description' => 'Disciplinas no contempladas en el listado principal.'],
        ];

        foreach ($disciplines as $index => $data) {
            Discipline::firstOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'is_active' => true,
                    'sort_order' => $index * 10,
                ]
            );
        }
    }
}
