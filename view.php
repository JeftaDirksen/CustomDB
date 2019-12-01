<html>
<head><title>CustomDB</title></head>
<body>
<?php
require("functions.php");

// Input
if(!empty($_GET['table'])) {
  $table = sanitize($_GET['table']);
}
else die("Missing/Wrong table variable");
if(!empty($_GET['id'])) {
  $id = sanitize($_GET['id']);
}
else die("Missing/Wrong id variable");

// DB Connect
$mysqli = connect();

// Query
$result = $mysqli->query("select * from `$table` where id = $id");
if(!$result) die("Query error");

// Record
echo "<a href=\"edit.php?table=$table&id=$id\">Edit</a>\n";
echo "<table>";
while($row = $result->fetch_assoc()) {
  foreach($row as $key=>$value) {
    echo "<tr>";
    echo "<td>$key</td>";
    echo "<td>$value</td>";
    echo "</tr>\n";
  }
}
echo "</table>";
?>
</body>
</html>