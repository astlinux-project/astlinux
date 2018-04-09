<?php

// Copyright (C) 2008 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// insert-wav-inline.php for AstLinux
// 04-26-2008

$VOICEMAILCACHEPREFIX = 'VOICEMAIL_';
$MONITORCACHEPREFIX = 'MONITOR_';

// Function: insertWAVinline()
//
function insertWAVinline($wavfile, $type) {

  if ($type === 'html4-http' || $type === 'html5-http') {
    $wavURL = 'http://'.$_SERVER['HTTP_HOST'].$wavfile;
  } else {
    $wavURL = $wavfile;
  }

  if ($type === 'html5' || $type === 'html5-http') {
    echo '<audio src="'.$wavURL.'" autoplay="autoplay" controls="controls">';
  }
  echo '<object classid="clsid:6BF52A52-394A-11D3-B153-00C04F79FAA6" id="audio" width="150" height="32" type="application/x-oleobject">';
  echo '<param name="url" value="'.$wavURL.'" />';
  echo '<param name="autostart" value="true" />';
  echo '<param name="showcontrols" value="true" />';
  echo '<param name="uiMode" value="mini"/>';
  echo '<!--[if !IE]> -->';
  echo '<object type="audio/x-wav" data="'.$wavURL.'" width="140" height="28">';
  echo '<param name="src" value="'.$wavURL.'" />';
  echo '<param name="autoplay" value="true" />';
  echo '<param name="autostart" value="true" />';
  echo '<param name="controller" value="true" />';
  echo '</object>';
  echo '<!-- <![endif]-->';
  echo '</object>';
  if ($type === 'html5' || $type === 'html5-http') {
    echo '</audio>';
  }
}

// Function: unique_string()
//
function unique_string($len = 8) {

  $letters = 'abcefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $unique = substr(str_shuffle($letters), 0, $len);
  $unique .= date('is');

  return($unique);
}

// Function: createCACHElink()
//
function createCACHElink($wavfile, $sysdir, $prefix) {

  $cache = '/cache';
  if (! is_dir($dir = $sysdir.$cache)) {
    return(FALSE);
  }
  $cache .= '/'.$prefix;
  foreach (glob($sysdir.$cache.'*') as $globfile) {
    if (is_file($globfile)) {
      @unlink($globfile);
    }
  }
  $cache .= unique_string().'.wav';
  if (! @copy($wavfile, $sysdir.$cache)) {
    return(FALSE);
  }
  return($cache);
}

?>
