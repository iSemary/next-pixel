<?php

namespace App\Support;

use Illuminate\Database\QueryException;

class DatabaseExceptionInspector
{
    /**
     * Determine whether a QueryException represents a unique-constraint violation across MySQL, PostgreSQL, and SQLite.
     */
    public static function isUniqueConstraintViolation(QueryException $e): bool
    {
        $driverCode = $e->errorInfo[1] ?? null;
        $sqlState = (string) ($e->errorInfo[0] ?? $e->getCode());

        return $driverCode === 1062
            || $driverCode === 7
            || in_array($sqlState, ['23000', '23505'], true)
            || str_contains($e->getMessage(), 'Duplicate entry')
            || str_contains($e->getMessage(), 'duplicate key value')
            || str_contains($e->getMessage(), 'UNIQUE constraint failed');
    }
}
