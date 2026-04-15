<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class SqlBackupService
{
    public function generateSqlDump(): string
    {
        $driver = DB::connection()->getDriverName();
        $pdo = DB::connection()->getPdo();
        $tables = $driver === 'sqlite'
            ? array_map(fn ($row) => $row->name, DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"))
            : array_map(fn ($row) => array_values((array) $row)[0], DB::select('SHOW TABLES'));

        $sql = "-- OIPSMS SQL backup\n-- Generated at ".now()->toDateTimeString()."\n\nSET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            $rows = DB::table($table)->get();
            if ($rows->isEmpty()) {
                continue;
            }

            $columns = array_keys((array) $rows->first());
            $columnSql = implode(', ', array_map(fn ($column) => "`{$column}`", $columns));

            foreach ($rows as $row) {
                $values = array_map(function ($value) use ($pdo) {
                    if ($value === null) {
                        return 'NULL';
                    }

                    if (is_bool($value)) {
                        return $value ? '1' : '0';
                    }

                    return $pdo->quote((string) $value);
                }, array_values((array) $row));

                $sql .= "INSERT INTO `{$table}` ({$columnSql}) VALUES (".implode(', ', $values).");\n";
            }

            $sql .= "\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        return $sql;
    }
}
