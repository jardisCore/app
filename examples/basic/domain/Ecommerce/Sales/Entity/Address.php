<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Entity;

use JardisSupport\Data\Attribute\Column;
use JardisSupport\Data\Attribute\PrimaryKey;
use JardisSupport\Data\Attribute\Table;

/**
 * Address data model
 *
 * Generated from table: addresses
 */
#[Table(name: 'addresses')]
class Address
{
    public const PRIMARY_KEY = 'id';
    public const SOURCE = 'addresses';
    public const IS_AUTOINCREMENT = true;

    /**
     * Snapshot of original values for change tracking.
     * Managed via reflection by external services.
     *
     * @var array<string, mixed>
     */
    protected array $__snapshot = [
        'id' => null,
        'street' => null,
        'city' => null,
        'postal_code' => null,
        'country' => null
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
    #[Column(name: 'street', type: 'varchar', length: 255)]
    protected ?string $street = null;

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
    #[Column(name: 'country', type: 'varchar', length: 2)]
    protected ?string $country = null;

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

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setStreet(?string $street): self
    {
        $this->street = $street;
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

    public function setCountry(?string $country): self
    {
        $this->country = $country;
        return $this;
    }
}
