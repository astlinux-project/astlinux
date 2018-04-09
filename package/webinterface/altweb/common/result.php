<?php

// Copyright (C) 2008-2009 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// result.php for AstLinux
// 03-25-2008

  putHtml('<center>');
  if (isset($_GET['result'])) {
    $result = $_GET['result'];
    if ($result == 0) {
      putHtml('<p style="color: green;">Action Successful.</p>');
    } elseif ($result == 1) {
      putHtml('<p style="color: orange;">No Action.</p>');
    } elseif ($result == 2) {
      if (($cmd = htmlspecialchars(getPREFdef($global_prefs, 'number_error_cmdstr'))) === '') {
        $cmd = 'Number must be 10 digits in the format NXXNXXXXXX';
      }
      putHtml('<p style="color: red;">'.$cmd.'</p>');
    } elseif ($result == 3) {
      putHtml('<p style="color: red;">Error.</p>');
    } elseif ($result == 4 || $result == 1101 || $result == 1102) {
      putHtml('<p style="color: red;">'.asteriskERROR($result).'</p>');
    } elseif ($result == 99) {
      putHtml('<p style="color: red;">Action Failed.</p>');
    } elseif ($result == 999) {
      putHtml('<p style="color: red;">Permission denied for user "'.$global_user.'".</p>');
    } else {
      putHtml('<p>&nbsp;</p>');
    }
  } elseif ($RESULT_NUMBER !== '') {
    putHtml('<p style="color: orange;">Number "'.$RESULT_NUMBER.'" is not defined, click "Save Changes" to add the number.</p>');
  } else {
    putHtml('<p>&nbsp;</p>');
  }
  putHtml('</center>');
?>
