<?php
function connect() {
  @$mysqli = new mysqli(ini_get("mysqli.default_host"), $_SESSION['username'], $_SESSION['password'], $_SESSION['db']);
  if($mysqli->connect_error) {
    if(in_array($mysqli->connect_errno, array(1045,4151))) redirect('login.php');
    die($mysqli->connect_errno." ".$mysqli->connect_error);
  }
  return $mysqli;
}

function sanitize($string) {
  return addslashes(str_replace("`", "``", $string));
}

function redirect($url = "") {
  if(empty($url)) $url = $_SERVER['REQUEST_URI'];
  header("Location: $url", true, 303);
  die();
}

function button($icon, $href = "", $confirm = "") {
  if($confirm) echo "<a href=\"javascript:if(confirm('$confirm')) location.href='$href'\"><i class=\"material-icons\">$icon</i></a>\n";
  else echo "<a href=\"$href\"><i class=\"material-icons\">$icon</i></a>\n";
}

function getFieldData($table, $field) {
  global $mysqli;
  if(!$result = $mysqli->query("show full columns from `$table` where Field = '$field'"))
    die($mysqli->error);
  $row = $result->fetch_assoc();
  
  // Caption
  if(preg_match('/".+"/', $row['Comment']) == 1) $fd['caption'] = explode('"',$row['Comment'])[1];
  else $fd['caption'] = ucfirst($field);
  
  // Hide
  $fd['hide'] = (strpos($row['Comment'],"_") !== false) ? "1" : "0";
  
  // Type
  $type = explode("(",$row['Type'])[0];
  if(substr($type, -3) == "int") $fd['type'] = "number";
  elseif(substr($type, -4) == "char") $fd['type'] = "text";
  elseif($type == "decimal") $fd['type'] = "decimal";
  elseif($type == "date") $fd['type'] = "date";
  elseif($type == "bit") $fd['type'] = "checkbox";
  else $df['type'] = "unknown($type)";

  // Size
  if($fd['type'] == "text") $fd['size'] = rtrim(explode("(",$row['Type'])[1],")");
  
  // Decimal
  elseif($fd['type'] == "decimal") {
    list($precision,$scale) = explode(",",rtrim(explode("(",$row['Type'])[1],")"));
    $fd['min'] = rtrim("-".str_repeat("9",$precision-$scale).".".str_repeat("9",$scale),".");
    $fd['max'] = rtrim(str_repeat("9",$precision-$scale).".".str_repeat("9",$scale),".");
    if($scale == 0) $fd['step'] = "1";
    else $fd['step'] = "0.".str_repeat("0",$scale-1)."1";
  }

  // Lookup
  if($row['Key'] == "MUL") {
    $q = "SELECT REFERENCED_TABLE_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE CONSTRAINT_SCHEMA = SCHEMA() AND TABLE_NAME = '$table' AND COLUMN_NAME = '$field'";
    if(!$result = $mysqli->query($q)) die($mysqli->error);
    $row = $result->fetch_assoc();
    $fd['lookup'] = $row['REFERENCED_TABLE_NAME'];
  }
  else $fd['lookup'] = false;

  return $fd;
}

function getTableData($table) {
  global $mysqli;
  $q = "SELECT TABLE_COMMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = SCHEMA() and TABLE_NAME = '$table'";
  if(!$result = $mysqli->query($q)) die($mysqli->error);
  $row = $result->fetch_assoc();
  
  // Caption
  if(preg_match('/".+"/', $row['TABLE_COMMENT']) == 1) $td['caption'] = explode('"',$row['TABLE_COMMENT'])[1];
  else $td['caption'] = ucfirst($table);

  // Hide
  $td['hide'] = (strpos($row['TABLE_COMMENT'],"_") !== false) ? "1" : "0";

  return $td;
}

function fkDropdown($table, $field, $selected = "") {
  global $mysqli;
  $q = "SELECT REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE " .
       "WHERE CONSTRAINT_SCHEMA = SCHEMA() and TABLE_NAME = '$table' and COLUMN_NAME = '$field'";
  if(!$result = $mysqli->query($q)) die($mysqli->error);
  $row = $result->fetch_assoc();
  $r_table = $row['REFERENCED_TABLE_NAME'];
  $r_field = $row['REFERENCED_COLUMN_NAME'];
  
  $dropdown = "<select name=\"$field\">";
  if(!$result = $mysqli->query("select * from $r_table")) die($mysqli->error);
  while($row = $result->fetch_assoc()) {
    $value = $row[$r_field];
    $lookupValue = array_values($row)[1];
    $sel = ($value == $selected) ? " selected" : "";
    $dropdown .= "<option value=\"$value\"$sel>$lookupValue</option>";
  }
  $dropdown .= "</select>\n";
  return $dropdown;
}

function getLookupValue($lookupTable, $id) {
  global $mysqli;
  $q = "SELECT * FROM `$lookupTable` WHERE id = $id";
  if(!$result = $mysqli->query($q)) die($mysqli->error);
  $row = $result->fetch_array();
  return $row[1];
}

function breadcrumbs($table = null, $record = null, $action = null) {
  $breadcrumbs = "<div><a href=\"/\">".ucwords($_SESSION['db'])."</a>";
  if($table) {
    $breadcrumbs .= " &#8674; <a href=\"browse.php?table=$table\">".getTableData($table)['caption']."</a>";
  }
  if($record) {
    $breadcrumbs .= " &#8674; <a href=\"view.php?table=$table&id=$record\">".getRecordData($table, $record)['caption']."</a>";
  }
  if($action) $breadcrumbs .= " &#8674; $action";
  $breadcrumbs .= "</div><br>";
  return $breadcrumbs;
}

function getRecordData($table, $id) {
  global $mysqli;
  if(!$result = $mysqli->query("select * from `$table` where id = $id"))
    die($mysqli->error);
  $rd = $result->fetch_assoc();
  $rd['caption'] = array_values($rd)[1];
  return $rd;
}
