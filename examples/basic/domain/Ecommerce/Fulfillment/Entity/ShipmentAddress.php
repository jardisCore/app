<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Entity;

use JardisSupport\Data\Attribute\Column;
use JardisSupport\Data\Attribute\PrimaryKey;
use JardisSupport\Data\Attribute\Table;

/**
 * ShipmentAddress data model
 *
 * Generated from table: shipment_addresses
 */
#[Table(name: 'shipment_addresses')]
class ShipmentAddress
{
    public const PRIMARY_KEY = 'id';
    public const SOURCE = 'shipment_addresses';
    public const IS_AUTOINCREMENT = true;

    /**
     * Snapshot of original values for change tracking.
     * Managed via reflection by external services.
     *
     * @var array<string, mixed>
     */
    protected array $__snapshot = [
        'id' => null,
        'recipient_name' => null,
        'company' => null,
        'street' => null,
        'street2' => null,
        'city' => null,
        'postal_code' => null,
        'state' => null,
        'country' => null,
        'phone' => null
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
    #[Column(name: 'recipient_name', type: 'varchar', length: 255)]
    protected ?string $recipientName = null;

    /**
     * @var ?string
     */
    #[Column(name: 'company', type: 'varchar', length: 255, nullable: true)]
    protected ?string $company = null;

    /**
     * @var ?string
     */
    #[Column(name: 'street', type: 'varchar', length: 255)]
    protected ?string $street = null;

    /**
     * @var ?string
     */
    #[Column(name: 'street2', type: 'varchar', length: 255, nullable: true)]
    protected ?string $street2 = null;

    /**
     * @var ?string
     */
    #[Column(name: 'city', type: 'varchar', length: 100)]
    protected ?string $city = null;

    /**
     * @var ?string
     */
    #[Column(name: 'postal_code', type: 'varchar', length: 20)]
    protected ?string $postalCode = null;

    /**
     * @var ?string
     */
    #[Column(name: 'state', type: 'varchar', length: 100, nullable: true)]
    protected ?string $state = null;

    /**
     * @var ?string
     */
    #[Column(name: 'country', type: 'varchar', length: 2)]
    protected ?string $country = null;

    /**
     * @var ?string
     */
    #[Column(name: 'phone', type: 'varchar', length: 50, nullable: true)]
    protected ?string $phone = null;

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

    public function getRecipientName(): ?string
    {
        return $this->recipientName;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function getStreet2(): ?string
    {
        return $this->street2;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setRecipientName(?string $recipientName): self
    {
        $this->recipientName = $recipientName;
        return $this;
    }

    public function setCompany(?string $company): self
    {
        $this->company = $company;
        return $this;
    }

    public function setStreet(?string $street): self
    {
        $this->street = $street;
        return $this;
    }

    public function setStreet2(?string $street2): self
    {
        $this->street2 = $street2;
        return $this;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;
        return $this;
    }

    public function setPostalCode(?string $postalCode): self
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    public function setState(?string $state): self
    {
        $this->state = $state;
        return $this;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;
        return $this;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }
}
