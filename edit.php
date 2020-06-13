<?php
require("init.php");

// Save
if(isset($_POST['form_submit'])) {
  $setfields = [];
  foreach($_POST as $key=>$value) {
    $key = sanitize($key);
    $value = sanitize($value);
    if($key == "form_table") $table = $value;
    if($key == "form_id") $id = $value;
    if(substr($key,0,5) != "form_") {
      $setfields[] = "`$key` = '$value'";
    }
  }
  
  $query = "update `$table` set ".join(", ",$setfields)." where id = $id";
  if(!$result = $mysqli->query($query)) die($mysqli->error);
  
  redirect("view.php?table=$table&id=$id");
}

require("header.php");

// Input
$table = isset($_GET['table']) ? sanitize($_GET['table']) : "";
$id = isset($_GET['id']) ? sanitize($_GET['id']) : "";
$new = isset($_GET['new']) ? true : false;
if(empty($table)||(empty($id)&&!$new)) die("Input error");

// Query
if($new) {
  if(!$result = $mysqli->query("insert into `$table` () values()"))
    die($mysqli->error);
  $id = $mysqli->insert_id;
}
if(!$result = $mysqli->query("select * from `$table` where id = $id"))
  die($mysqli->error);

// Table
if($new) button(ICON_BACK, "view.php?table=$table&id=$id&delete");
else {
  button(ICON_BACK, "view.php?table=$table&id=$id");
}
echo "<form method=\"POST\">\n";
echo "<input type=\"hidden\" name=\"form_table\" value=\"$table\">\n";
echo "<input type=\"hidden\" name=\"form_id\" value=\"$id\">\n";
echo "<table>\n";
$row = $result->fetch_assoc();
foreach($row as $field=>$value) {
  if($field == "id") continue;
  $type = getFieldType($table, $field);
  echo "<tr>";
  echo "<td>$field</td>";
  if(isFK($table, $field)) {
    echo "<td>".fkDropdown($table, $field, $value)."</td>";
  }
  else echo "<td><input type=\"$type\" name=\"$field\" value=\"$value\"></td>";
  echo "</tr>\n";
}
?>
<tr><td></td><td><input type="submit" name="form_submit" value="<?php echo ICON_SAVE ?>"</td></tr>
</table>
</form>
<?php
require("footer.php");
