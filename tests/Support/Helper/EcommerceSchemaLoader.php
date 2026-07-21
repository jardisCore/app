<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Support\Helper;

/**
 * Loads the single Sales BC schema vendored as a plain PHP array under
 * `examples/basic/schema/Sales/schema.php` into the flat schema-array
 * format `SchemaToMysqlDdl` consumes.
 *
 * Simplified single-file port of the Builder's
 * `JardisTools\Tests\Fixture\DomainSchemaLoader` (which merges every BC's
 * `Schema.yaml` under a domain) — the Ecommerce fixture's Sales tables
 * (addresses, customers, invoices, invoice_lines, item_discounts,
 * order_items, orders) carry no foreign key into Catalog/Fulfillment
 * (verified against the source `Schema.yaml` files), so only the Sales
 * schema is needed to run the `GET/POST/PATCH /orders` routes this
 * example app exposes.
 *
 * A plain PHP array — not a `Schema.yaml` parsed at runtime — deliberately:
 * pulling in `symfony/yaml` as a dependency would put a `symfony/*` package
 * in this very package's vendor tree, directly contradicting the K5
 * Vendor-Isolation harness this same phase (P7 Block C) has to prove
 * (`examples/basic/schema/Sales/Schema.yaml` is kept alongside as the
 * human-readable source of truth this was generated from — a one-time
 * `Symfony\Component\Yaml\Yaml::parseFile()` + `var_export()` conversion
 * done during authoring, not a build step this package repeats or a
 * runtime dependency it carries).
 *
 * @phpstan-type SchemaArray array{connection: string, tables: array<string, array<string, mixed>>}
 */
final class EcommerceSchemaLoader
{
    /**
     * @return SchemaArray
     */
    public static function load(): array
    {
        $path = dirname(__DIR__, 3) . '/examples/basic/schema/Sales/schema.php';
        /** @var SchemaArray $data */
        $data = require $path;

        return $data;
    }
}
