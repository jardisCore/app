<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Query\Handler;

use Ecommerce\Catalog\Aggregate\Category\Query\Response\CategoryResponse;
use Ecommerce\Catalog\Aggregate\Category\Repository\CategoryRepository;
use Ecommerce\EcommerceContext;
use Ecommerce\Response\DomainResponseTransformer;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Data\FieldMapper;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;
use Ecommerce\Catalog\Aggregate\Category\Query\CategoryByIdentifier as QueryCategoryByIdentifier;

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
                'id' => 'id',
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

        /** @var array<int, CategoryResponse> $projected */
        $projected = array_map(
            function (array $row) use ($mapEntity): CategoryResponse {
                $mapped = $this->handle(FieldMapper::class)->fromAggregate($row, $mapEntity, 'category');
                $categoryResponse = new CategoryResponse();
                $this->handle(Hydration::class)->hydrateAggregate($categoryResponse, $mapped);
                return $categoryResponse;
            },
            $result,
        );

        $this->result()->setData(['category' => $projected[0] ?? null]);

        return $this->handle(DomainResponseTransformer::class)->transform($this->result());
    }
}
