<?php
include '../db-config.php'; // your DB connection
include '../includes/functions.php'; // if not already included

$log_from    = $_GET['log_from'] ?? '';
$log_to      = $_GET['log_to'] ?? '';
$log_role    = $_GET['log_role'] ?? '';
$log_action  = $_GET['log_action'] ?? '';

$conditions = [];
if ($log_from)    $conditions[] = "DATE(l.created_at) >= '$log_from'";
if ($log_to)      $conditions[] = "DATE(l.created_at) <= '$log_to'";
if ($log_role)    $conditions[] = "u.role = '$log_role'";
if ($log_action)  $conditions[] = "l.action LIKE '%$log_action%'";

$where = count($conditions) > 0 ? "WHERE " . implode(" AND ", $conditions) : "";

$query = "
  SELECT l.action, l.created_at, u.name, u.role 
  FROM logs l 
  JOIN users u ON l.user_id = u.id 
  $where 
  ORDER BY l.created_at DESC
  LIMIT 100
";
$result = $conn->query($query);
?>

<table class="table table-bordered table-sm align-middle">
  <thead class="table-light">
    <tr>
      <th>User</th>
      <th>Role</th>
      <th>Action</th>
      <th>Date</th>
      <th>Time</th>
    </tr>
  </thead>
  <tbody>
    <?php if ($result->num_rows): ?>
      <?php while ($log = $result->fetch_assoc()):
        $ts = strtotime($log['created_at']);
        $date = date("Y-m-d", $ts);
        $time = date("h:i A", $ts);
      ?>
        <tr>
          <td><?= htmlspecialchars($log['name']) ?></td>
          <td><span class="badge bg-secondary"><?= htmlspecialchars($log['role']) ?></span></td>
          <td><?= htmlspecialchars($log['action']) ?></td>
          <td><?= $date ?></td>
          <td class="text-muted"><?= $time ?></td>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr><td colspan="5" class="text-center text-muted">No logs found.</td></tr>
    <?php endif; ?>
  </tbody>
</table>
