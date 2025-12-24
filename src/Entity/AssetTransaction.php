<?php

namespace App\Entity;

use App\Repository\AssetTransactionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AssetTransactionRepository::class)]
#[ORM\HasLifecycleCallbacks]
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

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $assignedTo = null;

    #[ORM\Column(length: 20)]
    private ?string $status = 'SOLICITADO';

    #[ORM\ManyToOne(inversedBy: 'assetTransactions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Asset $asset = null;

    #[ORM\ManyToOne]
    private ?Department $originDepartment = null;

    #[ORM\ManyToOne]
    private ?Department $destinationDepartment = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null; // Quien crea la solicitud

    // --- CAMPOS PARA FIRMAS DIGITALES (AUDITORÍA) ---

    #[ORM\ManyToOne]
    private ?User $techApprovedBy = null; // Firma de Tecnología

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $techApprovedAt = null;

    #[ORM\ManyToOne]
    private ?User $accountingApprovedBy = null; // Firma de Contabilidad

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $accountingApprovedAt = null;

    #[ORM\ManyToOne]
    private ?User $receivedBy = null; // Firma del Responsable de Destino (Área que recibe)

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $receivedAt = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        if (null === $this->status) {
            $this->status = 'SOLICITADO';
        }
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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
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

    // Getters y Setters de Firmas Digitales

    public function getTechApprovedBy(): ?User
    {
        return $this->techApprovedBy;
    }

    public function setTechApprovedBy(?User $techApprovedBy): static
    {
        $this->techApprovedBy = $techApprovedBy;
        return $this;
    }

    public function getTechApprovedAt(): ?\DateTimeImmutable
    {
        return $this->techApprovedAt;
    }

    public function setTechApprovedAt(?\DateTimeImmutable $techApprovedAt): static
    {
        $this->techApprovedAt = $techApprovedAt;
        return $this;
    }

    public function getAccountingApprovedBy(): ?User
    {
        return $this->accountingApprovedBy;
    }

    public function setAccountingApprovedBy(?User $accountingApprovedBy): static
    {
        $this->accountingApprovedBy = $accountingApprovedBy;
        return $this;
    }

    public function getAccountingApprovedAt(): ?\DateTimeImmutable
    {
        return $this->accountingApprovedAt;
    }

    public function setAccountingApprovedAt(?\DateTimeImmutable $accountingApprovedAt): static
    {
        $this->accountingApprovedAt = $accountingApprovedAt;
        return $this;
    }

    public function getReceivedBy(): ?User
    {
        return $this->receivedBy;
    }

    public function setReceivedBy(?User $receivedBy): static
    {
        $this->receivedBy = $receivedBy;
        return $this;
    }

    public function getReceivedAt(): ?\DateTimeImmutable
    {
        return $this->receivedAt;
    }

    public function setReceivedAt(?\DateTimeImmutable $receivedAt): static
    {
        $this->receivedAt = $receivedAt;
        return $this;
    }

    public function __toString(): string
    {
        return sprintf('[%s] %s - %s',
            $this->status,
            $this->type,
            $this->asset ? $this->asset->getName() : 'Sin Activo'
        );
    }
}
