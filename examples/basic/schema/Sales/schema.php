<?php

declare(strict_types=1);

return array (
  'connection' => '__test__',
  'tables' => 
  array (
    'addresses' => 
    array (
      'columns' => 
      array (
        0 => 
        array (
          'autoincrement' => true,
          'name' => 'id',
          'nullable' => false,
          'primary' => true,
          'type' => 'int',
        ),
        1 => 
        array (
          'length' => 255,
          'name' => 'street',
          'nullable' => false,
          'type' => 'varchar',
        ),
        2 => 
        array (
          'length' => 100,
          'name' => 'city',
          'nullable' => false,
          'type' => 'varchar',
        ),
        3 => 
        array (
          'length' => 20,
          'name' => 'postal_code',
          'nullable' => false,
          'type' => 'varchar',
        ),
        4 => 
        array (
          'length' => 2,
          'name' => 'country',
          'nullable' => false,
          'type' => 'varchar',
        ),
      ),
      'foreignKeys' => 
      array (
      ),
      'indexes' => 
      array (
        0 => 
        array (
          'columns' => 
          array (
            0 => 'id',
          ),
          'name' => 'PRIMARY',
          'type' => 'primary',
        ),
      ),
      'name' => 'addresses',
    ),
    'channel_hooks' => 
    array (
      'columns' => 
      array (
        0 => 
        array (
          'autoincrement' => true,
          'name' => 'id',
          'nullable' => false,
          'primary' => true,
          'type' => 'int',
        ),
        1 => 
        array (
          'name' => 'channel_id',
          'nullable' => false,
          'type' => 'int',
        ),
        2 => 
        array (
          'length' => 255,
          'name' => 'target_url',
          'nullable' => false,
          'type' => 'varchar',
        ),
        3 => 
        array (
          'length' => 64,
          'name' => 'signing_key',
          'nullable' => false,
          'type' => 'varchar',
        ),
      ),
      'foreignKeys' => 
      array (
        0 => 
        array (
          'column' => 'channel_id',
          'referencedColumn' => 'id',
          'referencedTable' => 'voucher_channels',
        ),
      ),
      'indexes' => 
      array (
        0 => 
        array (
          'columns' => 
          array (
            0 => 'id',
          ),
          'name' => 'PRIMARY',
          'type' => 'primary',
        ),
        1 => 
        array (
          'columns' => 
          array (
            0 => 'channel_id',
          ),
          'name' => 'uq_channel_hooks_channel_id',
          'type' => 'unique',
        ),
      ),
      'name' => 'channel_hooks',
    ),
    'customers' => 
    array (
      'columns' => 
      array (
        0 => 
        array (
          'autoincrement' => true,
          'name' => 'id',
          'nullable' => false,
          'primary' => true,
          'type' => 'int',
        ),
        1 => 
        array (
          'length' => 36,
          'name' => 'identifier',
          'nullable' => false,
          'type' => 'varchar',
        ),
        2 => 
        array (
          'length' => 255,
          'name' => 'email',
          'nullable' => false,
          'type' => 'varchar',
        ),
        3 => 
        array (
          'length' => 255,
          'name' => 'name',
          'nullable' => false,
          'type' => 'varchar',
        ),
        4 => 
        array (
          'length' => 50,
          'name' => 'phone',
          'nullable' => true,
          'type' => 'varchar',
        ),
        5 => 
        array (
          'name' => 'billing_address_id',
          'nullable' => true,
          'type' => 'int',
        ),
        6 => 
        array (
          'default' => 'CURRENT_TIMESTAMP',
          'name' => 'created_at',
          'nullable' => true,
          'type' => 'timestamp',
        ),
      ),
      'foreignKeys' => 
      array (
        0 => 
        array (
          'column' => 'billing_address_id',
          'referencedColumn' => 'id',
          'referencedTable' => 'addresses',
        ),
      ),
      'indexes' => 
      array (
        0 => 
        array (
          'columns' => 
          array (
            0 => 'id',
          ),
          'name' => 'PRIMARY',
          'type' => 'primary',
        ),
        1 => 
        array (
          'columns' => 
          array (
            0 => 'identifier',
          ),
          'name' => 'identifier',
          'type' => 'unique',
        ),
        2 => 
        array (
          'columns' => 
          array (
            0 => 'email',
          ),
          'name' => 'email',
          'type' => 'unique',
        ),
        3 => 
        array (
          'columns' => 
          array (
            0 => 'billing_address_id',
          ),
          'name' => 'idx_customers_billing_address_id',
          'type' => 'index',
        ),
      ),
      'name' => 'customers',
    ),
    'invoice_lines' => 
    array (
      'columns' => 
      array (
        0 => 
        array (
          'autoincrement' => true,
          'name' => 'id',
          'nullable' => false,
          'primary' => true,
          'type' => 'int',
        ),
        1 => 
        array (
          'length' => 36,
          'name' => 'identifier',
          'nullable' => false,
          'type' => 'varchar',
        ),
        2 => 
        array (
          'name' => 'invoice_id',
          'nullable' => false,
          'type' => 'int',
        ),
        3 => 
        array (
          'name' => 'position',
          'nullable' => false,
          'type' => 'int',
        ),
        4 => 
        array (
          'length' => 500,
          'name' => 'description',
          'nullable' => false,
          'type' => 'varchar',
        ),
        5 => 
        array (
          'name' => 'quantity',
          'nullable' => false,
          'precision' => 10,
          'scale' => 3,
          'type' => 'decimal',
        ),
        6 => 
        array (
          'length' => 20,
          'name' => 'unit',
          'nullable' => false,
          'type' => 'varchar',
        ),
        7 => 
        array (
          'name' => 'unit_price',
          'nullable' => false,
          'precision' => 12,
          'scale' => 4,
          'type' => 'decimal',
        ),
        8 => 
        array (
          'name' => 'discount_percent',
          'nullable' => true,
          'precision' => 5,
          'scale' => 2,
          'type' => 'decimal',
        ),
        9 => 
        array (
          'name' => 'line_total',
          'nullable' => false,
          'precision' => 12,
          'scale' => 2,
          'type' => 'decimal',
        ),
        10 => 
        array (
          'name' => 'tax_included',
          'nullable' => false,
          'type' => 'tinyint',
        ),
        11 => 
        array (
          'default' => 'CURRENT_TIMESTAMP',
          'name' => 'created_at',
          'nullable' => true,
          'type' => 'timestamp',
        ),
      ),
      'foreignKeys' => 
      array (
        0 => 
        array (
          'column' => 'invoice_id',
          'referencedColumn' => 'id',
          'referencedTable' => 'invoices',
        ),
      ),
      'indexes' => 
      array (
        0 => 
        array (
          'columns' => 
          array (
            0 => 'id',
          ),
          'name' => 'PRIMARY',
          'type' => 'primary',
        ),
        1 => 
        array (
          'columns' => 
          array (
            0 => 'identifier',
          ),
          'name' => 'identifier',
          'type' => 'unique',
        ),
        2 => 
        array (
          'columns' => 
          array (
            0 => 'invoice_id',
          ),
          'name' => 'idx_invoice_lines_invoice_id',
          'type' => 'index',
        ),
      ),
      'name' => 'invoice_lines',
    ),
    'invoices' => 
    array (
      'columns' => 
      array (
        0 => 
        array (
          'autoincrement' => true,
          'name' => 'id',
          'nullable' => false,
          'primary' => true,
          'type' => 'int',
        ),
        1 => 
        array (
          'length' => 36,
          'name' => 'tenant_key',
          'nullable' => false,
          'type' => 'varchar',
        ),
        2 => 
        array (
          'length' => 36,
          'name' => 'identifier',
          'nullable' => false,
          'type' => 'varchar',
        ),
        3 => 
        array (
          'length' => 50,
          'name' => 'invoice_number',
          'nullable' => false,
          'type' => 'varchar',
        ),
        4 => 
        array (
          'length' => 50,
          'name' => 'order_number',
          'nullable' => false,
          'type' => 'varchar',
        ),
        5 => 
        array (
          'length' => 36,
          'name' => 'customer_identifier',
          'nullable' => false,
          'type' => 'varchar',
        ),
        6 => 
        array (
          'enumValues' => 
          array (
            0 => 'draft',
            1 => 'sent',
            2 => 'paid',
            3 => 'overdue',
            4 => 'cancelled',
            5 => 'refunded',
          ),
          'name' => 'status',
          'nullable' => false,
          'type' => 'enum',
        ),
        7 => 
        array (
          'enumValues' => 
          array (
            0 => 'bank_transfer',
            1 => 'credit_card',
            2 => 'paypal',
            3 => 'invoice',
          ),
          'name' => 'payment_method',
          'nullable' => true,
          'type' => 'enum',
        ),
        8 => 
        array (
          'name' => 'total_net',
          'nullable' => false,
          'precision' => 12,
          'scale' => 2,
          'type' => 'decimal',
        ),
        9 => 
        array (
          'name' => 'tax_rate',
          'nullable' => false,
          'precision' => 5,
          'scale' => 2,
          'type' => 'decimal',
        ),
        10 => 
        array (
          'name' => 'total_gross',
          'nullable' => false,
          'precision' => 12,
          'scale' => 2,
          'type' => 'decimal',
        ),
        11 => 
        array (
          'length' => 3,
          'name' => 'currency',
          'nullable' => false,
          'type' => 'varchar',
        ),
        12 => 
        array (
          'name' => 'note',
          'nullable' => true,
          'type' => 'text',
        ),
        13 => 
        array (
          'name' => 'issued_at',
          'nullable' => true,
          'type' => 'datetime',
        ),
        14 => 
        array (
          'name' => 'due_at',
          'nullable' => true,
          'type' => 'datetime',
        ),
        15 => 
        array (
          'name' => 'paid_at',
          'nullable' => true,
          'type' => 'datetime',
        ),
        16 => 
        array (
          'default' => 'CURRENT_TIMESTAMP',
          'name' => 'created_at',
          'nullable' => true,
          'type' => 'timestamp',
        ),
      ),
      'foreignKeys' => 
      array (
      ),
      'indexes' => 
      array (
        0 => 
        array (
          'columns' => 
          array (
            0 => 'id',
          ),
          'name' => 'PRIMARY',
          'type' => 'primary',
        ),
        1 => 
        array (
          'columns' => 
          array (
            0 => 'identifier',
          ),
          'name' => 'identifier',
          'type' => 'unique',
        ),
        2 => 
        array (
          'columns' => 
          array (
            0 => 'invoice_number',
          ),
          'name' => 'invoice_number',
          'type' => 'unique',
        ),
      ),
      'name' => 'invoices',
    ),
    'item_discounts' => 
    array (
      'columns' => 
      array (
        0 => 
        array (
          'autoincrement' => true,
          'name' => 'id',
          'nullable' => false,
          'primary' => true,
          'type' => 'int',
        ),
        1 => 
        array (
          'name' => 'item_id',
          'nullable' => false,
          'type' => 'int',
        ),
        2 => 
        array (
          'length' => 50,
          'name' => 'discount_code',
          'nullable' => false,
          'type' => 'varchar',
        ),
        3 => 
        array (
          'name' => 'amount',
          'nullable' => false,
          'precision' => 10,
          'scale' => 2,
          'type' => 'decimal',
        ),
      ),
      'foreignKeys' => 
      array (
        0 => 
        array (
          'column' => 'item_id',
          'referencedColumn' => 'id',
          'referencedTable' => 'order_items',
        ),
      ),
      'indexes' => 
      array (
        0 => 
        array (
          'columns' => 
          array (
            0 => 'id',
          ),
          'name' => 'PRIMARY',
          'type' => 'primary',
        ),
        1 => 
        array (
          'columns' => 
          array (
            0 => 'item_id',
          ),
          'name' => 'idx_item_discounts_item_id',
          'type' => 'index',
        ),
      ),
      'name' => 'item_discounts',
    ),
    'order_items' => 
    array (
      'columns' => 
      array (
        0 => 
        array (
          'autoincrement' => true,
          'name' => 'id',
          'nullable' => false,
          'primary' => true,
          'type' => 'int',
        ),
        1 => 
        array (
          'length' => 36,
          'name' => 'identifier',
          'nullable' => false,
          'type' => 'varchar',
        ),
        2 => 
        array (
          'name' => 'order_id',
          'nullable' => false,
          'type' => 'int',
        ),
        3 => 
        array (
          'length' => 36,
          'name' => 'tenant_key',
          'nullable' => false,
          'type' => 'varchar',
        ),
        4 => 
        array (
          'length' => 36,
          'name' => 'product_identifier',
          'nullable' => false,
          'type' => 'varchar',
        ),
        5 => 
        array (
          'name' => 'quantity',
          'nullable' => false,
          'type' => 'int',
          'unsigned' => true,
        ),
        6 => 
        array (
          'name' => 'unit_price',
          'nullable' => false,
          'precision' => 10,
          'scale' => 2,
          'type' => 'decimal',
        ),
        7 => 
        array (
          'name' => 'subtotal',
          'nullable' => false,
          'precision' => 10,
          'scale' => 2,
          'type' => 'decimal',
        ),
      ),
      'foreignKeys' => 
      array (
        0 => 
        array (
          'column' => 'order_id',
          'referencedColumn' => 'id',
          'referencedTable' => 'orders',
        ),
      ),
      'indexes' => 
      array (
        0 => 
        array (
          'columns' => 
          array (
            0 => 'id',
          ),
          'name' => 'PRIMARY',
          'type' => 'primary',
        ),
        1 => 
        array (
          'columns' => 
          array (
            0 => 'identifier',
          ),
          'name' => 'identifier',
          'type' => 'unique',
        ),
        2 => 
        array (
          'columns' => 
          array (
            0 => 'order_id',
          ),
          'name' => 'idx_order_items_order_id',
          'type' => 'index',
        ),
      ),
      'name' => 'order_items',
    ),
    'orders' => 
    array (
      'columns' => 
      array (
        0 => 
        array (
          'autoincrement' => true,
          'name' => 'id',
          'nullable' => false,
          'primary' => true,
          'type' => 'int',
        ),
        1 => 
        array (
          'length' => 36,
          'name' => 'tenant_key',
          'nullable' => false,
          'type' => 'varchar',
        ),
        2 => 
        array (
          'length' => 50,
          'name' => 'order_number',
          'nullable' => false,
          'type' => 'varchar',
        ),
        3 => 
        array (
          'name' => 'customer_id',
          'nullable' => false,
          'type' => 'int',
        ),
        4 => 
        array (
          'name' => 'total_amount',
          'nullable' => false,
          'precision' => 10,
          'scale' => 2,
          'type' => 'decimal',
        ),
        5 => 
        array (
          'enumValues' => 
          array (
            0 => 'pending',
            1 => 'confirmed',
            2 => 'shipped',
            3 => 'delivered',
            4 => 'cancelled',
          ),
          'name' => 'status',
          'nullable' => false,
          'type' => 'enum',
        ),
        6 => 
        array (
          'default' => 'CURRENT_TIMESTAMP',
          'name' => 'created_at',
          'nullable' => true,
          'type' => 'timestamp',
        ),
      ),
      'foreignKeys' => 
      array (
        0 => 
        array (
          'column' => 'customer_id',
          'referencedColumn' => 'id',
          'referencedTable' => 'customers',
        ),
      ),
      'indexes' => 
      array (
        0 => 
        array (
          'columns' => 
          array (
            0 => 'id',
          ),
          'name' => 'PRIMARY',
          'type' => 'primary',
        ),
        1 => 
        array (
          'columns' => 
          array (
            0 => 'order_number',
          ),
          'name' => 'order_number',
          'type' => 'unique',
        ),
        2 => 
        array (
          'columns' => 
          array (
            0 => 'customer_id',
          ),
          'name' => 'idx_orders_customer_id',
          'type' => 'index',
        ),
      ),
      'name' => 'orders',
    ),
    'price_tiers' => 
    array (
      'columns' => 
      array (
        0 => 
        array (
          'autoincrement' => true,
          'name' => 'id',
          'nullable' => false,
          'primary' => true,
          'type' => 'int',
        ),
        1 => 
        array (
          'length' => 36,
          'name' => 'tenant_key',
          'nullable' => false,
          'type' => 'varchar',
        ),
        2 => 
        array (
          'length' => 36,
          'name' => 'identifier',
          'nullable' => false,
          'type' => 'varchar',
        ),
        3 => 
        array (
          'length' => 50,
          'name' => 'tier_code',
          'nullable' => false,
          'type' => 'varchar',
        ),
        4 => 
        array (
          'name' => 'min_quantity',
          'nullable' => false,
          'type' => 'int',
        ),
        5 => 
        array (
          'name' => 'unit_price',
          'nullable' => false,
          'precision' => 12,
          'scale' => 2,
          'type' => 'decimal',
        ),
        6 => 
        array (
          'default' => 'CURRENT_TIMESTAMP',
          'name' => 'created_at',
          'nullable' => true,
          'type' => 'timestamp',
        ),
      ),
      'foreignKeys' => 
      array (
      ),
      'indexes' => 
      array (
        0 => 
        array (
          'columns' => 
          array (
            0 => 'id',
          ),
          'name' => 'PRIMARY',
          'type' => 'primary',
        ),
        1 => 
        array (
          'columns' => 
          array (
            0 => 'identifier',
          ),
          'name' => 'identifier',
          'type' => 'unique',
        ),
        2 => 
        array (
          'columns' => 
          array (
            0 => 'tier_code',
          ),
          'name' => 'tier_code',
          'type' => 'unique',
        ),
      ),
      'name' => 'price_tiers',
    ),
    'voucher_channels' => 
    array (
      'columns' => 
      array (
        0 => 
        array (
          'autoincrement' => true,
          'name' => 'id',
          'nullable' => false,
          'primary' => true,
          'type' => 'int',
        ),
        1 => 
        array (
          'name' => 'voucher_id',
          'nullable' => false,
          'type' => 'int',
        ),
        2 => 
        array (
          'length' => 36,
          'name' => 'identifier',
          'nullable' => false,
          'type' => 'varchar',
        ),
        3 => 
        array (
          'length' => 100,
          'name' => 'label',
          'nullable' => false,
          'type' => 'varchar',
        ),
        4 => 
        array (
          'length' => 64,
          'name' => 'access_key',
          'nullable' => false,
          'type' => 'varchar',
        ),
      ),
      'foreignKeys' => 
      array (
        0 => 
        array (
          'column' => 'voucher_id',
          'referencedColumn' => 'id',
          'referencedTable' => 'vouchers',
        ),
      ),
      'indexes' => 
      array (
        0 => 
        array (
          'columns' => 
          array (
            0 => 'id',
          ),
          'name' => 'PRIMARY',
          'type' => 'primary',
        ),
        1 => 
        array (
          'columns' => 
          array (
            0 => 'voucher_id',
          ),
          'name' => 'uq_voucher_channels_voucher_id',
          'type' => 'unique',
        ),
        2 => 
        array (
          'columns' => 
          array (
            0 => 'identifier',
          ),
          'name' => 'uq_voucher_channels_identifier',
          'type' => 'unique',
        ),
      ),
      'name' => 'voucher_channels',
    ),
    'vouchers' => 
    array (
      'columns' => 
      array (
        0 => 
        array (
          'autoincrement' => true,
          'name' => 'id',
          'nullable' => false,
          'primary' => true,
          'type' => 'int',
        ),
        1 => 
        array (
          'length' => 36,
          'name' => 'scope_key',
          'nullable' => false,
          'type' => 'varchar',
        ),
        2 => 
        array (
          'length' => 50,
          'name' => 'voucher_code',
          'nullable' => false,
          'type' => 'varchar',
        ),
        3 => 
        array (
          'name' => 'amount',
          'nullable' => false,
          'precision' => 10,
          'scale' => 2,
          'type' => 'decimal',
        ),
        4 => 
        array (
          'length' => 255,
          'name' => 'note',
          'nullable' => true,
          'type' => 'varchar',
        ),
        5 => 
        array (
          'length' => 64,
          'name' => 'secret_digest',
          'nullable' => false,
          'type' => 'varchar',
        ),
        6 => 
        array (
          'length' => 36,
          'name' => 'issued_by',
          'nullable' => false,
          'type' => 'varchar',
        ),
        7 => 
        array (
          'length' => 36,
          'name' => 'issuer_id',
          'nullable' => false,
          'type' => 'varchar',
        ),
        8 => 
        array (
          'default' => 'CURRENT_TIMESTAMP',
          'name' => 'created_at',
          'nullable' => true,
          'type' => 'timestamp',
        ),
      ),
      'foreignKeys' => 
      array (
      ),
      'indexes' => 
      array (
        0 => 
        array (
          'columns' => 
          array (
            0 => 'id',
          ),
          'name' => 'PRIMARY',
          'type' => 'primary',
        ),
        1 => 
        array (
          'columns' => 
          array (
            0 => 'voucher_code',
          ),
          'name' => 'uq_vouchers_voucher_code',
          'type' => 'unique',
        ),
      ),
      'name' => 'vouchers',
    ),
  ),
);
