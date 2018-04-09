<?php

// Copyright (C) 2013-2017 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// slapd.php for AstLinux
// 10-21-2013
//
// System location of rc.conf file
$CONFFILE = '/etc/rc.conf';
// System location of /mnt/kd/rc.conf.d directory
$SLAPDCONFDIR = '/mnt/kd/rc.conf.d';
// System location of gui.slapd.conf file
$SLAPDCONFFILE = '/mnt/kd/rc.conf.d/gui.slapd.conf';

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

$anonymous_menu = array (
  'localhost' => 'localhost only',
  'yes' => 'access enabled',
  'no' => 'access disabled'
);

$users_menu = array (
  'directory' => 'cn=directory,ou=users',
  'staff' => 'cn=staff,ou=users'
);

// Function: saveSLAPDsettings
//
function saveSLAPDsettings($conf_dir, $conf_file) {
  $result = 11;

  if (! is_dir($conf_dir)) {
    return(3);
  }
  if (($fp = @fopen($conf_file,"wb")) === FALSE) {
    return(3);
  }
  fwrite($fp, "### gui.slapd.conf - start ###\n###\n");

  $value = 'LDAP_SERVER="'.$_POST['slapd_enabled'].'"';
  fwrite($fp, "### LDAP Server Enabled\n".$value."\n");

  $value = 'LDAP_SERVER_ANONYMOUS="'.$_POST['slapd_anonymous'].'"';
  fwrite($fp, "### LDAP Server Anonymous\n".$value."\n");

  $value = 'LDAP_SERVER_BASEDN="'.tuq($_POST['slapd_basedn']).'"';
  fwrite($fp, "### LDAP Server Base DN\n".$value."\n");

  $value = 'LDAP_SERVER_PASS="'.string2RCconfig(trim($_POST['slapd_admin_pass'])).'"';
  fwrite($fp, "### LDAP Server Password\n".$value."\n");

  fwrite($fp, "### gui.slapd.conf - end ###\n");
  fclose($fp);

  return($result);
}

