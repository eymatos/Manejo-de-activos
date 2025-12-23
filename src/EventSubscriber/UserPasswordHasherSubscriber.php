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
            BeforeEntityUpdatedEvent::class => ['hashPassword'],
        ];
    }

    public function handleBeforePersist(BeforeEntityPersistedEvent $event): void
    {
        $entity = $event->getEntityInstance();

        if ($entity instanceof User) {
            $this->processUserPassword($entity);
        }

        if ($entity instanceof AssetTransaction) {
            $this->processAssetTransaction($entity);
        }
    }

    public function hashPassword(BeforeEntityUpdatedEvent $event): void
    {
        $entity = $event->getEntityInstance();
        if ($entity instanceof User) {
            $this->processUserPassword($entity);
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

    private function processAssetTransaction(AssetTransaction $transaction): void
    {
        if ($this->security->getUser()) {
            $transaction->setUser($this->security->getUser());
        }

        $asset = $transaction->getAsset();
        $destination = $transaction->getDestinationDepartment();

        if ($asset && $transaction->getType() === 'TRASLADO') {
            // CAPTURAMOS EL ORIGEN: Guardamos el depto actual del activo antes de cambiarlo
            if ($asset->getCurrentDepartment()) {
                $transaction->setOriginDepartment($asset->getCurrentDepartment());
            }

            // AHORA SÍ: Movemos el activo al nuevo destino
            if ($destination) {
                $asset->setCurrentDepartment($destination);
                $asset->setUpdatedAt(new \DateTimeImmutable());
            }
        }
    }
}
