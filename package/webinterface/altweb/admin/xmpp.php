<?php

// Copyright (C) 2013-2017 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// xmpp.php for AstLinux
// 11-01-2013
// 02-10-2016, Added Staff support
//
// System location of /mnt/kd/rc.conf.d directory
$XMPPCONFDIR = '/mnt/kd/rc.conf.d';
// System location of gui.xmpp.conf file
$XMPPCONFFILE = '/mnt/kd/rc.conf.d/gui.xmpp.conf';

$verbosity_menu = array (
  'error' => 'Low',
  'warn' => 'Medium',
  'info' => 'High',
  'none' => 'None'
);

$idle_timeout_menu = array (
  '' => 'disabled',
  '120' => '2 minute',
  '180' => '3 minute',
  '240' => '4 minute',
  '300' => '5 minute',
  '360' => '6 minute',
  '480' => '8 minute',
  '600' => '10 minute'
);

$pubsub_autocreate_menu = array (
  '' => 'disabled',
  'yes' => 'Publish and Subscribe',
  'publish' => 'Publish Only',
  'subscribe' => 'Subscribe Only'
);

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

// Function: xmppGETclients
//
function xmppGETclients($vars) {
  $id = 0;

  if (is_file('/mnt/kd/prosody/prosody.cfg.lua')) {
    $tmpfile = tempnam("/tmp", "PHP_");
    shell('prosodyctl mod_listusers >'.$tmpfile.' 2>/dev/null', $status);
    if ($status == 0) {
      if (($fh = @fopen($tmpfile, "r")) !== FALSE) {
        while (! feof($fh)) {
          if (($line = trim(fgets($fh, 1024))) !== '') {
            $db['data'][$id]['user'] = $line;
            $id++;
          }
        }
        fclose($fh);
      }
    }
    @unlink($tmpfile);
  }
  // Sort by Username
  if ($id > 1) {
    foreach ($db['data'] as $key => $row) {
      $user[$key] = $row['user'];
    }
    array_multisort($user, SORT_ASC, SORT_STRING, $db['data']);
  }
  return($db);
}

// Function: saveXMPPsettings
//
function saveXMPPsettings($conf_dir, $conf_file) {
  global $global_admin;
  $result = 11;

  // Don't save settings if 'staff' user.
  if (! $global_admin) {
    return($result);
  }

  if (! is_dir($conf_dir)) {
    return(3);
  }
  if (($fp = @fopen($conf_file,"wb")) === FALSE) {
    return(3);
  }
  fwrite($fp, "### gui.xmpp.conf - start ###\n###\n");

  $value = 'XMPP_ENABLE="'.$_POST['xmpp_enable'].'"';
  fwrite($fp, "### XMPP Enable\n".$value."\n");

  $value = 'XMPP_ENABLE_S2S="'.$_POST['xmpp_enable_s2s'].'"';
  fwrite($fp, "### XMPP Server to Server Connections\n".$value."\n");

  $value = 'XMPP_SYSLOG="'.$_POST['verbosity'].'"';
  fwrite($fp, "### Log Syslog\n".$value."\n");

  $value = 'XMPP_C2S_PORT="'.tuq($_POST['xmpp_c2s_port']).'"';
  fwrite($fp, "### Client to Server TCP Port\n".$value."\n");

  $value = 'XMPP_S2S_PORT="'.tuq($_POST['xmpp_s2s_port']).'"';
  fwrite($fp, "### Server to Server TCP Port\n".$value."\n");

  $value = 'XMPP_GROUPS="'.$_POST['xmpp_groups'].'"';
  fwrite($fp, "### Shared Groups\n".$value."\n");

  $value = 'XMPP_C2S_IDLE_TIMEOUT="'.$_POST['idle_timeout'].'"';
  fwrite($fp, "### Dead Client Timeout\n".$value."\n");

  $value = 'XMPP_HOSTNAME="'.tuq($_POST['xmpp_hostname']).'"';
  fwrite($fp, "### XMPP VirtualHost\n".$value."\n");

  $value = 'XMPP_ADMIN_USERS="'.tuq($_POST['xmpp_admin_users']).'"';
  fwrite($fp, "### Admin Users\n".$value."\n");

  $value = 'XMPP_ENABLE_MODULES="'.tuq($_POST['xmpp_enable_modules']).'"';
  fwrite($fp, "### Enable Additional Modules\n".$value."\n");

  $value = 'XMPP_DISABLE_MODULES="'.tuq($_POST['xmpp_disable_modules']).'"';
  fwrite($fp, "### Disable Default Modules\n".$value."\n");

  $value = 'XMPP_CONFERENCE="'.tuq($_POST['xmpp_conference']).'"';
  fwrite($fp, "### Multi-User Chat Conference\n".$value."\n");

  $value = 'XMPP_PUBSUB="'.tuq($_POST['xmpp_pubsub']).'"';
  fwrite($fp, "### PubSub Service\n".$value."\n");

  $value = 'XMPP_PUBSUB_ADMINS="'.tuq($_POST['xmpp_pubsub_admins']).'"';
  fwrite($fp, "### PubSub Admins\n".$value."\n");

  $value = 'XMPP_PUBSUB_AUTOCREATE="'.$_POST['xmpp_pubsub_autocreate'].'"';
  fwrite($fp, "### PubSub Autocreate\n".$value."\n");

  $value = 'XMPP_CERT=""';
  fwrite($fp, "### Default Certificate Path\n".$value."\n");

  $value = 'XMPP_KEY=""';
  fwrite($fp, "### Default Key Path\n".$value."\n");

  fwrite($fp, "### gui.xmpp.conf - end ###\n");
  fclose($fp);

  return($result);
}

