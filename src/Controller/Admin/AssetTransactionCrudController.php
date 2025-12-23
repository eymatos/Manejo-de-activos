<?php

namespace App\Controller\Admin;

use App\Entity\AssetTransaction;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

class AssetTransactionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AssetTransaction::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Transacción')
            ->setEntityLabelInPlural('Historial de Movimientos')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            DateTimeField::new('createdAt', 'Fecha')->setFormat('dd/MM/yy HH:mm')->onlyOnIndex(),
            AssociationField::new('asset', 'Activo'),
            ChoiceField::new('type', 'Operación')->setChoices([
                'Traslado' => 'TRASLADO', 'Descargo' => 'DESCARGO', 'Reparación' => 'REPARACION'
            ])->renderAsBadges(),

            // Campo Responsable solicitado
            TextField::new('assignedTo', 'Responsable / Recibe'),

            AssociationField::new('originDepartment', 'Origen')->onlyOnIndex()
                ->formatValue(fn ($v) => $v ?: '✨ Nuevo'),

            AssociationField::new('destinationDepartment', 'Destino'),
            TextField::new('observations', 'Observaciones')->hideOnIndex(),
        ];
    }
}
