<?php

// Copyright (C) 2008-2019 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// footer.php for AstLinux
// 03-25-2008
// 09-03-2019, Add Goto-Top button

?>
<button onclick="gototop_action()" id="gttBtn" title="Goto Top">&uarr;</button>
<script language="JavaScript" type="text/javascript">
//<![CDATA[
// When the user clicks on the button, scroll to the top of the document
function gototop_action() {
  document.body.scrollTop = 0;
  document.documentElement.scrollTop = 0;
}

function set_gototop_scroll() {
  if (document.body.scrollTop > 48 || document.documentElement.scrollTop > 48) {
    document.getElementById("gttBtn").style.display = "block";
  } else {
    document.getElementById("gttBtn").style.display = "none";
  }
}

function setGTThandler() {
  set_gototop_scroll();
  window.onscroll = set_gototop_scroll;
}
setGTThandler();
//]]>
</script>
</body>
</html>
