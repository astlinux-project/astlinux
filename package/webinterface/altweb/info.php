<?php

// Copyright (C) 2008-2009 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// info.php for AstLinux
// 12-09-2008
//

// Function: getSYSlocation
//
function getSYSlocation($base = '')
{
  if (($end = strrpos($_SERVER['SCRIPT_FILENAME'], $_SERVER['SCRIPT_NAME'])) === FALSE) {
    $str_R = '';
  } else {
    if (($str_R = substr($_SERVER['SCRIPT_FILENAME'], 0, $end)) !== '') {
      $str_R .= $base;
    }
  }
  return($str_R);
}

$topic = isset($_GET['topic']) ? $_GET['topic'] : '';
$ifile = getSYSlocation('/common/topics.info');
if ($topic === '' || $ifile === '') {
  exit;
}

$tmpfile = tempnam("/tmp", "PHP_");
@exec('sed -n "/^\[\['.$topic.'\]\]/,/^\[\[/ p" '.$ifile.' | sed "/^\[\[/ d" >'.$tmpfile);

header('Content-Type: text/plain;  charset=iso-8859-1');
header('Content-Disposition: inline; filename="'.$topic.'.txt"');
header('Content-Transfer-Encoding: binary');
header('Content-Length: '.filesize($tmpfile));
ob_clean();
flush();                   
@readfile($tmpfile);
@unlink($tmpfile);
exit;
?>
