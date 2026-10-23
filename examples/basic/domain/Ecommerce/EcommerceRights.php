<?php

declare(strict_types=1);

namespace Ecommerce;

use JardisSupport\Contract\Auth\RightsCatalogInterface;

/**
 * EcommerceRights: the rights catalog of the Ecommerce domain (V3).
 *
 * Each right is bound to the endpoint it guards; the class constants are the one
 * spelling of a right. Hermetic - derived from the domain model, data only.
 */
final class EcommerceRights implements RightsCatalogInterface
{
    public const CATALOG_PROCESS__UPDATEPRODUCTINCATALOG = 'catalog.process:updateProductInCatalog';
    public const CATALOG_CATEGORY__READ = 'catalog.category:read';
    public const CATALOG_PRODUCT__READ = 'catalog.product:read';
    public const FULFILLMENT_SHIPMENT__READ = 'fulfillment.shipment:read';
    public const FULFILLMENT_SHIPMENT__SHIPMENTBYORDERNUMBER = 'fulfillment.shipment:shipmentByOrderNumber';
    public const SALES_PROCESS__BATCHORDERFULFILMENT = 'sales.process:batchOrderFulfilment';
    public const SALES_PROCESS__CROSSBCREADDEMO = 'sales.process:crossBcReadDemo';
    public const SALES_PROCESS__CROSSBCSERVICEDEMO = 'sales.process:crossBcServiceDemo';
    public const SALES_PROCESS__EXTERNALCALLSERVICEDEMO = 'sales.process:externalCallServiceDemo';
    public const SALES_PROCESS__ORDERBULKQUOTE = 'sales.process:orderBulkQuote';
    public const SALES_PROCESS__ORDERINVOICEBATCH = 'sales.process:orderInvoiceBatch';
    public const SALES_PROCESS__ORDERINVOICEUNIONALERT = 'sales.process:orderInvoiceUnionAlert';
    public const SALES_PROCESS__ORDERLIMITAFTERPLACE = 'sales.process:orderLimitAfterPlace';
    public const SALES_PROCESS__RULEGUARDEDORDERINTAKE = 'sales.process:ruleGuardedOrderIntake';
    public const SALES_PROCESS__VOUCHERREDEEMCHECK = 'sales.process:voucherRedeemCheck';
    public const SALES_INVOICE__READ = 'sales.invoice:read';
    public const SALES_INVOICE__INVOICESOFACTORTENANT = 'sales.invoice:invoicesOfActorTenant';
    public const SALES_ORDER__READ = 'sales.order:read';
    public const SALES_ORDER__ORDERSOFACTOR = 'sales.order:ordersOfActor';
    public const SALES_ORDER__UPDATEORDER = 'sales.order:updateOrder';
    public const SALES_ORDER__UPDATEORDERITEM = 'sales.order:updateOrderItem';
    public const SALES_VOUCHER__READ = 'sales.voucher:read';
    public const SALES_VOUCHER__UPDATEVOUCHER = 'sales.voucher:updateVoucher';

    /**
     * All rights of the domain in endpoint order; keys are unique.
     *
     * @return array<string, string> right => endpoint
     */
    public function rights(): array
    {
        return [
            self::CATALOG_PROCESS__UPDATEPRODUCTINCATALOG => 'POST /catalog/updateProductInCatalog',
            self::CATALOG_CATEGORY__READ => 'GET /catalog/categories/queries/byIdentifiers',
            self::CATALOG_PRODUCT__READ => 'GET /catalog/products/queries/byIdentifiers',
            self::FULFILLMENT_SHIPMENT__READ => 'GET /fulfillment/shipments/queries/byIdentifiers',
            self::FULFILLMENT_SHIPMENT__SHIPMENTBYORDERNUMBER =>
                'GET /fulfillment/shipments/queries/shipmentByOrderNumber',
            self::SALES_PROCESS__BATCHORDERFULFILMENT => 'POST /sales/batchOrderFulfilment',
            self::SALES_PROCESS__CROSSBCREADDEMO => 'POST /sales/crossBcReadDemo',
            self::SALES_PROCESS__CROSSBCSERVICEDEMO => 'POST /sales/crossBcServiceDemo',
            self::SALES_PROCESS__EXTERNALCALLSERVICEDEMO => 'POST /sales/externalCallServiceDemo',
            self::SALES_PROCESS__ORDERBULKQUOTE => 'POST /sales/orderBulkQuote',
            self::SALES_PROCESS__ORDERINVOICEBATCH => 'POST /sales/orderInvoiceBatch',
            self::SALES_PROCESS__ORDERINVOICEUNIONALERT => 'POST /sales/orderInvoiceUnionAlert',
            self::SALES_PROCESS__ORDERLIMITAFTERPLACE => 'POST /sales/orderLimitAfterPlace',
            self::SALES_PROCESS__RULEGUARDEDORDERINTAKE => 'POST /sales/ruleGuardedOrderIntake',
            self::SALES_PROCESS__VOUCHERREDEEMCHECK => 'POST /sales/voucherRedeemCheck',
            self::SALES_INVOICE__READ => 'GET /sales/invoices/queries/byIdentifiers',
            self::SALES_INVOICE__INVOICESOFACTORTENANT => 'GET /sales/invoices/queries/invoicesOfActorTenant',
            self::SALES_ORDER__READ => 'GET /sales/orders/queries/byOrderNumbers',
            self::SALES_ORDER__ORDERSOFACTOR => 'GET /sales/orders/queries/ordersOfActor',
            self::SALES_ORDER__UPDATEORDER => 'PUT /sales/orders/{orderNumber}',
            self::SALES_ORDER__UPDATEORDERITEM => 'PUT /sales/orders/{orderNumber}/orderItems/{orderItemIdentifier}',
            self::SALES_VOUCHER__READ => 'GET /sales/vouchers/queries/byVoucherCodes',
            self::SALES_VOUCHER__UPDATEVOUCHER => 'PUT /sales/vouchers/{voucherCode}',
        ];
    }
}
