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
use Ecommerce\Catalog\Model\Category\Query\CategoryByIds as QueryCategoryByIds;

/**
 * Query endpoint: GetCategoryByIds
 */
class GetCategoryByIdsHandler extends EcommerceContext
{
    private const BULK_WARN_ABOVE = 100;

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
        /** @var QueryCategoryByIds $categoryByIds */
        $categoryByIds = $this->payload();

        $logger = $this->resource()->logger();
        if ($logger !== null) {
            $count = count($categoryByIds->ids);
            if ($count > self::BULK_WARN_ABOVE) {
                $logger->warning('Bulk read {handler} received {count} keys (threshold {threshold}).', [
                    'handler' => self::class,
                    'count' => $count,
                    'threshold' => self::BULK_WARN_ABOVE,
                ]);
            }
        }

        /** @var array<int, array<string, mixed>> $result */
        $result = $this->handle(CategoryRepository::class)->getCategoryByIds($categoryByIds, false);

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

        $this->result()->setData(['@type' => 'categoryList', 'items' => $projected]);

        return ($this->transformer())($this->result());
    }
}
