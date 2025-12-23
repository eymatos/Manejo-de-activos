<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),

            EmailField::new('email', 'Correo Electrónico'),

            // Campo de contraseña con validación de repetición
            TextField::new('password', 'Contraseña')
                ->setFormType(RepeatedType::class)
                ->setFormTypeOptions([
                    'type' => PasswordType::class,
                    'first_options' => ['label' => 'Contraseña'],
                    'second_options' => ['label' => 'Repetir Contraseña'],
                    'mapped' => false, // Importante para no chocar con el hash
                ])
                ->setRequired($pageName === 'new')
                ->onlyOnForms(),

            // Selector de Roles (Symfony guarda los roles como un Array)
            ChoiceField::new('roles', 'Permisos / Roles')
                ->allowMultipleChoices()
                ->setChoices([
                    'Administrador' => 'ROLE_ADMIN',
                    'Usuario Estándar' => 'ROLE_USER',
                    'Encargado de Inventario' => 'ROLE_EDITOR',
                ])
                ->renderAsBadges(),
        ];
    }
}
