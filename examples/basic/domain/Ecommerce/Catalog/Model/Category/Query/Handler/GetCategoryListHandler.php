<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Category\Query\Handler;

use Ecommerce\Catalog\Model\Category\Query\CategoryListFilter;
use Ecommerce\Catalog\Model\Category\Repository\QueryCategoryList;
use Ecommerce\EcommerceContext;
use JardisSupport\Data\Handler\ProjectWire;
use JardisSupport\Response\Data\ReadResult;
use Throwable;

/**
 * Handler for CategoryList list query.
 *
 * Generated code - do not modify directly.
 */
class GetCategoryListHandler extends EcommerceContext
{
    /**
     * @return ReadResult<array<string, mixed>>
     * @throws Throwable
     */
    public function __invoke(): ReadResult
    {
        /** @var CategoryListFilter $filter */
        $filter = $this->payload();

        /** @var list<array<string, mixed>> $rows */
        $rows = $this->handle(QueryCategoryList::class)($filter);
        $total = (int) ($rows[0]['total_count'] ?? 0);

        $wireTypes = [
            'createdAt' => 'date-time',
            'updatedAt' => 'date-time',
        ];
        $projectWire = $this->handle(ProjectWire::class);

        /** @var list<array<string, mixed>> $items */
        $items = array_map(
            fn(array $row) => $projectWire(array_diff_key($row, ['total_count' => true]), $wireTypes),
            $rows
        );

        return ReadResult::ofList($items, $total);
    }
}
