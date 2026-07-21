<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Entity;

use DateTime;
use JardisSupport\Data\Attribute\Column;
use JardisSupport\Data\Attribute\ForeignKey;
use JardisSupport\Data\Attribute\PrimaryKey;
use JardisSupport\Data\Attribute\Table;

/**
 * Customer data model
 *
 * Generated from table: customers
 */
#[Table(name: 'customers')]
class Customer
{
    public const PRIMARY_KEY = 'id';
    public const SOURCE = 'customers';
    public const IS_AUTOINCREMENT = true;

    /**
     * Snapshot of original values for change tracking.
     * Managed via reflection by external services.
     *
     * @var array<string, mixed>
     */
    protected array $__snapshot = [
        'id' => null,
        'identifier' => null,
        'email' => null,
        'name' => null,
        'phone' => null,
        'billing_address_id' => null,
        'created_at' => 'CURRENT_TIMESTAMP'
    ];

    /**
     * @var ?int
     */
    #[PrimaryKey(autoIncrement: true)]
    #[Column(name: 'id', type: 'int')]
    protected ?int $id = null;

    /**
     * @var ?string
     */
    #[Column(name: 'identifier', type: 'varchar', length: 36)]
    protected ?string $identifier = null;

    /**
     * @var ?string
     */
    #[Column(name: 'email', type: 'varchar', length: 255)]
    protected ?string $email = null;

    /**
     * @var ?string
     */
    #[Column(name: 'name', type: 'varchar', length: 255)]
    protected ?string $name = null;

    /**
     * @var ?string
     */
    #[Column(name: 'phone', type: 'varchar', length: 50, nullable: true)]
    protected ?string $phone = null;

    /**
     * @var ?int
     */
    #[Column(name: 'billing_address_id', type: 'int', nullable: true)]
    #[ForeignKey(referencedTable: 'addresses', referencedColumn: 'id', onUpdate: 'NO ACTION', onDelete: 'NO ACTION')]
    protected ?int $billingAddressId = null;

    /**
     * @var ?DateTime
     */
    #[Column(name: 'created_at', type: 'timestamp', nullable: true, default: 'CURRENT_TIMESTAMP')]
    protected ?DateTime $createdAt = null;

    /**
     * Returns the snapshot of original values.
     *
     * @return array<string, mixed>
     */
    public function getSnapshot(): array
    {
        return $this->__snapshot;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getBillingAddressId(): ?int
    {
        return $this->billingAddressId;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function setIdentifier(?string $identifier): self
    {
        $this->identifier = $identifier;
        return $this;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function setBillingAddressId(?int $billingAddressId): self
    {
        $this->billingAddressId = $billingAddressId;
        return $this;
    }

    public function setCreatedAt(?DateTime $createdAt = null): self
    {
        $this->createdAt = $createdAt ?? new DateTime();
        return $this;
    }
}
