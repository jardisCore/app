<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Data;

/**
 * InvoicePaymentMethod enum
 *
 * Generated from database ENUM type
 */
enum InvoicePaymentMethod: string
{
    case BankTransfer = 'bank_transfer';
    case CreditCard = 'credit_card';
    case Paypal = 'paypal';
    case Invoice = 'invoice';
}
