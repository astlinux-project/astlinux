<?php

// Copyright (C) 2013 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// sqldata.php for AstLinux
// 03-09-2013
//

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

// Function: create_sql_tables()
//
function create_sql_tables()
{
  global $global_prefs;

  try {
    $pdo_db = new PDO("sqlite:/mnt/kd/asterisk-odbc.sqlite3");
    $pdo_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (getPREFdef($global_prefs, 'sqldata_create_schema') !== 'no') {

      // List all tables
      $tbl_arr = array();
      $sql = "SELECT name FROM sqlite_master WHERE type='table'";
      foreach ($pdo_db->query($sql) as $row) {
        $tbl_arr[] = $row['name'];
      }
      if (! in_array('sip_users', $tbl_arr) || ! in_array('out_context', $tbl_arr) || ! in_array('ip_phones', $tbl_arr)) {
        $sql_file = getSYSlocation('/common/sqldata.sql');
        if (is_file($sql_file)) {
          if (($sql_str = @file_get_contents($sql_file)) !== FALSE) {
            $sql_arr = explode(';', $sql_str);
            foreach ($sql_arr as $sql) {
              if (($trim_sql = trim($sql)) !== '') {
                $pdo_db->exec($trim_sql);
              }
            }
          } else {
            return("ERROR: File '$sql_file' failed to load.");
          }
        } else {
          return("ERROR: File '$sql_file' not found.");
        }
      }
    }
    $pdo_db = NULL;
  } catch (PDOException $e) {
    return($e->getMessage());
  }
  return(TRUE);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! ($global_admin || $global_staff_enable_sqldata)) {
    $result = 999;
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} else { // Start of HTTP GET
$ACCESS_RIGHTS = $global_staff_enable_sqldata ? 'staff' : 'admin';
require_once '../common/header.php';

  putHtml("<center>");
  putHtml('<p>&nbsp;</p>');
  putHtml("</center>");
?>
  <script language="JavaScript" type="text/javascript">
  //<![CDATA[

  function setIFheight() {
    var iframe = document.getElementById("sqldata");
    var winH = 460;
    if (document.documentElement && document.documentElement.offsetHeight) {
      winH = document.documentElement.offsetHeight;
    }
    if (window.innerHeight) {
      winH = window.innerHeight;
    }
    var offset = 160;
    if (iframe.getBoundingClientRect) {
      offset = iframe.getBoundingClientRect().top + 22;
    }

    iframe.height = winH - offset;
    window.onresize = setIFheight;
  }
  //]]>
  </script>
  <center>
  <table class="layoutNOpad" width="100%"><tr><td><center>
<?php

  putHtml('<table class="stdtable" width="100%"><tr><td style="text-align: center;">');
  if (class_exists('PDO')) {
    if (($errStr = create_sql_tables()) === TRUE) {
      echo '<iframe id="sqldata" src="/admin/phpliteadmin.php" frameborder="1" width="100%" onload="setIFheight();">';
      putHtml('</iframe>');
    } else {
      putHtml('<p style="color: red;">'.htmlspecialchars($errStr).'</p>');
    }
  } else {
    putHtml('<p style="color: red;">The PDO SQLite3 module is not available.</p>');
  }
  putHtml('</td></tr></table>');
  putHtml("</center></td></tr></table>");
  putHtml("</center>");
} // End of HTTP GET
require_once '../common/footer.php';

?>
