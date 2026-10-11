<?php
/**
 * Data provider for the admin sidebar.
 * Keep database queries outside the UI markup.
 */

if (!function_exists('admin_sidebar_count')) {
    function admin_sidebar_count($conn, string $sql): int
    {
        if (!$conn instanceof mysqli) {
            return 0;
        }

        $result = @$conn->query($sql);
        if (!$result) {
            return 0;
        }

        $row = $result->fetch_assoc();
        return (int)($row['c'] ?? 0);
    }
}

if (!function_exists('admin_sidebar_stats')) {
    function admin_sidebar_stats($conn): array
    {
        return [
            'toxic' => admin_sidebar_count($conn, 'SELECT COUNT(*) AS c FROM toxic_plants'),
            'rare' => admin_sidebar_count($conn, 'SELECT COUNT(*) AS c FROM rare_plants'),
            'disease' => admin_sidebar_count($conn, 'SELECT COUNT(*) AS c FROM disease_spread WHERE spread_percentage >= 50'),
        ];
    }
}
