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
          'phpType' => 'int',
          'primary' => true,
          'type' => 'int',
        ),
        1 => 
        array (
          'length' => 255,
          'name' => 'street',
          'nullable' => false,
          'phpType' => 'string',
          'type' => 'varchar',
        ),
        2 => 
        array (
          'length' => 100,
          'name' => 'city',
          'nullable' => false,
          'phpType' => 'string',
          'type' => 'varchar',
        ),
        3 => 
        array (
          'length' => 20,
          'name' => 'postal_code',
          'nullable' => false,
          'phpType' => 'string',
          'type' => 'varchar',
        ),
        4 => 
        array (
          'length' => 2,
          'name' => 'country',
          'nullable' => false,
          'phpType' => 'string',
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
    'customers' => 
    array (
      'columns' => 
      array (
        0 => 
        array (
          'autoincrement' => true,
          'name' => 'id',
          'nullable' => false,
          'phpType' => 'int',
          'primary' => true,
          'type' => 'int',
        ),
        1 => 
        array (
          'length' => 36,
          'name' => 'identifier',
          'nullable' => false,
          'phpType' => 'string',
          'type' => 'varchar',
        ),
        2 => 
        array (
          'length' => 255,
          'name' => 'email',
          'nullable' => false,
          'phpType' => 'string',
          'type' => 'varchar',
        ),
        3 => 
        array (
          'length' => 255,
          'name' => 'name',
          'nullable' => false,
          'phpType' => 'string',
          'type' => 'varchar',
        ),
        4 => 
        array (
          'length' => 50,
          'name' => 'phone',
          'nullable' => true,
          'phpType' => 'string',
          'type' => 'varchar',
        ),
        5 => 
        array (
          'name' => 'billing_address_id',
          'nullable' => true,
          'phpType' => 'int',
          'type' => 'int',
        ),
        6 => 
        array (
          'default' => 'CURRENT_TIMESTAMP',
          'name' => 'created_at',
          'nullable' => true,
          'phpType' => 'datetime',
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
          'phpType' => 'int',
          'primary' => true,
          'type' => 'int',
        ),
        1 => 
        array (
          'length' => 36,
          'name' => 'identifier',
          'nullable' => false,
          'phpType' => 'string',
          'type' => 'varchar',
        ),
        2 => 
        array (
          'name' => 'invoice_id',
          'nullable' => false,
          'phpType' => 'int',
          'type' => 'int',
        ),
        3 => 
        array (
          'name' => 'position',
          'nullable' => false,
          'phpType' => 'int',
          'type' => 'int',
        ),
        4 => 
        array (
          'length' => 500,
          'name' => 'description',
          'nullable' => false,
          'phpType' => 'string',
          'type' => 'varchar',
        ),
        5 => 
        array (
          'name' => 'quantity',
          'nullable' => false,
          'phpType' => 'float',
          'precision' => 10,
          'scale' => 3,
          'type' => 'decimal',
        ),
        6 => 
        array (
          'length' => 20,
          'name' => 'unit',
          'nullable' => false,
          'phpType' => 'string',
          'type' => 'varchar',
        ),
        7 => 
        array (
          'name' => 'unit_price',
          'nullable' => false,
          'phpType' => 'float',
          'precision' => 12,
          'scale' => 4,
          'type' => 'decimal',
        ),
        8 => 
        array (
          'name' => 'discount_percent',
          'nullable' => true,
          'phpType' => 'float',
          'precision' => 5,
          'scale' => 2,
          'type' => 'decimal',
        ),
        9 => 
        array (
          'name' => 'line_total',
          'nullable' => false,
          'phpType' => 'float',
          'precision' => 12,
          'scale' => 2,
          'type' => 'decimal',
        ),
        10 => 
        array (
          'name' => 'tax_included',
          'nullable' => false,
          'phpType' => 'int',
          'type' => 'tinyint',
        ),
        11 => 
        array (
          'default' => 'CURRENT_TIMESTAMP',
          'name' => 'created_at',
          'nullable' => true,
          'phpType' => 'datetime',
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
          'phpType' => 'int',
          'primary' => true,
          'type' => 'int',
        ),
        1 => 
        array (
          'length' => 36,
          'name' => 'identifier',
          'nullable' => false,
          'phpType' => 'string',
          'type' => 'varchar',
        ),
        2 => 
        array (
          'length' => 50,
          'name' => 'invoice_number',
          'nullable' => false,
          'phpType' => 'string',
          'type' => 'varchar',
        ),
        3 => 
        array (
          'length' => 50,
          'name' => 'order_number',
          'nullable' => false,
          'phpType' => 'string',
          'type' => 'varchar',
        ),
        4 => 
        array (
          'length' => 36,
          'name' => 'customer_identifier',
          'nullable' => false,
          'phpType' => 'string',
          'type' => 'varchar',
        ),
        5 => 
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
          'phpType' => 'string',
          'type' => 'enum',
        ),
        6 => 
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
          'phpType' => 'string',
          'type' => 'enum',
        ),
        7 => 
        array (
          'name' => 'total_net',
          'nullable' => false,
          'phpType' => 'float',
          'precision' => 12,
          'scale' => 2,
          'type' => 'decimal',
        ),
        8 => 
        array (
          'name' => 'tax_rate',
          'nullable' => false,
          'phpType' => 'float',
          'precision' => 5,
          'scale' => 2,
          'type' => 'decimal',
        ),
        9 => 
        array (
          'name' => 'total_gross',
          'nullable' => false,
          'phpType' => 'float',
          'precision' => 12,
          'scale' => 2,
          'type' => 'decimal',
        ),
        10 => 
        array (
          'length' => 3,
          'name' => 'currency',
          'nullable' => false,
          'phpType' => 'string',
          'type' => 'varchar',
        ),
        11 => 
        array (
          'name' => 'note',
          'nullable' => true,
          'phpType' => 'string',
          'type' => 'text',
        ),
        12 => 
        array (
          'name' => 'issued_at',
          'nullable' => true,
          'phpType' => 'datetime',
          'type' => 'datetime',
        ),
        13 => 
        array (
          'name' => 'due_at',
          'nullable' => true,
          'phpType' => 'datetime',
          'type' => 'date',
        ),
        14 => 
        array (
          'name' => 'paid_at',
          'nullable' => true,
          'phpType' => 'datetime',
          'type' => 'datetime',
        ),
        15 => 
        array (
          'default' => 'CURRENT_TIMESTAMP',
          'name' => 'created_at',
          'nullable' => true,
          'phpType' => 'datetime',
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
          'phpType' => 'int',
          'primary' => true,
          'type' => 'int',
        ),
        1 => 
        array (
          'name' => 'item_id',
          'nullable' => false,
          'phpType' => 'int',
          'type' => 'int',
        ),
        2 => 
        array (
          'length' => 50,
          'name' => 'discount_code',
          'nullable' => false,
          'phpType' => 'string',
          'type' => 'varchar',
        ),
        3 => 
        array (
          'name' => 'amount',
          'nullable' => false,
          'phpType' => 'float',
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
          'phpType' => 'int',
          'primary' => true,
          'type' => 'int',
        ),
        1 => 
        array (
          'length' => 36,
          'name' => 'identifier',
          'nullable' => false,
          'phpType' => 'string',
          'type' => 'varchar',
        ),
        2 => 
        array (
          'name' => 'order_id',
          'nullable' => false,
          'phpType' => 'int',
          'type' => 'int',
        ),
        3 => 
        array (
          'length' => 36,
          'name' => 'product_identifier',
          'nullable' => false,
          'phpType' => 'string',
          'type' => 'varchar',
        ),
        4 => 
        array (
          'name' => 'quantity',
          'nullable' => false,
          'phpType' => 'int',
          'type' => 'int',
          'unsigned' => true,
        ),
        5 => 
        array (
          'name' => 'unit_price',
          'nullable' => false,
          'phpType' => 'float',
          'precision' => 10,
          'scale' => 2,
          'type' => 'decimal',
        ),
        6 => 
        array (
          'name' => 'subtotal',
          'nullable' => false,
          'phpType' => 'float',
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
          'phpType' => 'int',
          'primary' => true,
          'type' => 'int',
        ),
        1 => 
        array (
          'length' => 50,
          'name' => 'order_number',
          'nullable' => false,
          'phpType' => 'string',
          'type' => 'varchar',
        ),
        2 => 
        array (
          'name' => 'customer_id',
          'nullable' => false,
          'phpType' => 'int',
          'type' => 'int',
        ),
        3 => 
        array (
          'name' => 'total_amount',
          'nullable' => false,
          'phpType' => 'float',
          'precision' => 10,
          'scale' => 2,
          'type' => 'decimal',
        ),
        4 => 
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
          'phpType' => 'string',
          'type' => 'enum',
        ),
        5 => 
        array (
          'default' => 'CURRENT_TIMESTAMP',
          'name' => 'created_at',
          'nullable' => true,
          'phpType' => 'datetime',
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
  ),
);
