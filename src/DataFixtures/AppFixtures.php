<?php

namespace App\DataFixtures;

use App\Entity\Department;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $departamentos = [
            'Dirección Ejecutiva', 'Departamento Financiero', 'Direccion de Planificación',
            'Dirección de Recursos Humanos', 'Departamento Administrativo', 'Div. de Servicios Generales',
            'Div. de Transportación', 'Unidad Contraloria', 'Recepción',
            'Departamento de Inspección y Vigilancia', 'Caja', 'Departamento de Servicio al Usuario',
            'Dirección de Comunicaciones', 'Dirección Juridica', 'Departamento de Analisis de Precio',
            'Departamento de Buenas Prácticas Comerciales', 'Departamento de Educación al Consumidor',
            'Departamento de Internacional', 'Departamento de Analisis de Casos', 'OAI',
            'Div. de Mensajeria', 'Departamento de Conciliación', 'Departamento de Tecnología',
            'Departamento de Coordinación Provincial', 'Departamento de Seguridad',
            'Sede: Megacentro', 'Sede: Colinacentro', 'Sede: El Conde', 'Sede: Sambil', 'Sede: Occidental Mall',
            'Provincial: Santiago', 'Provincial: Moca', 'Provincial: Puerto Plata', 'Provincial: SFM',
            'Provincial: La Vega', 'Provincial: Cotui', 'Provincial: Barahona', 'Provincial: San Cristobal',
            'Provincial: San Juan', 'Provincial: Ocoa', 'Provincial: Nagua', 'Provincial: Punta Cana',
            'Provincial: Hato Mayor', 'Provincial: La Romana', 'Provincial: San Pedro de Macoris'
        ];

        foreach ($departamentos as $nombre) {
            $dept = new Department();
            $dept->setName($nombre);

            // Asignamos una ubicación por defecto para evitar el error de SQL
            if (str_contains($nombre, 'Provincial:')) {
                $dept->setLocation('Oficina Regional');
            } elseif (str_contains($nombre, 'Sede:')) {
                $dept->setLocation('Punto de Servicio');
            } else {
                $dept->setLocation('Sede Central');
            }

            $manager->persist($dept);
        }

        $manager->flush();
    }
}
