<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Repository;

use Ecommerce\Catalog\Entity\Validation\ProductImageValidator;
use Ecommerce\Catalog\Entity\Validation\ProductValidator;
use Ecommerce\Catalog\Entity\Validation\ProductVariantPriceValidator;
use Ecommerce\Catalog\Entity\Validation\ProductVariantValidator;
use Ecommerce\Catalog\Model\Product\Command\Validation\ValidationException;
use Ecommerce\EcommerceContext;
use JardisSupport\Validation\CompositeFieldValidator;
use Throwable;
use Ecommerce\Catalog\Model\Product\Aggregate\Product as ProductHandler;

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
     * @throws Throwable
     */
    public function __invoke(ProductHandler $handler): void
    {
        if ($handler->isMarkedForDeletion()) {
            return;
        }

        $errors = [];
        $kinds = [];
        $aggregate = $handler->getData();

        if (!empty($handler->getEntityData('product')['values'])) {
            $this->validateEntity($aggregate, ProductValidator::class, 'product', $errors, $kinds);
        }

        $productImageCollection = $handler->getCollectionData('productImage');
        foreach ($aggregate->getProductImage() as $index => $productImage) {
            if (!empty($productImageCollection[$index]['values'])) {
                $this->validateEntity(
                    $productImage,
                    ProductImageValidator::class,
                    "productImage[{$index}]",
                    $errors,
                    $kinds
                );
            }
        }
        $productVariantCollection = $handler->getCollectionData('productVariant');
        foreach ($aggregate->getProductVariant() as $index => $productVariant) {
            if (!empty($productVariantCollection[$index]['values'])) {
                $this->validateEntity(
                    $productVariant,
                    ProductVariantValidator::class,
                    "productVariant[{$index}]",
                    $errors,
                    $kinds
                );
            }
        }
        $productVariantPriceCollection = $handler->getCollectionData('productVariantPrice');
        $productVariantPricePosition = 0;
        foreach ($aggregate->getProductVariant() as $productVariantIndex => $productVariant) {
            foreach ($productVariant->getProductVariantPrice() as $productVariantPriceIndex => $productVariantPrice) {
                if (!empty($productVariantPriceCollection[$productVariantPricePosition]['values'])) {
                    $this->validateEntity(
                        $productVariantPrice,
                        ProductVariantPriceValidator::class,
                        "productVariant[{$productVariantIndex}].productVariantPrice[{$productVariantPriceIndex}]",
                        $errors,
                        $kinds
                    );
                }
                $productVariantPricePosition++;
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors, $kinds, root: 'product');
        }
    }

    /**
     * Validates a single entity against its validator.
     *
     * @param object $entity The entity to validate
     * @param class-string $validatorClass Validator class name
     * @param string $entityName Entity identifier for error grouping
     * @param array<string, mixed> $errors Collected errors (by reference)
     * @param array<string, mixed> $kinds Collected validator kinds, same shape (by reference)
     * @throws Throwable
     */
    protected function validateEntity(
        object $entity,
        string $validatorClass,
        string $entityName,
        array &$errors,
        array &$kinds
    ): void {
        $validatorInstance = $this->handle($validatorClass);
        /** @var callable(): CompositeFieldValidator $validatorInstance */
        $validator = $validatorInstance();
        $result = $validator->validate($entity);

        if (!$result->isValid()) {
            $errors[$entityName] = $result->getErrors();
            $kinds[$entityName] = $result->getKinds();
        }
    }
}
