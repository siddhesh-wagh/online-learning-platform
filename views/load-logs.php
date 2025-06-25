<?php
require '../vendor/autoload.php'; // Dompdf autoload
include '../db-config.php';
include '../includes/functions.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Get filter parameters
$log_from   = $_GET['log_from'] ?? '';
$log_to     = $_GET['log_to'] ?? '';
$log_role   = $_GET['log_role'] ?? '';
$log_action = $_GET['log_action'] ?? '';
$export     = $_GET['export'] ?? '';
$page       = max(1, (int) ($_GET['page'] ?? 1));
$limit      = 25;
$offset     = ($page - 1) * $limit;

// Build WHERE clause
$conditions = [];
if ($log_from)     $conditions[] = "DATE(l.created_at) >= '" . $conn->real_escape_string($log_from) . "'";
if ($log_to)       $conditions[] = "DATE(l.created_at) <= '" . $conn->real_escape_string($log_to) . "'";
if ($log_role)     $conditions[] = "u.role = '" . $conn->real_escape_string($log_role) . "'";
if ($log_action)   $conditions[] = "l.action LIKE '%" . $conn->real_escape_string($log_action) . "%'";

$where = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

// Total logs count
$total_sql = "SELECT COUNT(*) AS total FROM logs l JOIN users u ON l.user_id = u.id $where";
$total_res = $conn->query($total_sql);
$total = $total_res ? (int) $total_res->fetch_assoc()['total'] : 0;

// Fetch logs with limit
$query = "
  SELECT l.action, l.created_at, u.name, u.role 
  FROM logs l 
  JOIN users u ON l.user_id = u.id 
  $where 
  ORDER BY l.created_at DESC 
  LIMIT $limit OFFSET $offset
";
$result = $conn->query($query);

// ========== EXPORT CSV ==========
if ($export === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="logs.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['User', 'Role', 'Action', 'Date', 'Time']);

    while ($row = $result->fetch_assoc()) {
        $ts = strtotime($row['created_at']);
        fputcsv($out, [
            $row['name'],
            $row['role'],
            $row['action'],
            date("Y-m-d", $ts),
            date("h:i A", $ts)
        ]);
    }
    fclose($out);
    exit;
}

// ========== EXPORT PDF ==========
if ($export === 'pdf') {
    $html = "<h2>Activity Logs</h2>
    <table border='1' cellpadding='6' cellspacing='0' width='100%'>
    <tr><th>User</th><th>Role</th><th>Action</th><th>Date</th><th>Time</th></tr>";

    while ($row = $result->fetch_assoc()) {
        $ts = strtotime($row['created_at']);
        $html .= "<tr>
            <td>" . htmlspecialchars($row['name']) . "</td>
            <td>" . htmlspecialchars($row['role']) . "</td>
            <td>" . htmlspecialchars($row['action']) . "</td>
            <td>" . date("Y-m-d", $ts) . "</td>
            <td>" . date("h:i A", $ts) . "</td>
        </tr>";
    }

    $html .= "</table>";
    $options = new Options();
    $options->set('defaultFont', 'Arial');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("logs.pdf");
    exit;
}

// ========== AJAX Load ==========

// Fix: if no logs found
$from = ($total > 0) ? ($offset + 1) : 0;
$to   = ($total > 0) ? min($offset + $limit, $total) : 0;

echo "<script>updateLogCount($from, $to, $total);</script>";

echo "<table class='table table-bordered table-sm align-middle'>
<thead class='table-light'>
  <tr><th>User</th><th>Role</th><th>Action</th><th>Date</th><th>Time</th></tr>
</thead><tbody>";

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $ts = strtotime($row['created_at']);
        echo "<tr>
          <td>" . htmlspecialchars($row['name']) . "</td>
          <td><span class='badge bg-secondary'>" . htmlspecialchars($row['role']) . "</span></td>
          <td>" . htmlspecialchars($row['action']) . "</td>
          <td>" . date("Y-m-d", $ts) . "</td>
          <td class='text-muted'>" . date("h:i A", $ts) . "</td>
        </tr>";
    }
} else {
    echo "<tr><td colspan='5' class='text-center text-muted'>No logs found.</td></tr>";
}

echo "</tbody></table>";
?>
