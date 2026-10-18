<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Entity;

use JardisSupport\Data\Attribute\Column;
use JardisSupport\Data\Attribute\ForeignKey;
use JardisSupport\Data\Attribute\PrimaryKey;
use JardisSupport\Data\Attribute\Table;

/**
 * VoucherChannel data model
 *
 * Generated from table: voucher_channels
 */
#[Table(name: 'voucher_channels')]
class VoucherChannel
{
    public const PRIMARY_KEY = 'id';
    public const SOURCE = 'voucher_channels';
    public const IS_AUTOINCREMENT = true;

    /**
     * Snapshot of original values for change tracking.
     * Managed via reflection by external services.
     *
     * @var array<string, mixed>
     */
    // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore -- Reflection-Slot jardissupport/data
    protected array $__snapshot = [
        'id' => null,
        'voucher_id' => null,
        'identifier' => null,
        'label' => null,
        'access_key' => null
    ];

    /**
     * @var ?int
     */
    #[PrimaryKey(autoIncrement: true)]
    #[Column(name: 'id', type: 'int')]
    protected ?int $id = null;

    /**
     * @var ?int
     */
    #[Column(name: 'voucher_id', type: 'int')]
    #[ForeignKey(referencedTable: 'vouchers', referencedColumn: 'id')]
    protected ?int $voucherId = null;

    /**
     * @var ?string
     */
    #[Column(name: 'identifier', type: 'varchar', length: 36)]
    protected ?string $identifier = null;

    /**
     * @var ?string
     */
    #[Column(name: 'label', type: 'varchar', length: 100)]
    protected ?string $label = null;

    /**
     * @var ?string
     */
    #[Column(name: 'access_key', type: 'varchar', length: 64)]
    protected ?string $accessKey = null;

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

    public function getVoucherId(): ?int
    {
        return $this->voucherId;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getAccessKey(): ?string
    {
        return $this->accessKey;
    }

    public function setVoucherId(?int $voucherId): self
    {
        $this->voucherId = $voucherId;
        return $this;
    }

    public function setIdentifier(?string $identifier): self
    {
        $this->identifier = $identifier;
        return $this;
    }

    public function setLabel(?string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function setAccessKey(?string $accessKey): self
    {
        $this->accessKey = $accessKey;
        return $this;
    }
}
