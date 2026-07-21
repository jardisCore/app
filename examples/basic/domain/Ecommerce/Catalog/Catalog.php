<?php

declare(strict_types=1);

namespace Ecommerce\Catalog;

use Throwable;
use Ecommerce\EcommerceContext;
use Ecommerce\Catalog\Aggregate\Category\CategoryRead;
use Ecommerce\Catalog\Aggregate\Product\ProductRead;
use Ecommerce\Catalog\Process\CatalogProcess;

/**
 * Catalog Bounded Context.
 *
 * Public API (the "Außentür", G2): one read accessor per aggregate
 * plus process(). Aggregate writes are not exposed here.
 *
 * Usage:
 *   $bc->category()->{useCase}(...)
 *   $bc->product()->{useCase}(...)
 *   $bc->process()->{process}(...)
 */
class Catalog extends EcommerceContext
{
    /**
     * Returns the Category aggregate read facade.
     *
     * @return CategoryRead
     * @throws Throwable
     */
    public function category(): CategoryRead
    {
        return $this->handle(CategoryRead::class);
    }

    /**
     * Returns the Product aggregate read facade.
     *
     * @return ProductRead
     * @throws Throwable
     */
    public function product(): ProductRead
    {
        return $this->handle(ProductRead::class);
    }

    /**
     * Returns the process facade bundling this bounded context's processes.
     *
     * @return CatalogProcess
     * @throws Throwable
     */
    public function process(): CatalogProcess
    {
        return $this->handle(CatalogProcess::class);
    }
}