// Function set_LDAP_user_passwd()
//
function set_LDAP_user_passwd($rootpw, $pass1, $pass2, $user, $minlen) {
  $result = 1;

  if ($rootpw !== '') {
    if (strlen($pass1) > $minlen) {
      if ($pass1 === $pass2) {
        $result = 21;
        $admin = tempnam("/var/tmp", "PHP_");
        $newpass = tempnam("/var/tmp", "PHP_");
        $cmd = '. /etc/rc.conf; ';
        $cmd .= '/usr/bin/ldappasswd -x -D "cn=admin,${LDAP_SERVER_BASEDN:-dc=ldap}" -H ldap://127.0.0.1 ';
        $cmd .= '-y '.$admin.' -T '.$newpass.' "cn='.$user.',ou=users,${LDAP_SERVER_BASEDN:-dc=ldap}"';
        @file_put_contents($admin, $rootpw);
        @file_put_contents($newpass, $pass1);
        shell($cmd.' >/dev/null 2>/dev/null', $status);
        @unlink($newpass);
        @unlink($admin);
        if ($status == 0) {
          syslog(LOG_WARNING, 'LDAP "cn='.$user.',ou=users" password changed.  Remote Address: '.$_SERVER['REMOTE_ADDR']);
          $result = 20;
        }
      } else {
        $result = 22;
      }
    } else {
      $result = 23;
    }
  } else {
    $result = 24;
  }
  return($result);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! $global_admin) {
    $result = 999;
  } elseif (isset($_POST['submit_save']) || isset($_POST['submit_password'])) {
    $result = saveSLAPDsettings($SLAPDCONFDIR, $SLAPDCONFFILE);
    if (isset($_POST['rootpw'], $_POST['pass1'], $_POST['pass2'])) {
      $rootpw = tuqd($_POST['rootpw']);
      $pass1 = tuqd($_POST['pass1']);
      $pass2 = tuqd($_POST['pass2']);
      if (isset($_POST['submit_password']) || ($pass1 !== '' && $pass2 !== '')) {
        if (($user = $_POST['username']) !== '') {
          $result = set_LDAP_user_passwd($rootpw, $pass1, $pass2, $user, 3);
        }
      }
    }
  } elseif (isset($_POST['submit_restart'])) {
    $result = 99;
    if (isset($_POST['confirm_restart'])) {
      $result = restartPROCESS('slapd', 10, $result, 'init');
    } else {
      $result = 2;
    }
  } elseif (isset($_POST['submit_self_signed_sip_tls'])) {
    $result = saveSLAPDsettings($SLAPDCONFDIR, $SLAPDCONFFILE);
    header('Location: /admin/siptlscert.php');
    exit;
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} else { // Start of HTTP GET
$ACCESS_RIGHTS = 'admin';
require_once '../common/header.php';

  if (is_file($SLAPDCONFFILE)) {
    $db = parseRCconf($SLAPDCONFFILE);
  } else {
    $db = NULL;
  }

  putHtml("<center>");
  if (isset($_GET['result'])) {
    $result = $_GET['result'];
    if ($result == 2) {
      putHtml('<p style="color: red;">No Action, check "Confirm" for this action.</p>');
    } elseif ($result == 3) {
      putHtml('<p style="color: red;">Error creating file.</p>');
    } elseif ($result == 10) {
      putHtml('<p style="color: green;">LDAP Server'.statusPROCESS('slapd').'.</p>');
    } elseif ($result == 11) {
      putHtml('<p style="color: green;">Settings saved, click "Restart LDAP" to apply any changed settings.</p>');
    } elseif ($result == 20) {
      putHtml('<p style="color: green;">"ou=users" password successfully changed.</p>');
    } elseif ($result == 21) {
      putHtml('<p style="color: red;">"ou=users" password failed to be changed.</p>');
    } elseif ($result == 22) {
      putHtml('<p style="color: red;">Passwords do not match.</p>');
    } elseif ($result == 23) {
      putHtml('<p style="color: red;">Password too short.</p>');
    } elseif ($result == 24) {
      putHtml('<p style="color: red;">"cn=admin" Password not specified.</p>');
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
  putHtml("</center>");
?>
  <center>
  <table class="layout"><tr><td><center>
  <form method="post" action="<?php echo $myself;?>">
  <table width="100%" class="stdtable">
  <tr><td style="text-align: center;" colspan="2">
  <h2>LDAP Server Configuration:</h2>
  </td></tr><tr><td width="240" style="text-align: center;">
  <input type="submit" class="formbtn" value="Save Settings" name="submit_save" />
  </td><td class="dialogText" style="text-align: center;">
  <input type="submit" class="formbtn" value="Restart LDAP" name="submit_restart" />
  &ndash;
  <input type="checkbox" value="restart" name="confirm_restart" />&nbsp;Confirm
  </td></tr></table>
  <table class="stdtable">
  <tr class="dtrow0"><td width="60">&nbsp;</td><td width="100">&nbsp;</td><td width="50">&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td width="60">&nbsp;</td></tr>
<?php
if ((! is_file('/mnt/kd/ssl/sip-tls/keys/server.crt') || ! is_file('/mnt/kd/ssl/sip-tls/keys/server.key')) &&
    (! is_file('/mnt/kd/ldap/certs/server.crt') || ! is_file('/mnt/kd/ldap/certs/server.key'))) {
  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Missing Server Certificate!</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: center;" colspan="6">');
  putHtml('How to Issue an ACME (Let\'s Encrypt) Certificate:'.includeTOPICinfo('ACME-Certificate'));
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Non-ACME SIP-TLS<br />Server Certificate:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  putHtml('<input type="submit" value="Self-Signed SIP-TLS Cert" name="submit_self_signed_sip_tls" class="button" />');
  putHtml('</td></tr>');
}

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>LDAP Directory Server:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('LDAP Server:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $slapd_enable = getVARdef($db, 'LDAP_SERVER');
  putHtml('<select name="slapd_enabled">');
  putHtml('<option value="no">disabled</option>');
  $sel = ($slapd_enable === 'yes') ? ' selected="selected"' : '';
  putHtml('<option value="yes"'.$sel.'>enabled</option>');
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Anonymous Read-Only:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $anonymous = getVARdef($db, 'LDAP_SERVER_ANONYMOUS');
  putHtml('<select name="slapd_anonymous">');
  foreach ($anonymous_menu as $key => $value) {
    $sel = ($anonymous === $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Base DN:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  if (($value = getVARdef($db, 'LDAP_SERVER_BASEDN')) === '') {
    $value = 'dc=ldap';
  }
  putHtml('<input type="text" size="56" maxlength="128" name="slapd_basedn" value="'.$value.'" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Admin Password<br />"cn=admin":');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $rootpw = getVARdef($db, 'LDAP_SERVER_PASS');
  $rootpw = htmlspecialchars(RCconfig2string($rootpw));
  putHtml('<input type="password" size="56" maxlength="128" name="slapd_admin_pass" value="'.$rootpw.'" />');
  putHtml('<i><br />(defaults to web interface "admin" password)</i>');
  putHtml('</td></tr>');

if (is_file('/var/run/slapd/slapd.pid')) {
  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Set "ou=users" Passwords:</strong>');
  putHtml('</td></tr>');
  putHtml('<tr><td style="text-align: right;" colspan="2">');
  putHtml('"cn=admin" Password:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  if ($rootpw !== '') {
    putHtml('**********');
    putHtml('<input type="hidden" name="rootpw" value="'.$rootpw.'" />');
  } else {
    putHtml('<input type="password" size="18" maxlength="128" name="rootpw" value="'.$rootpw.'" />');
  }
  putHtml('</td></tr>');
  putHtml('<tr><td style="text-align: right;" colspan="2">');
  putHtml('New Password:');
  putHtml('</td><td style="text-align: left;" colspan="2">');
  putHtml('<input type="password" size="18" maxlength="128" name="pass1" value="" />');
  putHtml('</td><td style="text-align: center;" colspan="2">');
  putHtml('<select name="username">');
  foreach ($users_menu as $key => $value) {
    putHtml('<option value="'.$key.'">'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');
  putHtml('<tr><td style="text-align: right;" colspan="2">');
  putHtml('Confirm Password:');
  putHtml('</td><td style="text-align: left;" colspan="2">');
  putHtml('<input type="password" size="18" maxlength="128" name="pass2" value="" />');
  putHtml('</td><td style="text-align: center;" colspan="2">');
  putHtml('<input type="submit" value="Set Password" name="submit_password" />');
  putHtml('</td></tr>');
}

  putHtml('</table>');
  putHtml('</form>');

  putHtml('</center></td></tr></table>');
  putHtml('</center>');
} // End of HTTP GET
require_once '../common/footer.php';

?>
