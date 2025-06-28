<?php
include '../db-config.php';

$tab = $_GET['tab'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 5;
$offset = ($page - 1) * $limit;

function paginate($total, $page, $tab) {
  $total_pages = ceil($total / 5);
  if ($total_pages <= 1) return;

  echo "<nav class='mt-3'><ul class='pagination justify-content-center'>";
  for ($i = 1; $i <= $total_pages; $i++) {
    $active = $i == $page ? 'active' : '';
    echo "<li class='page-item $active'>
            <a class='page-link' href='#' onclick=\"loadTab('$tab', $i); return false;\">$i</a>
          </li>";
  }
  echo "</ul></nav>";
}

if ($tab === 'users') {
  $total = $conn->query("SELECT COUNT(*) AS t FROM users")->fetch_assoc()['t'];
  $res = $conn->query("SELECT name, email, created_at FROM users ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
  echo "<ul class='list-group'>";
  while ($u = $res->fetch_assoc()) {
    echo "<li class='list-group-item'>" . htmlspecialchars($u['name']) . " - " . htmlspecialchars($u['email']) .
         "<small class='float-end text-muted'>" . $u['created_at'] . "</small></li>";
  }
  echo "</ul>";
  paginate($total, $page, 'users');
}

elseif ($tab === 'courses') {
  $total = $conn->query("SELECT COUNT(*) AS t FROM courses")->fetch_assoc()['t'];
  $res = $conn->query("SELECT title, created_at FROM courses ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
  echo "<ul class='list-group'>";
  while ($c = $res->fetch_assoc()) {
    echo "<li class='list-group-item'>" . htmlspecialchars($c['title']) .
         "<small class='float-end text-muted'>" . $c['created_at'] . "</small></li>";
  }
  echo "</ul>";
  paginate($total, $page, 'courses');
}

elseif ($tab === 'comments') {
  $total = $conn->query("SELECT COUNT(*) AS t FROM comments")->fetch_assoc()['t'];
  $res = $conn->query("SELECT c.content, c.created_at, u.name, co.title 
                       FROM comments c 
                       JOIN users u ON c.user_id = u.id 
                       JOIN courses co ON c.course_id = co.id 
                       ORDER BY c.created_at DESC LIMIT $limit OFFSET $offset");
  echo "<ul class='list-group'>";
  while ($com = $res->fetch_assoc()) {
    echo "<li class='list-group-item'>" . htmlspecialchars($com['name']) . " on <strong>" .
         htmlspecialchars($com['title']) . "</strong><br><em>" . htmlspecialchars($com['content']) .
         "</em><small class='text-muted float-end'>" . $com['created_at'] . "</small></li>";
  }
  echo "</ul>";
  paginate($total, $page, 'comments');
}

else {
  echo "<div class='text-muted'>Invalid tab requested.</div>";
}
?>
