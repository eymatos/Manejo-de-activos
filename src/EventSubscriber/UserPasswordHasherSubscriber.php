<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Entity\AssetTransaction;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\SecurityBundle\Security;

class UserPasswordHasherSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private Security $security
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityPersistedEvent::class => ['handleBeforePersist'],
            BeforeEntityUpdatedEvent::class => ['handleBeforeUpdate'],
        ];
    }

    /**
     * Al CREAR la transacción (Equivale a llenar el formulario físico)
     */
    public function handleBeforePersist(BeforeEntityPersistedEvent $event): void
    {
        $entity = $event->getEntityInstance();

        if ($entity instanceof User) {
            $this->processUserPassword($entity);
        }

        if ($entity instanceof AssetTransaction) {
            // Registramos quién crea la solicitud (Firma del Solicitante)
            if ($this->security->getUser()) {
                $entity->setUser($this->security->getUser());
            }

            $asset = $entity->getAsset();
            if ($asset) {
                // Capturamos el origen automáticamente desde la ubicación actual del activo
                if ($asset->getCurrentDepartment()) {
                    $entity->setOriginDepartment($asset->getCurrentDepartment());
                }
            }
        }
    }

    /**
     * Al ACTUALIZAR la transacción (Proceso de Firmas Digitales)
     */
    public function handleBeforeUpdate(BeforeEntityUpdatedEvent $event): void
    {
        $entity = $event->getEntityInstance();

        if ($entity instanceof User) {
            $this->processUserPassword($entity);
        }

        if ($entity instanceof AssetTransaction) {
            $this->processDigitalSignatures($entity);
        }
    }

    private function processUserPassword(User $user): void
    {
        $plainPassword = $user->getPlainPassword();
        if ($plainPassword) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
            $user->eraseCredentials();
        }
    }

    /**
     * Lógica de Validación de Firmas según los formularios de Excel
     */
    private function processDigitalSignatures(AssetTransaction $transaction): void
    {
        $asset = $transaction->getAsset();
        if (!$asset) return;

        $currentUser = $this->security->getUser();
        if (!$currentUser) return;

        // 1. Gestionar Timestamps de Firmas
        if ($transaction->getTechApprovedBy() && !$transaction->getTechApprovedAt()) {
            $transaction->setTechApprovedAt(new \DateTimeImmutable());
        }

        if ($transaction->getAccountingApprovedBy() && !$transaction->getAccountingApprovedAt()) {
            $transaction->setAccountingApprovedAt(new \DateTimeImmutable());
        }

        if ($transaction->getReceivedBy() && !$transaction->getReceivedAt()) {
            $transaction->setReceivedAt(new \DateTimeImmutable());
        }

        // 2. VERIFICACIÓN FINAL: ¿Podemos mover el activo?
        $isComputer = ($asset->getCategory() === 'COMPUTO');

        $hasTechSignature = $transaction->getTechApprovedBy() !== null;
        $hasAccountingSignature = $transaction->getAccountingApprovedBy() !== null;
        $hasReceiverSignature = $transaction->getReceivedBy() !== null;

        $readyForTransfer = $hasAccountingSignature && $hasReceiverSignature;

        if ($isComputer && !$hasTechSignature) {
            $readyForTransfer = false;
        }

        if ($readyForTransfer) {
            $destination = $transaction->getDestinationDepartment();
            if ($destination) {
                // Ejecutamos el traslado físico en el sistema
                $asset->setCurrentDepartment($destination);

                // ACTUALIZACIÓN SOLICITADA: Guardamos quién tiene el activo ahora
                if ($transaction->getAssignedTo()) {
                    $asset->setCurrentHolder($transaction->getAssignedTo());
                }

                $asset->setUpdatedAt(new \DateTimeImmutable());
                $transaction->setStatus('ACEPTADO');
            }
        }
    }
}
