<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Repository;

use Ecommerce\Catalog\Aggregate\Product\Command\Validation\ValidationException;
use Ecommerce\Catalog\Entity\Validation\ProductImageValidator;
use Ecommerce\Catalog\Entity\Validation\ProductValidator;
use Ecommerce\Catalog\Entity\Validation\ProductVariantPriceValidator;
use Ecommerce\Catalog\Entity\Validation\ProductVariantValidator;
use Ecommerce\EcommerceContext;
use JsonException;
use Throwable;
use Ecommerce\Catalog\Aggregate\Product\Aggregate\Product as ProductHandler;

/**
 * Validates Product aggregate entities before persistence.
 *
 * Validates all entities using their generated validators.
 * Called automatically by Repository before PersistAggregate.
 */
class ValidateProduct extends EcommerceContext
{
    /**
     * Validates all entities in the aggregate.
     *
     * @param ProductHandler $handler Aggregate handler
     * @throws ValidationException If validation fails
     * @throws JsonException
     * @throws Throwable
     */
    public function __invoke(ProductHandler $handler): void
    {
        if ($handler->isMarkedForDeletion()) {
            return;
        }

        $errors = [];
        $aggregate = $handler->getData();

        if (!empty($handler->getEntityData('product')['values'])) {
            $this->validateEntity($aggregate, ProductValidator::class, 'product', $errors);
        }

        $productImageCollection = $handler->getCollectionData('productImage');
        foreach ($aggregate->getProductImage() as $index => $productImage) {
            if (!empty($productImageCollection[$index]['values'])) {
                $this->validateEntity($productImage, ProductImageValidator::class, "productImage[{$index}]", $errors);
            }
        }
        $productVariantCollection = $handler->getCollectionData('productVariant');
        foreach ($aggregate->getProductVariant() as $index => $productVariant) {
            if (!empty($productVariantCollection[$index]['values'])) {
                $this->validateEntity(
                    $productVariant,
                    ProductVariantValidator::class,
                    "productVariant[{$index}]",
                    $errors
                );
            }
        }
        $productVariantPriceCollection = $handler->getCollectionData('productVariantPrice');
        foreach (array_merge([], ...array_map(fn(object $e) => $e->getProductVariantPrice(), $aggregate->getProductVariant())) as $index => $productVariantPrice) {
            if (!empty($productVariantPriceCollection[$index]['values'])) {
                $this->validateEntity(
                    $productVariantPrice,
                    ProductVariantPriceValidator::class,
                    "productVariantPrice[{$index}]",
                    $errors
                );
            }
        }

        if (!empty($errors)) {
            throw new ValidationException(json_encode($errors, JSON_THROW_ON_ERROR));
        }
    }

    /**
     * Validates a single entity against its validator.
     *
     * @param object $entity The entity to validate
     * @param class-string $validatorClass Validator class name
     * @param string $entityName Entity identifier for error grouping
     * @param array<string, mixed> $errors Collected errors (by reference)
     * @throws Throwable
     */
    protected function validateEntity(
        object $entity,
        string $validatorClass,
        string $entityName,
        array &$errors
    ): void {
        $validator = ($this->handle($validatorClass))();
        $result = $validator->validate($entity);

        if (!$result->isValid()) {
            $errors[$entityName] = $result->getErrors();
        }
    }
}
