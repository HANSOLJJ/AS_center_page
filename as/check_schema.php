<?php
require_once 'mysql_compat.php';
$connect = mysql_connect('mysql', 'mic4u_user', 'change_me');
mysql_select_db('mic4u', $connect);

$query = "DESCRIBE step13_as";
$result = mysql_query($query);

echo "step13_as columns with 'in_no':\n";
while ($row = mysql_fetch_assoc($result)) {
    if (strpos($row['Field'], 'in_no') !== false) {
        echo $row['Field'] . ": " . $row['Type'] . " | Default: " . ($row['Default'] ?? 'NULL') . " | Extra: " . ($row['Extra'] ?? '') . "\n";
    }
}

mysql_close($connect);
?>
