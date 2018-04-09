<?php

// Copyright (C) 2008-2017 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// cli.php for AstLinux
// 12-01-2011
// 02-16-2017, Optionally allow "staff" access
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
$ACCESS_RIGHTS = $global_staff_enable_cli ? 'staff' : 'admin';
require_once '../common/header.php';

  putHtml("<center>");
  putHtml('<p>&nbsp;</p>');
  putHtml("</center>");
?>
  <script language="JavaScript" type="text/javascript">
  //<![CDATA[

  function setOKexit() {
    var cli = document.getElementById("cli");
    var active = cli.contentWindow.document.getElementById("reconnect").style.visibility == 'hidden';
    if (active) {
      return 'CLI session is active, leaving will kill the session!';
    }
  }

  function setCLIheight() {
    var winW = 840;
    if (document.documentElement && document.documentElement.offsetWidth) {
      winW = document.documentElement.offsetWidth;
    }
    if (window.innerWidth) {
      winW = window.innerWidth;
    }
    document.getElementById("cli").height = winW * 11 / 20;
    window.onresize = setCLIheight;
    window.onbeforeunload = setOKexit;
  }
  //]]>
  </script>
  <center>
  <table class="layoutNOpad" width="100%"><tr><td><center>
<?php

  putHtml('<table class="stdtable" width="100%"><tr><td style="text-align: center;">');
  if (is_file('/var/run/shellinaboxd.pid')) {
    echo '<iframe id="cli" src="/admin/cli/" frameborder="1" width="100%" style="background-color: #ffffff;" onload="setCLIheight();">';
    putHtml('</iframe>');
  } else {
    putHtml('<p style="color: red;">The CLI Proxy Server is not running, enable via the Network Tab.</p>');
  }
  putHtml('</td></tr></table>');
  putHtml("</center></td></tr></table>");
  putHtml("</center>");
} // End of HTTP GET
require_once '../common/footer.php';

?>
