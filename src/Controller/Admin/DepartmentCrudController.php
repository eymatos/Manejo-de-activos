<?php

namespace App\Controller\Admin;

use App\Entity\Department;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

class DepartmentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Department::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id', 'ID')->onlyOnDetail(),
            TextField::new('name', 'Departamento'),

            // Usamos formatValue para mostrar solo la cantidad en lugar de nombres largos
            AssociationField::new('assets', 'Total Activos')
                ->onlyOnIndex()
                ->formatValue(function ($value, $entity) {
                    return count($entity->getAssets()) . ' equipos';
                }),
        ];
    }
}
