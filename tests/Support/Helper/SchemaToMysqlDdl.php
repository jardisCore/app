<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Support\Helper;

/**
 * Converts a Schema array (Builder DB-export format) to MySQL DDL.
 *
 * Ported 1:1 from `jardistools/builder`
 * (`tests/Builder/Integration/Helper/SchemaToMysqlDdl.php`) — the same
 * DDL-generation pattern the Builder's own Ecommerce fixture harness uses
 * (PLAN.md P7 "Fixture-Betrieb im Builder"). This is a test-support
 * utility copied for parity, not a change to the vendored domain fixture
 * itself (`examples/basic/domain/Ecommerce/` stays byte-identical to its
 * source).
 *
 * Supports: column types (int/varchar/date/text/decimal), auto_increment,
 * NOT NULL, DEFAULT, PRIMARY KEY, UNIQUE INDEX, FOREIGN KEY.
 * FK-safe ordering for both CREATE and DROP.
 */
final class SchemaToMysqlDdl
{
    /**
     * Generates CREATE TABLE statements from schema array.
     *
     * Tables are ordered FK-safe: tables without FKs first, dependents after.
     *
     * @param array<string, mixed> $schemaArray Schema array with 'tables' key
     * @return array<string> CREATE TABLE SQL statements
     */
    public static function generate(array $schemaArray): array
    {
        $tables = $schemaArray['tables'] ?? [];
        $ordered = self::orderByDependencies($tables);

        $statements = [];
        foreach ($ordered as $tableName) {
            $table = $tables[$tableName];
            $statements[] = self::buildCreateTable($table);
        }

        return $statements;
    }

    /**
     * Generates DROP TABLE IF EXISTS statements in FK-safe reverse order.
     *
     * @param array<string, mixed> $schemaArray Schema array with 'tables' key
     * @return array<string> DROP TABLE SQL statements
     */
    public static function drop(array $schemaArray): array
    {
        $tables = $schemaArray['tables'] ?? [];
        $ordered = self::orderByDependencies($tables);
        $reversed = array_reverse($ordered);

        $statements = [];
        foreach ($reversed as $tableName) {
            $statements[] = "DROP TABLE IF EXISTS `{$tableName}`";
        }

        return $statements;
    }

    /**
     * @param array<string, mixed> $table
     */
    private static function buildCreateTable(array $table): string
    {
        $tableName = $table['name'];
        $lines = [];

        foreach ($table['columns'] as $column) {
            $lines[] = self::buildColumnDefinition($column);
        }

        foreach ($table['indexes'] ?? [] as $index) {
            if ($index['type'] === 'primary') {
                $cols = implode('`, `', $index['columns']);
                $lines[] = "PRIMARY KEY (`{$cols}`)";
            } elseif ($index['type'] === 'unique') {
                $cols = implode('`, `', $index['columns']);
                $indexName = self::truncateIdentifier($index['name']);
                $lines[] = "UNIQUE KEY `{$indexName}` (`{$cols}`)";
            }
        }

        foreach ($table['foreignKeys'] ?? [] as $fk) {
            $constraintName = self::truncateIdentifier(sprintf('fk_%s_%s', $tableName, $fk['column']));
            $lines[] = sprintf(
                'CONSTRAINT `%s` FOREIGN KEY (`%s`) REFERENCES `%s` (`%s`)',
                $constraintName,
                $fk['column'],
                $fk['referencedTable'],
                $fk['referencedColumn']
            );
        }

        $body = implode(",\n    ", $lines);

        return "CREATE TABLE `{$tableName}` (\n    {$body}\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    }

    /**
     * Truncates a MySQL identifier so it fits the 64-char limit. When the
     * natural name is too long, a short hash suffix preserves uniqueness.
     */
    private static function truncateIdentifier(string $name): string
    {
        if (strlen($name) <= 64) {
            return $name;
        }
        $hash = substr(hash('crc32b', $name), 0, 8);
        return substr($name, 0, 55) . '_' . $hash;
    }

    /**
     * @param array<string, mixed> $column
     */
    private static function buildColumnDefinition(array $column): string
    {
        $name = $column['name'];
        $type = self::mapColumnType($column);
        $nullable = ($column['nullable'] ?? false) ? '' : ' NOT NULL';
        $default = self::buildDefaultClause($column);
        $autoIncrement = ($column['autoincrement'] ?? false) ? ' AUTO_INCREMENT' : '';

        return "`{$name}` {$type}{$nullable}{$default}{$autoIncrement}";
    }

    /**
     * @param array<string, mixed> $column
     */
    private static function buildDefaultClause(array $column): string
    {
        if (!array_key_exists('default', $column) || $column['default'] === null) {
            return '';
        }

        $default = $column['default'];
        if (is_string($default) && strtoupper($default) === 'CURRENT_TIMESTAMP') {
            return ' DEFAULT CURRENT_TIMESTAMP';
        }

        $escaped = str_replace("'", "''", (string) $default);
        return " DEFAULT '{$escaped}'";
    }

    /**
     * @param array<string, mixed> $column
     */
    private static function mapColumnType(array $column): string
    {
        $type = strtolower($column['type']);

        return match ($type) {
            'int', 'integer' => 'INT',
            'varchar' => 'VARCHAR(' . ($column['length'] ?? 255) . ')',
            'date' => 'DATE',
            'datetime' => 'DATETIME',
            'timestamp' => 'TIMESTAMP',
            'text' => 'TEXT',
            'decimal' => 'DECIMAL(' . ($column['precision'] ?? 10) . ',' . ($column['scale'] ?? 2) . ')',
            'boolean', 'bool' => 'TINYINT(1)',
            'time' => 'TIME',
            'blob' => 'BLOB',
            'enum' => self::buildEnumType($column),
            default => strtoupper($type),
        };
    }

    /**
     * @param array<string, mixed> $column
     */
    private static function buildEnumType(array $column): string
    {
        $values = $column['enumValues'] ?? [];
        $quoted = array_map(static fn(string $v) => "'{$v}'", $values);
        return 'ENUM(' . implode(',', $quoted) . ')';
    }

    /**
     * Orders tables so that referenced tables come before dependent tables.
     *
     * @param array<string, array<string, mixed>> $tables
     * @return array<string> Ordered table names
     */
    private static function orderByDependencies(array $tables): array
    {
        $resolved = [];
        $unresolved = array_keys($tables);

        $maxIterations = count($unresolved) * count($unresolved);
        $iteration = 0;

        while (!empty($unresolved) && $iteration < $maxIterations) {
            $iteration++;
            foreach ($unresolved as $i => $tableName) {
                $dependencies = array_column($tables[$tableName]['foreignKeys'] ?? [], 'referencedTable');
                $dependencies = array_diff($dependencies, [$tableName]);

                if (empty(array_diff($dependencies, $resolved))) {
                    $resolved[] = $tableName;
                    unset($unresolved[$i]);
                    $unresolved = array_values($unresolved);
                    break;
                }
            }
        }

        return array_merge($resolved, $unresolved);
    }
}
