<?php
/**
 * Shared export/report helpers for the admin dashboard.
 * Keeps CSV/report operations out of individual pages and prevents repetition.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function admin_require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: login_register.php');
        exit;
    }
}

function admin_h($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function admin_scalar(mysqli $conn, string $sql, $default = 0)
{
    $result = $conn->query($sql);
    if (!$result) {
        return $default;
    }
    $row = $result->fetch_row();
    return $row[0] ?? $default;
}

function admin_fetch_rows(mysqli $conn, string $sql): array
{
    $rows = [];
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function admin_send_csv_headers(string $filename): void
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo "\xEF\xBB\xBF";
}

function admin_write_csv_row($handle, array $row): void
{
    fputcsv($handle, $row);
}

function admin_write_csv_section($handle, string $title, array $headers, array $rows, ?callable $mapper = null): void
{
    admin_write_csv_row($handle, [$title]);
    admin_write_csv_row($handle, $headers);
    foreach ($rows as $row) {
        admin_write_csv_row($handle, $mapper ? $mapper($row) : array_values($row));
    }
    admin_write_csv_row($handle, []);
}
