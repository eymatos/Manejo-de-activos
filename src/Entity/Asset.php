<?php

namespace App\Entity;

use App\Repository\AssetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AssetRepository::class)]
#[ORM\HasLifecycleCallbacks] // Esto permite que las fechas se actualicen solas
class Asset
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $brand = null;

    #[ORM\Column(length: 255)]
    private ?string $serial = null;

    #[ORM\Column(length: 255)]
    private ?string $assetNumber = null;

    #[ORM\Column(length: 255)]
    private ?string $nationalInventoryNumber = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'assets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Department $currentDepartment = null;

    /**
     * @var Collection<int, AssetTransaction>
     */
    #[ORM\OneToMany(targetEntity: AssetTransaction::class, mappedBy: 'asset')]
    private Collection $assetTransactions;

    public function __construct()
    {
        $this->assetTransactions = new ArrayCollection();
        // Inicializamos las fechas por defecto
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): static
    {
        $this->brand = $brand;
        return $this;
    }

    public function getSerial(): ?string
    {
        return $this->serial;
    }

    public function setSerial(string $serial): static
    {
        $this->serial = $serial;
        return $this;
    }

    public function getAssetNumber(): ?string
    {
        return $this->assetNumber;
    }

    public function setAssetNumber(string $assetNumber): static
    {
        $this->assetNumber = $assetNumber;
        return $this;
    }

    public function getNationalInventoryNumber(): ?string
    {
        return $this->nationalInventoryNumber;
    }

    public function setNationalInventoryNumber(string $nationalInventoryNumber): static
    {
        $this->nationalInventoryNumber = $nationalInventoryNumber;
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getCurrentDepartment(): ?Department
    {
        return $this->currentDepartment;
    }

    public function setCurrentDepartment(?Department $currentDepartment): static
    {
        $this->currentDepartment = $currentDepartment;
        return $this;
    }

    /**
     * @return Collection<int, AssetTransaction>
     */
    public function getAssetTransactions(): Collection
    {
        return $this->assetTransactions;
    }

    public function addAssetTransaction(AssetTransaction $assetTransaction): static
    {
        if (!$this->assetTransactions->contains($assetTransaction)) {
            $this->assetTransactions->add($assetTransaction);
            $assetTransaction->setAsset($this);
        }
        return $this;
    }

    public function removeAssetTransaction(AssetTransaction $assetTransaction): static
    {
        if ($this->assetTransactions->removeElement($assetTransaction)) {
            if ($assetTransaction->getAsset() === $this) {
                $assetTransaction->setAsset(null);
            }
        }
        return $this;
    }
    public function __toString(): string
    {
        // Esto mostrará: "Laptop Dell (SN: 15456466797)" en los desplegables
        return sprintf('%s %s (SN: %s)', $this->name, $this->brand, $this->serial);
    }

}
