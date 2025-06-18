<?php
include '../db-config.php';
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="courses.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Title', 'Instructor ID', 'Created']);

$result = $conn->query("SELECT id, title, instructor_id, created_at FROM courses");
while ($row = $result->fetch_assoc()) {
    fputcsv($output, $row);
}
fclose($output);
exit;
