<?php

namespace App\Entity;

use App\Repository\AssetTransactionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AssetTransactionRepository::class)]
#[ORM\HasLifecycleCallbacks] // Para automatizar la fecha
class AssetTransaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null; // Ej: 'TRASLADO', 'DESCARGO', 'REPARACION'

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $observations = null;

    // NUEVO: Nombre de la persona a quien se le asigna el activo
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $assignedTo = null;

    #[ORM\ManyToOne(inversedBy: 'assetTransactions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Asset $asset = null;

    #[ORM\ManyToOne]
    private ?Department $originDepartment = null;

    #[ORM\ManyToOne]
    private ?Department $destinationDepartment = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getObservations(): ?string
    {
        return $this->observations;
    }

    public function setObservations(?string $observations): static
    {
        $this->observations = $observations;
        return $this;
    }

    public function getAssignedTo(): ?string
    {
        return $this->assignedTo;
    }

    public function setAssignedTo(?string $assignedTo): static
    {
        $this->assignedTo = $assignedTo;
        return $this;
    }

    public function getAsset(): ?Asset
    {
        return $this->asset;
    }

    public function setAsset(?Asset $asset): static
    {
        $this->asset = $asset;
        return $this;
    }

    public function getOriginDepartment(): ?Department
    {
        return $this->originDepartment;
    }

    public function setOriginDepartment(?Department $originDepartment): static
    {
        $this->originDepartment = $originDepartment;
        return $this;
    }

    public function getDestinationDepartment(): ?Department
    {
        return $this->destinationDepartment;
    }

    public function setDestinationDepartment(?Department $destinationDepartment): static
    {
        $this->destinationDepartment = $destinationDepartment;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function __toString(): string
    {
        return sprintf('%s - %s (%s)',
            $this->type,
            $this->asset ? $this->asset->getName() : 'Sin Activo',
            $this->createdAt ? $this->createdAt->format('d/m/Y') : 'Sin fecha'
        );
    }
}