// Function: changeUserPass
//
function changeUserPass() {

  $user = str_replace(' ', '', stripshellsafe($_POST['user']));
  $pass = str_replace(' ', '', stripshellsafe($_POST['pass']));

  if ($user === '') {
    return(FALSE);
  }
  if ($pass === '') {
    return(1);
  }
  if (! is_file('/mnt/kd/prosody/prosody.cfg.lua')) {
    return(2);
  }

  shell("echo -e '$pass\\n$pass' | prosodyctl passwd '$user' >/dev/null 2>/dev/null", $status);

  return($status == 0 ? TRUE : FALSE);
}

// Function: addUserPass
//
function addUserPass() {

  $user = str_replace(' ', '', stripshellsafe($_POST['user']));
  $pass = str_replace(' ', '', stripshellsafe($_POST['pass']));

  if ($user === '') {
    return(FALSE);
  }
  if ($pass === '') {
    return(1);
  }
  if (! is_file('/mnt/kd/prosody/prosody.cfg.lua')) {
    return(2);
  }
  if (! preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]*[@][a-zA-Z][a-zA-Z0-9._-]*[a-zA-Z]$/', $user)) {
    return(4);
  }

  shell("echo -e '$pass\\n$pass' | prosodyctl adduser '$user' >/dev/null 2>/dev/null", $status);

  return($status == 0 ? TRUE : FALSE);
}

// Function: deleteUser
//
function deleteUser($user) {

  $user = str_replace(' ', '', stripshellsafe($user));

  if ($user === '') {
    return(FALSE);
  }
  if (! is_file('/mnt/kd/prosody/prosody.cfg.lua')) {
    return(2);
  }

  shell("prosodyctl deluser '$user' >/dev/null 2>/dev/null", $status);

  return($status == 0 ? TRUE : FALSE);
}

// Function: reloadModule
//
function reloadModule($mod) {

  if ($mod === '') {
    return(FALSE);
  }
  if (! is_file('/mnt/kd/prosody/prosody.cfg.lua')) {
    return(2);
  }
  if (! is_file('/var/run/prosody/prosody.pid')) {
    return(3);
  }

  shell('prosodycmd \'module:reload("'.$mod.'")\' >/dev/null 2>/dev/null', $status);

  return($status == 0 ? TRUE : FALSE);
}

