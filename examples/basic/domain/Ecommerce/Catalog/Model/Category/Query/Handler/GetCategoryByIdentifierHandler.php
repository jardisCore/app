<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Category\Query\Handler;

use Ecommerce\Catalog\Model\Category\Query\Response\CategoryResponse;
use Ecommerce\Catalog\Model\Category\Repository\CategoryRepository;
use Ecommerce\EcommerceContext;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Data\FieldMapper;
use JardisSupport\Data\Handler\ProjectWire;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;
use Ecommerce\Catalog\Model\Category\Query\CategoryByIdentifier as QueryCategoryByIdentifier;

/**
 * Query endpoint: GetCategoryByIdentifier
 */
class GetCategoryByIdentifierHandler extends EcommerceContext
{
    /**
     * Executes the Query endpoint operation.
     *
     * @return DomainResponseInterface
     * @throws Throwable
     * @throws Exception
     * @throws ReflectionException
     */
    public function __invoke(): DomainResponseInterface
    {
        /** @var QueryCategoryByIdentifier $categoryByIdentifier */
        $categoryByIdentifier = $this->payload();

        /** @var array<int, array<string, mixed>> $result */
        $result = $this->handle(CategoryRepository::class)->getCategoryByIdentifier($categoryByIdentifier, false);

        /** @var array<string, array<string, string>> $readMaps */
        $readMaps = [
            'category' => [
                'identifier' => 'identifier',
                'slug' => 'slug',
                'parentIdentifier' => 'parent_identifier',
                'categoryName' => 'name',
                'description' => 'description',
                'metaTitle' => 'meta_title',
                'metaDescription' => 'meta_description',
                'isActive' => 'is_active',
                'isVisible' => 'is_visible',
                'sortOrder' => 'sort_order',
                'productCount' => 'product_count',
                'createdAt' => 'created_at',
                'updatedAt' => 'updated_at',
            ],
            'categoryTranslation' => [
                'id' => 'id',
                'locale' => 'locale',
                'title' => 'title',
                'description' => 'description',
                'metaTitle' => 'meta_title',
                'metaDescription' => 'meta_description',
            ],
        ];
        $mapEntity = static fn(string $entity): array => $readMaps[$entity] ?? [];

        $wireTypes = [
            'createdAt' => 'date-time',
            'updatedAt' => 'date-time',
        ];

        $projectWire = $this->handle(ProjectWire::class);
        $fieldMapper = $this->handle(FieldMapper::class);
        $hydration = $this->handle(Hydration::class);

        /** @var array<int, CategoryResponse> $projected */
        $projected = array_map(
            function (array $row) use (
                $mapEntity,
                $fieldMapper,
                $hydration,
                $projectWire,
                $wireTypes
            ): CategoryResponse {
                $mapped = $fieldMapper->fromAggregate($row, $mapEntity, 'category');
                $mapped = $projectWire($mapped, $wireTypes);
                $categoryResponse = new CategoryResponse();
                $hydration->hydrateAggregate($categoryResponse, $mapped);
                return $categoryResponse;
            },
            $result,
        );

        $found = $projected[0] ?? null;
        $this->result()->setData($found === null ? [] : ['@type' => 'category'] + get_object_vars($found));

        return ($this->transformer())($this->result());
    }
}
