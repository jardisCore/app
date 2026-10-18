<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Command\Handler\Action;

use Ecommerce\Catalog\FieldMap;
use Ecommerce\EcommerceContext;
use InvalidArgumentException;
use JardisSupport\Data\FieldMapper;
use Throwable;

/**
 * Action: BuildUpdateProductExpected
 */
class BuildUpdateProductExpected extends EcommerceContext
{
    /**
     * Maps the read basis the caller holds to columns.
     *
     * @param array<string, mixed> $expected Outer field name => value the caller read
     * @return array<string, mixed> Column => value
     * @throws InvalidArgumentException If a field is not part of the root FieldMap
     * @throws Throwable
     */
    public function __invoke(array $expected): array
    {
        $fieldMap = $this->handle(FieldMap::class);
        $columns = $fieldMap->productsColumns();

        $unknown = array_keys(array_diff_key($expected, $columns));
        if ($unknown !== []) {
            throw new InvalidArgumentException(
                'Unknown expected field(s) for Product: ' . implode(', ', $unknown)
            );
        }

        return $this->handle(FieldMapper::class)->toColumns($expected, $columns);
    }
}
