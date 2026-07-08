<?php

namespace Database\Seeders;

use App\Models\Territory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TerritorySeeder extends Seeder
{
    public function run(): void
    {
        // Catálogo inicial: comunas de la Región del Maule + capitales regionales + comunas relevantes.
        // Puede completarse con las 346 comunas de Chile en una fase posterior.
        $territories = [
            // Región del Maule
            ['name' => 'Talca', 'region' => 'Maule', 'province' => 'Talca'],
            ['name' => 'Constitución', 'region' => 'Maule', 'province' => 'Talca'],
            ['name' => 'Curepto', 'region' => 'Maule', 'province' => 'Talca'],
            ['name' => 'Empedrado', 'region' => 'Maule', 'province' => 'Talca'],
            ['name' => 'Maule', 'region' => 'Maule', 'province' => 'Talca'],
            ['name' => 'Pelarco', 'region' => 'Maule', 'province' => 'Talca'],
            ['name' => 'Pencahue', 'region' => 'Maule', 'province' => 'Talca'],
            ['name' => 'Río Claro', 'region' => 'Maule', 'province' => 'Talca'],
            ['name' => 'San Clemente', 'region' => 'Maule', 'province' => 'Talca'],
            ['name' => 'San Rafael', 'region' => 'Maule', 'province' => 'Talca'],
            ['name' => 'Cauquenes', 'region' => 'Maule', 'province' => 'Cauquenes'],
            ['name' => 'Chanco', 'region' => 'Maule', 'province' => 'Cauquenes'],
            ['name' => 'Pelluhue', 'region' => 'Maule', 'province' => 'Cauquenes'],
            ['name' => 'Curicó', 'region' => 'Maule', 'province' => 'Curicó'],
            ['name' => 'Hualañé', 'region' => 'Maule', 'province' => 'Curicó'],
            ['name' => 'Licantén', 'region' => 'Maule', 'province' => 'Curicó'],
            ['name' => 'Molina', 'region' => 'Maule', 'province' => 'Curicó'],
            ['name' => 'Rauco', 'region' => 'Maule', 'province' => 'Curicó'],
            ['name' => 'Romeral', 'region' => 'Maule', 'province' => 'Curicó'],
            ['name' => 'Sagrada Familia', 'region' => 'Maule', 'province' => 'Curicó'],
            ['name' => 'Teno', 'region' => 'Maule', 'province' => 'Curicó'],
            ['name' => 'Vichuquén', 'region' => 'Maule', 'province' => 'Curicó'],
            ['name' => 'Colbún', 'region' => 'Maule', 'province' => 'Linares'],
            ['name' => 'Linares', 'region' => 'Maule', 'province' => 'Linares'],
            ['name' => 'Longaví', 'region' => 'Maule', 'province' => 'Linares'],
            ['name' => 'Parral', 'region' => 'Maule', 'province' => 'Linares'],
            ['name' => 'Retiro', 'region' => 'Maule', 'province' => 'Linares'],
            ['name' => 'San Javier', 'region' => 'Maule', 'province' => 'Linares'],
            ['name' => 'Villa Alegre', 'region' => 'Maule', 'province' => 'Linares'],
            ['name' => 'Yerbas Buenas', 'region' => 'Maule', 'province' => 'Linares'],

            // Capitales regionales y comunas relevantes
            ['name' => 'Arica', 'region' => 'Arica y Parinacota', 'province' => 'Arica'],
            ['name' => 'Iquique', 'region' => 'Tarapacá', 'province' => 'Iquique'],
            ['name' => 'Antofagasta', 'region' => 'Antofagasta', 'province' => 'Antofagasta'],
            ['name' => 'Copiapó', 'region' => 'Atacama', 'province' => 'Copiapó'],
            ['name' => 'La Serena', 'region' => 'Coquimbo', 'province' => 'Elqui'],
            ['name' => 'Valparaíso', 'region' => 'Valparaíso', 'province' => 'Valparaíso'],
            ['name' => 'Viña del Mar', 'region' => 'Valparaíso', 'province' => 'Valparaíso'],
            ['name' => 'Rancagua', 'region' => "Libertador General Bernardo O'Higgins", 'province' => 'Cachapoal'],
            ['name' => 'Santiago', 'region' => 'Metropolitana de Santiago', 'province' => 'Santiago'],
            ['name' => 'Providencia', 'region' => 'Metropolitana de Santiago', 'province' => 'Santiago'],
            ['name' => 'Ñuñoa', 'region' => 'Metropolitana de Santiago', 'province' => 'Santiago'],
            ['name' => 'La Florida', 'region' => 'Metropolitana de Santiago', 'province' => 'Santiago'],
            ['name' => 'Maipú', 'region' => 'Metropolitana de Santiago', 'province' => 'Santiago'],
            ['name' => 'Las Condes', 'region' => 'Metropolitana de Santiago', 'province' => 'Santiago'],
            ['name' => 'Puente Alto', 'region' => 'Metropolitana de Santiago', 'province' => 'Cordillera'],
            ['name' => 'San Bernardo', 'region' => 'Metropolitana de Santiago', 'province' => 'Maipo'],
            ['name' => 'Concepción', 'region' => 'Biobío', 'province' => 'Concepción'],
            ['name' => 'Temuco', 'region' => 'La Araucanía', 'province' => 'Cautín'],
            ['name' => 'Valdivia', 'region' => 'Los Ríos', 'province' => 'Valdivia'],
            ['name' => 'Puerto Montt', 'region' => 'Los Lagos', 'province' => 'Llanquihue'],
            ['name' => 'Coyhaique', 'region' => 'Aysén del General Carlos Ibáñez del Campo', 'province' => 'Coyhaique'],
            ['name' => 'Punta Arenas', 'region' => 'Magallanes y de la Antártica Chilena', 'province' => 'Magallanes'],
        ];

        foreach ($territories as $index => $data) {
            $slug = Str::slug($data['name'].'-'.$data['region']);

            Territory::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $data['name'],
                    'region' => $data['region'],
                    'province' => $data['province'],
                    'is_active' => true,
                    'sort_order' => $index * 10,
                ]
            );
        }
    }
}
