<?php

// Copyright (C) 2015 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// fossil.php for AstLinux
// 08-21-2015
//

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! $global_admin) {
    $result = 999;
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} else { // Start of HTTP GET
$ACCESS_RIGHTS = 'admin';
require_once '../common/header.php';

  putHtml("<center>");
  putHtml('<p>&nbsp;</p>');
  putHtml("</center>");
?>
  <script language="JavaScript" type="text/javascript">
  //<![CDATA[

  function setIFheight() {
    var iframe = document.getElementById("fossil");
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
  if (is_file('/var/run/fossil.pid')) {
    echo '<iframe id="fossil" src="/admin/fossil/timeline?y=ci" frameborder="1" width="100%" onload="setIFheight();">';
    putHtml('</iframe>');
  } elseif (! is_file('/etc/init.d/fossil')) {
    putHtml('<p style="color: red;">Fossil is not available on your AstLinux image.</p>');
  } else {
    putHtml('<p style="color: red;">The Fossil Server is not running, enable via the Network Tab.</p>');
  }
  putHtml('</td></tr></table>');
  putHtml("</center></td></tr></table>");
  putHtml("</center>");
} // End of HTTP GET
require_once '../common/footer.php';

?>
