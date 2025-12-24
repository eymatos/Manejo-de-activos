<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Usuario')
            ->setEntityLabelInPlural('Usuarios')
            ->setPageTitle('index', 'Gestión de Usuarios y Accesos');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),

            EmailField::new('email', 'Correo Electrónico'),

            TextField::new('plainPassword', 'Contraseña')
                ->setFormType(RepeatedType::class)
                ->setFormTypeOptions([
                    'type' => PasswordType::class,
                    'first_options' => ['label' => 'Contraseña'],
                    'second_options' => ['label' => 'Repetir Contraseña'],
                ])
                ->setRequired($pageName === Crud::PAGE_NEW)
                ->onlyOnForms(),

            AssociationField::new('department', 'Departamento / Área')
                ->setHelp('Asigne el departamento para que el usuario pueda firmar recepciones.'),

            ChoiceField::new('roles', 'Permisos / Roles')
                ->allowMultipleChoices()
                ->setChoices([
                    'Administrador Total' => 'ROLE_ADMIN',
                    'Encargado de Contabilidad' => 'ROLE_CONTABILIDAD',
                    'Encargado de Tecnología' => 'ROLE_TECNOLOGIA',
                    'Usuario Estándar' => 'ROLE_USER',
                ])
                ->renderAsBadges([
                    'ROLE_ADMIN' => 'danger',
                    'ROLE_CONTABILIDAD' => 'success',
                    'ROLE_TECNOLOGIA' => 'info',
                    'ROLE_USER' => 'secondary',
                ]),
        ];
    }
}
