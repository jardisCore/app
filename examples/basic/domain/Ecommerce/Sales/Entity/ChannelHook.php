<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Entity;

use JardisSupport\Data\Attribute\Column;
use JardisSupport\Data\Attribute\ForeignKey;
use JardisSupport\Data\Attribute\PrimaryKey;
use JardisSupport\Data\Attribute\Table;

/**
 * ChannelHook data model
 *
 * Generated from table: channel_hooks
 */
#[Table(name: 'channel_hooks')]
class ChannelHook
{
    public const PRIMARY_KEY = 'id';
    public const SOURCE = 'channel_hooks';
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
        'channel_id' => null,
        'target_url' => null,
        'signing_key' => null
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
    #[Column(name: 'channel_id', type: 'int')]
    #[ForeignKey(referencedTable: 'voucher_channels', referencedColumn: 'id')]
    protected ?int $channelId = null;

    /**
     * @var ?string
     */
    #[Column(name: 'target_url', type: 'varchar', length: 255)]
    protected ?string $targetUrl = null;

    /**
     * @var ?string
     */
    #[Column(name: 'signing_key', type: 'varchar', length: 64)]
    protected ?string $signingKey = null;

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

    public function getChannelId(): ?int
    {
        return $this->channelId;
    }

    public function getTargetUrl(): ?string
    {
        return $this->targetUrl;
    }

    public function getSigningKey(): ?string
    {
        return $this->signingKey;
    }

    public function setChannelId(?int $channelId): self
    {
        $this->channelId = $channelId;
        return $this;
    }

    public function setTargetUrl(?string $targetUrl): self
    {
        $this->targetUrl = $targetUrl;
        return $this;
    }

    public function setSigningKey(?string $signingKey): self
    {
        $this->signingKey = $signingKey;
        return $this;
    }
}
