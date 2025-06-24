<?php
include '../db-config.php';
include '../includes/functions.php';

$log_from   = $_GET['log_from'] ?? '';
$log_to     = $_GET['log_to'] ?? '';
$log_role   = $_GET['log_role'] ?? '';
$log_action = $_GET['log_action'] ?? '';
$export     = $_GET['export'] ?? '';
$page       = $_GET['page'] ?? 1;
$limit      = 25;
$offset     = ($page - 1) * $limit;

$conditions = [];
if ($log_from)     $conditions[] = "DATE(l.created_at) >= '$log_from'";
if ($log_to)       $conditions[] = "DATE(l.created_at) <= '$log_to'";
if ($log_role)     $conditions[] = "u.role = '$log_role'";
if ($log_action)   $conditions[] = "l.action LIKE '%$log_action%'";

$where = count($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Total logs
$total = $conn->query("SELECT COUNT(*) AS total FROM logs l JOIN users u ON l.user_id = u.id $where")->fetch_assoc()['total'];

// Actual logs
$query = "
  SELECT l.action, l.created_at, u.name, u.role 
  FROM logs l 
  JOIN users u ON l.user_id = u.id 
  $where 
  ORDER BY l.created_at DESC 
  LIMIT $limit OFFSET $offset
";
$result = $conn->query($query);

// Export if needed
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

if ($export === 'pdf') {
  require('../vendor/autoload.php');
  use Dompdf\Dompdf;

  $html = "<h2>Activity Logs</h2><table border='1' cellpadding='6' cellspacing='0'><tr>
            <th>User</th><th>Role</th><th>Action</th><th>Date</th><th>Time</th></tr>";
  while ($row = $result->fetch_assoc()) {
    $ts = strtotime($row['created_at']);
    $html .= "<tr>
      <td>{$row['name']}</td>
      <td>{$row['role']}</td>
      <td>{$row['action']}</td>
      <td>" . date("Y-m-d", $ts) . "</td>
      <td>" . date("h:i A", $ts) . "</td>
    </tr>";
  }
  $html .= "</table>";

  $dompdf = new Dompdf();
  $dompdf->loadHtml($html);
  $dompdf->render();
  $dompdf->stream("logs.pdf");
  exit;
}

// Regular load for table
$from = $offset + 1;
$to = min($offset + $limit, $total);

echo "<script>updateLogCount($from, $to, $total);</script>";
echo "<table class='table table-bordered table-sm align-middle'><thead>
        <tr><th>User</th><th>Role</th><th>Action</th><th>Date</th><th>Time</th></tr></thead><tbody>";

if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $ts = strtotime($row['created_at']);
    echo "<tr>
      <td>".htmlspecialchars($row['name'])."</td>
      <td><span class='badge bg-secondary'>{$row['role']}</span></td>
      <td>".htmlspecialchars($row['action'])."</td>
      <td>".date("Y-m-d", $ts)."</td>
      <td class='text-muted'>".date("h:i A", $ts)."</td>
    </tr>";
  }
} else {
  echo "<tr><td colspan='5' class='text-center text-muted'>No logs found.</td></tr>";
}
echo "</tbody></table>";
?>
