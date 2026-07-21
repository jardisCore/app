<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Entity;

use JardisSupport\Data\Attribute\Column;
use JardisSupport\Data\Attribute\ForeignKey;
use JardisSupport\Data\Attribute\PrimaryKey;
use JardisSupport\Data\Attribute\Table;

/**
 * CategoryTranslation data model
 *
 * Generated from table: category_translations
 */
#[Table(name: 'category_translations')]
class CategoryTranslation
{
    public const PRIMARY_KEY = 'id';
    public const SOURCE = 'category_translations';
    public const IS_AUTOINCREMENT = true;

    /**
     * Snapshot of original values for change tracking.
     * Managed via reflection by external services.
     *
     * @var array<string, mixed>
     */
    protected array $__snapshot = [
        'id' => null,
        'category_id' => null,
        'locale' => null,
        'title' => null,
        'description' => null,
        'meta_title' => null,
        'meta_description' => null
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
    #[Column(name: 'category_id', type: 'int')]
    #[ForeignKey(referencedTable: 'categories', referencedColumn: 'id', onUpdate: 'NO ACTION', onDelete: 'NO ACTION')]
    protected ?int $categoryId = null;

    /**
     * @var ?string
     */
    #[Column(name: 'locale', type: 'varchar', length: 5)]
    protected ?string $locale = null;

    /**
     * @var ?string
     */
    #[Column(name: 'title', type: 'varchar', length: 255)]
    protected ?string $title = null;

    /**
     * @var ?string
     */
    #[Column(name: 'description', type: 'text', nullable: true)]
    protected ?string $description = null;

    /**
     * @var ?string
     */
    #[Column(name: 'meta_title', type: 'varchar', length: 255, nullable: true)]
    protected ?string $metaTitle = null;

    /**
     * @var ?string
     */
    #[Column(name: 'meta_description', type: 'varchar', length: 500, nullable: true)]
    protected ?string $metaDescription = null;

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

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getMetaTitle(): ?string
    {
        return $this->metaTitle;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setCategoryId(?int $categoryId): self
    {
        $this->categoryId = $categoryId;
        return $this;
    }

    public function setLocale(?string $locale): self
    {
        $this->locale = $locale;
        return $this;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function setMetaTitle(?string $metaTitle): self
    {
        $this->metaTitle = $metaTitle;
        return $this;
    }

    public function setMetaDescription(?string $metaDescription): self
    {
        $this->metaDescription = $metaDescription;
        return $this;
    }
}