if (is_file($XMPPCONFFILE)) {
  $vars = parseRCconf($XMPPCONFFILE);
} else {
  $vars = NULL;
}
$db = xmppGETclients($vars);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! ($global_admin || $global_staff_enable_xmpp)) {
    $result = 999;
  } elseif (isset($_POST['submit_save'])) {
    $ok = 0;
    $n = arrayCount($db['data']);
    for ($i = 0; $i < $n; $i++) {
      if ($db['data'][$i]['user'] === str_replace(' ', '', stripshellsafe($_POST['user']))) {
        $ok = changeUserPass();
        break;
      }
    }
    if ($ok === 0) {
      $ok = addUserPass();
    }
    $result = saveXMPPsettings($XMPPCONFDIR, $XMPPCONFFILE);
    if ($result == 11 && $ok === 1) {
      $result = 12;
    } elseif ($result == 11 && $ok === 4) {
      $result = 4;
    } elseif ($result == 11 && $ok === TRUE) {
      $result = 15;
    }
  } elseif (isset($_POST['submit_restart'])) {
    $result = 99;
    if (isset($_POST['confirm_restart'])) {
      $result = restartPROCESS('prosody', 10, $result, 'init');
    } else {
      $result = 2;
    }
  } elseif (isset($_POST['submit_delete'])) {
    $ok = 0;
    $delete = $_POST['delete'];
    if (arrayCount($delete) > 0) {
      foreach ($delete as $deluser) {
        if (($ok = deleteUser($deluser)) === FALSE) {
          break;
        }
      }
    }
    $result = saveXMPPsettings($XMPPCONFDIR, $XMPPCONFFILE);
    if ($result == 11 && $ok === FALSE) {
      $result = 13;
    } elseif ($result == 11 && $ok === TRUE) {
      $result = 14;
    }
  } elseif (isset($_POST['submit_reload_groups'])) {
    $result = 99;
    if (reloadModule('groups') === TRUE) {
      $result = 16;
    }
  } elseif (isset($_POST['submit_self_signed_sip_tls'])) {
    $result = saveXMPPsettings($XMPPCONFDIR, $XMPPCONFFILE);
    header('Location: /admin/siptlscert.php');
    exit;
  } elseif (isset($_POST['submit_edit_groups'])) {
    $result = saveXMPPsettings($XMPPCONFDIR, $XMPPCONFFILE);
    if (is_writable($file = '/mnt/kd/prosody/sharedgroups.conf')) {
      header('Location: /admin/edit.php?file='.$file);
      exit;
    }
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} else { // Start of HTTP GET
$ACCESS_RIGHTS = $global_staff_enable_xmpp ? 'staff' : 'admin';
require_once '../common/header.php';

  putHtml('<center>');
  if (isset($_GET['result'])) {
    $result = $_GET['result'];
    if ($result == 2) {
      putHtml('<p style="color: red;">No Action, check "Confirm" for this action.</p>');
    } elseif ($result == 3) {
      putHtml('<p style="color: red;">Error creating file.</p>');
    } elseif ($result == 4) {
      putHtml('<p style="color: red;">Invalid username, specify a JID, including a host. Example: alice@example.com</p>');
    } elseif ($result == 10) {
      putHtml('<p style="color: green;">XMPP Server'.statusPROCESS('prosody').'.</p>');
    } elseif ($result == 11) {
      putHtml('<p style="color: green;">Settings saved'.($global_admin ? ', click "Restart Server" to apply any changed settings.' : '.').'</p>');
    } elseif ($result == 12) {
      putHtml('<p style="color: red;">Missing Password, User not added or changed.</p>');
    } elseif ($result == 13) {
      putHtml('<p style="color: red;">User(s) failed to be deleted.</p>');
    } elseif ($result == 14) {
      putHtml('<p style="color: green;">User(s) successfully deleted.</p>');
    } elseif ($result == 15) {
      putHtml('<p style="color: green;">User successfully added or changed.</p>');
    } elseif ($result == 16) {
      putHtml('<p style="color: green;">Shared Groups Reloaded.</p>');
    } elseif ($result == 99) {
      putHtml('<p style="color: red;">Action Failed.</p>');
    } elseif ($result == 999) {
      putHtml('<p style="color: red;">Permission denied for user "'.$global_user.'".</p>');
    } else {
      putHtml('<p style="color: orange;">No Action.</p>');
    }
  } else {
    putHtml('<p>&nbsp;</p>');
  }
  putHtml('</center>');
?>
  <center>
  <table class="layout"><tr><td><center>
  <form method="post" action="<?php echo $myself;?>">
  <table width="100%" class="stdtable">
  <tr><td style="text-align: center;" colspan="3">
  <h2>XMPP Server Configuration:</h2>
  </td></tr><tr><td style="text-align: center;">
  <input type="submit" class="formbtn" value="Save Settings" name="submit_save" />
  </td><td style="text-align: center;">
  <input type="submit" class="formbtn" value="Restart Server" name="submit_restart" />
  &ndash;
  <input type="checkbox" value="restart" name="confirm_restart" />&nbsp;Confirm
  </td><td style="text-align: center;">
  <input type="submit" class="formbtn" value="Delete Checked" name="submit_delete" />
  </td></tr></table>
<?php

  if (isset($_GET['user'])) {
    $edit_user = $_GET['user'];
  } else {
    $edit_user = '';
  }

  putHtml('<table width="100%" class="stdtable">');
  putHtml('<tr class="dtrow0"><td width="180">&nbsp;</td><td>&nbsp;</td></tr>');

if ($global_admin) {
if ((! is_file('/mnt/kd/ssl/sip-tls/keys/server.crt') || ! is_file('/mnt/kd/ssl/sip-tls/keys/server.key')) &&
    (! is_file('/mnt/kd/prosody/certs/server.crt') || ! is_file('/mnt/kd/prosody/certs/server.key'))) {
  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="2">');
  putHtml('<strong>Missing Server Certificate!</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: center;" colspan="2">');
  putHtml('How to Issue an ACME (Let\'s Encrypt) Certificate:'.includeTOPICinfo('ACME-Certificate'));
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Non-ACME SIP-TLS<br />Server Certificate:');
  putHtml('</td><td style="text-align: left;">');
  putHtml('<input type="submit" value="Self-Signed SIP-TLS Cert" name="submit_self_signed_sip_tls" class="button" />');
  putHtml('</td></tr>');
}
  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="2">');
  putHtml('<strong>XMPP Configuration Options:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('XMPP Server:');
  putHtml('</td><td style="text-align: left;">');
  $xmpp_enable = getVARdef($vars, 'XMPP_ENABLE');
  putHtml('<select name="xmpp_enable">');
  putHtml('<option value="">disabled</option>');
  $sel = ($xmpp_enable === 'yes') ? ' selected="selected"' : '';
  putHtml('<option value="yes"'.$sel.'>enabled</option>');
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Server-to-Server<br />Connections:');
  putHtml('</td><td style="text-align: left;">');
  $xmpp_enable_s2s = getVARdef($vars, 'XMPP_ENABLE_S2S');
  putHtml('<select name="xmpp_enable_s2s">');
  putHtml('<option value="no">disabled</option>');
  $sel = ($xmpp_enable_s2s === 'yes') ? ' selected="selected"' : '';
  putHtml('<option value="yes"'.$sel.'>enabled</option>');
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Log Verbosity:');
  putHtml('</td><td style="text-align: left;">');
  $verbosity = getVARdef($vars, 'XMPP_SYSLOG');
  putHtml('<select name="verbosity">');
  foreach ($verbosity_menu as $key => $value) {
    $sel = ($verbosity === $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Client-to-Server<br />TCP Port:');
  putHtml('</td><td style="text-align: left;">');
  if (($value = getVARdef($vars, 'XMPP_C2S_PORT')) === '') {
    $value = '5222';
  }
  putHtml('<input type="text" size="10" maxlength="6" name="xmpp_c2s_port" value="'.$value.'" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Server-to-Server<br />TCP Port:');
  putHtml('</td><td style="text-align: left;">');
  if (($value = getVARdef($vars, 'XMPP_S2S_PORT')) === '') {
    $value = '5269';
  }
  putHtml('<input type="text" size="10" maxlength="6" name="xmpp_s2s_port" value="'.$value.'" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml(includeTOPICinfo('xmpp-shared-groups').'Shared Groups:');
  putHtml('</td><td style="text-align: left;">');
  $xmpp_groups = getVARdef($vars, 'XMPP_GROUPS');
  putHtml('<select name="xmpp_groups">');
  putHtml('<option value="no">disabled</option>');
  $sel = ($xmpp_groups === 'yes') ? ' selected="selected"' : '';
  putHtml('<option value="yes"'.$sel.'>enabled</option>');
  putHtml('</select>');
  if (is_file('/mnt/kd/prosody/sharedgroups.conf')) {
    putHtml('&ndash;');
    putHtml('<input type="submit" value="Edit Groups" name="submit_edit_groups" class="button" />');
    if ($xmpp_groups === 'yes') {
      putHtml('&ndash;');
      putHtml('<input type="submit" value="Reload" name="submit_reload_groups" class="button" />');
    }
  }
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Server-to-Client<br />Keep Alive Ping:');
  putHtml('</td><td style="text-align: left;">');
  $idle_timeout = getVARdef($vars, 'XMPP_C2S_IDLE_TIMEOUT');
  putHtml('<select name="idle_timeout">');
  foreach ($idle_timeout_menu as $key => $value) {
    $sel = ($idle_timeout === (string)$key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('dead client timeout');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Hostname:');
  putHtml('</td><td style="text-align: left;">');
  if (($hostname = getVARdef($vars, 'XMPP_HOSTNAME')) === '') {
    $hostname = get_HOSTNAME_DOMAIN();
  }
  putHtml('<input type="text" size="56" maxlength="200" name="xmpp_hostname" value="'.$hostname.'" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Admin Users:');
  putHtml('</td><td style="text-align: left;">');
  $value = getVARdef($vars, 'XMPP_ADMIN_USERS');
  putHtml('<input type="text" size="56" maxlength="512" name="xmpp_admin_users" value="'.$value.'" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Enable Additional<br />Modules:');
  putHtml('</td><td style="text-align: left;">');
  $value = getVARdef($vars, 'XMPP_ENABLE_MODULES');
  putHtml('<input type="text" size="56" maxlength="200" name="xmpp_enable_modules" value="'.$value.'" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Disable Default<br />Modules:');
  putHtml('</td><td style="text-align: left;">');
  $value = getVARdef($vars, 'XMPP_DISABLE_MODULES');
  putHtml('<input type="text" size="56" maxlength="200" name="xmpp_disable_modules" value="'.$value.'" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Multi-User Chat<br />Conference:');
  putHtml('</td><td style="text-align: left;">');
  $value = getVARdef($vars, 'XMPP_CONFERENCE');
  putHtml('<input type="text" size="56" maxlength="200" name="xmpp_conference" value="'.$value.'" />');
  putHtml('</td></tr>');
if ($value === '') {
  putHtml('<tr class="dtrow1"><td style="text-align: right;"><i>Example:</i></td><td style="text-align: left;">');
  $hosts = explode(' ', $hostname);
  putHtml('<i>conference.'.$hosts[0].'</i>');
  putHtml('</td></tr>');
}

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('PubSub Service:');
  putHtml('</td><td style="text-align: left;">');
  $value = getVARdef($vars, 'XMPP_PUBSUB');
  putHtml('<input type="text" size="56" maxlength="200" name="xmpp_pubsub" value="'.$value.'" />');
  putHtml('</td></tr>');
if ($value === '') {
  putHtml('<tr class="dtrow1"><td style="text-align: right;"><i>Example:</i></td><td style="text-align: left;">');
  $hosts = explode(' ', $hostname);
  putHtml('<i>pubsub.'.$hosts[0].'</i>');
  putHtml('</td></tr>');
}
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('PubSub Admins:');
  putHtml('</td><td style="text-align: left;">');
  $value = getVARdef($vars, 'XMPP_PUBSUB_ADMINS');
  putHtml('<input type="text" size="56" maxlength="512" name="xmpp_pubsub_admins" value="'.$value.'" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('PubSub Autocreate:');
  putHtml('</td><td style="text-align: left;">');
  $pubsub_autocreate = getVARdef($vars, 'XMPP_PUBSUB_AUTOCREATE');
  putHtml('<select name="xmpp_pubsub_autocreate">');
  foreach ($pubsub_autocreate_menu as $key => $value) {
    $sel = ($pubsub_autocreate === $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');
} // if global_admin

if (is_file('/mnt/kd/prosody/prosody.cfg.lua')) {
  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="2">');
  putHtml('<strong>Client Credentials:</strong>');
  putHtml('</td></tr>');
  putHtml('<tr><td style="text-align: right;">');
  putHtml('Username:');
  putHtml('</td><td style="text-align: left;">');
  putHtml('<input type="text" size="46" maxlength="128" name="user" value="'.$edit_user.'" />');
  putHtml('</td></tr>');
  putHtml('<tr><td style="text-align: right;">');
  putHtml('Password:');
  putHtml('</td><td style="text-align: left;">');
  putHtml('<input type="password" size="46" maxlength="128" name="pass" value="" />');
  putHtml('</td></tr>');
  putHtml('</table>');

  putHtml('<table width="66%" class="datatable">');
  putHtml("<tr>");

  if (($n = arrayCount($db['data'])) > 0) {
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Users", "</td>";
    echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Delete", "</td>";
    for ($i = 0; $i < $n; $i++) {
      putHtml("</tr>");
      echo '<tr ', ($i % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
      echo '<td><a href="'.$myself.'?user='.$db['data'][$i]['user'].'" class="actionText">'.$db['data'][$i]['user'].'</a>', '</td>';
      echo '<td style="text-align: center;">', '<input type="checkbox" name="delete[]" value="', $db['data'][$i]['user'], '" />', '</td>';
    }
  } else {
    echo '<td style="color: orange; text-align: center;">No Client Credentials.', '</td>';
  }
  putHtml("</tr>");
}
  putHtml("</table>");
  putHtml("</form>");
  putHtml("</center></td></tr></table>");
  putHtml("</center>");
} // End of HTTP GET
require_once '../common/footer.php';

?>
