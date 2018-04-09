<?php

// Copyright (C) 2011 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// pptp.php for AstLinux
// 23-02-2011
//
// System location of /mnt/kd/rc.conf.d directory
$PPTPCONFDIR = '/mnt/kd/rc.conf.d';
// System location of gui.pptp.conf file
$PPTPCONFFILE = '/mnt/kd/rc.conf.d/gui.pptp.conf';

$verbosity_menu = array (
  '1' => 'Low',
  '2' => 'Medium',
  '3' => 'High'
);

$connections_menu = array (
  '16' => '16 &ndash; [x.x.x.224-239] [x.x.x.224/28]',
  '8' => '8 &ndash; [x.x.x.232-239] [x.x.x.232/29]',
  '4' => '4 &ndash; [x.x.x.236-239] [x.x.x.236/30]',
  '2' => '2 &ndash; [x.x.x.238-239] [x.x.x.238/31]',
  '1' => '1 &ndash; [x.x.x.239] [x.x.x.239/32]'
);

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

// Function: getPOOLvars
//
function getPOOLvars($var) {

  if ($var !== '') {
    $datatokens = explode(' ', $var);
    if (isset($datatokens[0], $datatokens[1], $datatokens[2])) {
      $pool['num'] = $datatokens[0];
      $pool['remote'] = $datatokens[1];
      $pool['server'] = $datatokens[2];
      return($pool);
    }
  }
  // Set defaults
  // System location of gui.network.conf file
  $NETCONFFILE = '/mnt/kd/rc.conf.d/gui.network.conf';

  $pool['num'] = '8';
  $pool['remote'] = '192.168.101.232-239';
  $pool['server'] = '192.168.101.240';
  if (is_file($NETCONFFILE)) {
    $netvars = parseRCconf($NETCONFFILE);
    if (($intip = getVARdef($netvars, 'INTIP')) !== '') {
      $ip4tokens = explode('.', $intip);
      if (isset($ip4tokens[0], $ip4tokens[1], $ip4tokens[2])) {
        $ip4 = "$ip4tokens[0].$ip4tokens[1].$ip4tokens[2]";
        $pool['remote'] = "$ip4.232-239";
        $pool['server'] = "$ip4.240";
      }
    }
  }
  return($pool);
}

// Function: pptpGETclients
//
function pptpGETclients($vars) {
  $id = 0;

  if (($line = getVARdef($vars, 'PPTP_USER_PASS')) !== '') {
    $linetokens = explode("\n", $line);
    foreach ($linetokens as $data) {
      if ($data !== '') {
        $datatokens = explode(' ', $data);
        $db['data'][$id]['user'] = $datatokens[0];
        $db['data'][$id]['pass'] = $datatokens[1];
        $id++;
      }
    }
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

// Function: savePPTPsettings
//
function savePPTPsettings($conf_dir, $conf_file, $db, $delete = NULL) {
  $result = 11;

  if (! is_dir($conf_dir)) {
    return(3);
  }
  if (($fp = @fopen($conf_file,"wb")) === FALSE) {
    return(3);
  }
  fwrite($fp, "### gui.pptp.conf - start ###\n###\n");

  $value = 'PPTP_USER_PASS="';
  fwrite($fp, "### Authentication\n".$value."\n");
  if (count($db['data']) > 0) {
    foreach ($db['data'] as $data) {
      if ($data['user'] !== '' && $data['pass'] !== '') {
        $skip = FALSE;
        if (! is_null($delete)) {
          foreach ($delete as $deluser) {
            if ($deluser === $data['user']) {
              $skip = TRUE;
              break;
            }
          }
        }
        if (! $skip) {
          fwrite($fp, $data['user'].' '.$data['pass']."\n");
        }
      }
    }
  }
  fwrite($fp, '"'."\n");

  $pool = $_POST['pool_num'];
  if (($value = str_replace(' ', '', tuq($_POST['pool_remote']))) !== '') {
    $pool .= ' '.$value;
    if (($value = str_replace(' ', '', tuq($_POST['pool_server']))) !== '') {
      $pool .= ' '.$value;
    } else {
      $pool = '';
    }
  } else {
    $pool = '';
  }
  $value = 'PPTP_POOL="'.$pool.'"';
  fwrite($fp, "### PPTP Address Pool\n".$value."\n");

  if ($pool !== '') {
    $value = 'PPTP_SUBNET="'.str_replace(' ', '', tuq($_POST['subnet'])).'"';
  } else {
    $value = 'PPTP_SUBNET=""';
  }
  fwrite($fp, "### Routed PPTP Subnet\n".$value."\n");

  $value = 'PPTP_VERBOSITY="'.$_POST['verbosity'].'"';
  fwrite($fp, "### Log Verbosity\n".$value."\n");

  $value = 'PPTP_DNS="'.tuq($_POST['dns']).'"';
  fwrite($fp, "### MS DNS\n".$value."\n");

  $value = 'PPTP_WINS="'.tuq($_POST['wins']).'"';
  fwrite($fp, "### MS WINS\n".$value."\n");

  $value = 'PPTP_TUNNEL_EXTERNAL_HOSTS="'.tuq($_POST['tunnel_external_hosts']).'"';
  fwrite($fp, "### Allow External Hosts for Tunnel\n".$value."\n");

  $value = 'PPTP_ALLOW_HOSTS="'.tuq($_POST['allow_hosts']).'"';
  fwrite($fp, "### Allow Hosts\n".$value."\n");

  $value = 'PPTP_DENY_HOSTS="'.tuq($_POST['deny_hosts']).'"';
  fwrite($fp, "### Deny Hosts\n".$value."\n");

  $value = 'PPTP_DENY_LOG="'.$_POST['deny_log'].'"';
  fwrite($fp, "### Log Deny Hosts\n".$value."\n");

  fwrite($fp, "### gui.pptp.conf - end ###\n");
  fclose($fp);

  return($result);
}

// Function: addUserPass
//
function addUserPass(&$db, $id) {

  $user = str_replace(' ', '', stripshellsafe($_POST['user']));
  $pass = str_replace(' ', '', stripshellsafe($_POST['pass']));

  if ($user === '') {
    return(FALSE);
  }
  if ($pass === '') {
    return(1);
  }

  $db['data'][$id]['user'] = $user;
  $db['data'][$id]['pass'] = $pass;

  return(TRUE);
}

if (is_file($PPTPCONFFILE)) {
  $vars = parseRCconf($PPTPCONFFILE);
} else {
  $vars = NULL;
}
$db = pptpGETclients($vars);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! $global_admin) {
    $result = 999;
  } elseif (isset($_POST['submit_save'])) {
    $n = count($db['data']);
    $id = $n;
    for ($i = 0; $i < $n; $i++) {
      if ($db['data'][$i]['user'] === str_replace(' ', '', stripshellsafe($_POST['user']))) {
        $id = $i;
        break;
      }
    }
    $ok = addUserPass($db, $id);
    $result = savePPTPsettings($PPTPCONFDIR, $PPTPCONFFILE, $db);
    if ($result == 11 && $ok === 1) {
      $result = 12;
    }
  } elseif (isset($_POST['submit_restart'])) {
    $result = 99;
    if (isset($_POST['confirm_restart'])) {
      $result = restartPROCESS('pptpd', 10, $result, 'init');
    } else {
      $result = 2;
    }
  } elseif (isset($_POST['submit_delete'])) {
    $delete = $_POST['delete'];
    if (count($delete) > 0) {
      $result = savePPTPsettings($PPTPCONFDIR, $PPTPCONFFILE, $db, $delete);
    }
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} else { // Start of HTTP GET
$ACCESS_RIGHTS = 'admin';
require_once '../common/header.php';

  putHtml('<center>');
  if (isset($_GET['result'])) {
    $result = $_GET['result'];
    if ($result == 2) {
      putHtml('<p style="color: red;">No Action, check "Confirm" for this action.</p>');
    } elseif ($result == 3) {
      putHtml('<p style="color: red;">Error creating file.</p>');
    } elseif ($result == 10) {
      putHtml('<p style="color: green;">PPTP VPN Server'.statusPROCESS('pptpd').'.</p>');
    } elseif ($result == 11) {
      putHtml('<p style="color: green;">Settings saved, click "Restart Server" to apply any changed settings.</p>');
    } elseif ($result == 12) {
      putHtml('<p style="color: red;">Missing Password, User not added.</p>');
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
  <h2>PPTP VPN Server Configuration:</h2>
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

  if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $n = count($db['data']);
    for ($i = 0; $i < $n; $i++) {
      if ($id === $db['data'][$i]['user']) {
        $ldb = $db['data'][$i];
        break;
      }
    }
  }
  if (is_null($ldb)) {
    $ldb['user'] = '';
    $ldb['pass'] = '';
  }

  putHtml('<table width="100%" class="stdtable">');
  putHtml('<tr class="dtrow0"><td width="180">&nbsp;</td><td>&nbsp;</td></tr>');
  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="2">');
  putHtml('<strong>Tunnel Options:</strong>');
  putHtml('</td></tr>');

  $pool = getPOOLvars(getVARdef($vars, 'PPTP_POOL'));

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Max. Connections:');
  putHtml('</td><td style="text-align: left;">');
  $connections = $pool['num'];
  putHtml('<select name="pool_num">');
  foreach ($connections_menu as $key => $value) {
    $sel = ($connections === (string)$key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Remote Range IPv4:');
  putHtml('</td><td style="text-align: left;">');
  $value = $pool['remote'];
  putHtml('<input type="text" size="36" maxlength="64" name="pool_remote" value="'.$value.'" />');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('VPN Subnet:');
  putHtml('</td><td style="text-align: left;">');
  if (($value = getVARdef($vars, 'PPTP_SUBNET')) === '') {
    $cidr = array('16' => '/28', '8' => '/29', '4' => '/30', '2' => '/31', '1' => '/32');
    if (($pos = strpos($pool['remote'], '-')) !== FALSE) {
      $value = substr($pool['remote'], 0, $pos).$cidr[$pool['num']];
    } else {
      $value = $pool['remote'].$cidr[$pool['num']];
    }
  }
  putHtml('<input type="text" size="36" maxlength="64" name="subnet" value="'.$value.'" />');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Server IPv4:');
  putHtml('</td><td style="text-align: left;">');
  $value = $pool['server'];
  putHtml('<input type="text" size="36" maxlength="64" name="pool_server" value="'.$value.'" />');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('DNS:');
  putHtml('</td><td style="text-align: left;">');
  $value = getVARdef($vars, 'PPTP_DNS');
  putHtml('<input type="text" size="36" maxlength="64" name="dns" value="'.$value.'" />');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('WINS:');
  putHtml('</td><td style="text-align: left;">');
  $value = getVARdef($vars, 'PPTP_WINS');
  putHtml('<input type="text" size="36" maxlength="64" name="wins" value="'.$value.'" />');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Log Verbosity:');
  putHtml('</td><td style="text-align: left;">');
  $verbosity = getVARdef($vars, 'PPTP_VERBOSITY');
  putHtml('<select name="verbosity">');
  foreach ($verbosity_menu as $key => $value) {
    $sel = ($verbosity === (string)$key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="2">');
  putHtml('<strong>Firewall Options:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('External Hosts:');
  putHtml('</td><td style="text-align: left;">');
  if (($value = getVARdef($vars, 'PPTP_TUNNEL_EXTERNAL_HOSTS')) === '') {
    $value = '0/0';
  }
  putHtml('<input type="text" size="56" maxlength="200" name="tunnel_external_hosts" value="'.$value.'" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td class="dialogText" style="text-align: center;" colspan="2">');
  putHtml('<i>Default Policy is to allow after any "Allow/Deny" Hosts rules</i>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Allow Hosts:');
  putHtml('</td><td style="text-align: left;">');
  $value = getVARdef($vars, 'PPTP_ALLOW_HOSTS');
  putHtml('<input type="text" size="56" maxlength="200" name="allow_hosts" value="'.$value.'" />');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Deny Hosts:');
  putHtml('</td><td style="text-align: left;">');
  $value = getVARdef($vars, 'PPTP_DENY_HOSTS');
  putHtml('<input type="text" size="56" maxlength="200" name="deny_hosts" value="'.$value.'" />');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Log Denied:');
  putHtml('</td><td style="text-align: left;">');
  putHtml('<select name="deny_log">');
  $value = getVARdef($vars, 'PPTP_DENY_LOG');
  $sel = ($value === 'no') ? ' selected="selected"' : '';
  putHtml('<option value="no"'.$sel.'>No</option>');
  $sel = ($value === 'yes') ? ' selected="selected"' : '';
  putHtml('<option value="yes"'.$sel.'>Yes</option>');
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="2">');
  putHtml('<strong>Client Credentials:</strong>');
  putHtml('</td></tr>');
  putHtml('<tr><td style="text-align: right;">');
  putHtml('Username:');
  putHtml('</td><td style="text-align: left;">');
  putHtml('<input type="text" size="36" maxlength="64" name="user" value="'.$ldb['user'].'" />');
  putHtml('</td></tr>');
  putHtml('<tr><td style="text-align: right;">');
  putHtml('Password:');
  putHtml('</td><td style="text-align: left;">');
  putHtml('<input type="password" size="36" maxlength="128" name="pass" value="'.$ldb['pass'].'" />');
  putHtml('</td></tr>');
  putHtml('</table>');

  putHtml('<table width="66%" class="datatable">');
  putHtml("<tr>");

  if (($n = count($db['data'])) > 0) {
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Users", "</td>";
    echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Delete", "</td>";
    for ($i = 0; $i < $n; $i++) {
      putHtml("</tr>");
      echo '<tr ', ($i % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
      echo '<td><a href="'.$myself.'?id='.$db['data'][$i]['user'].'" class="actionText">'.$db['data'][$i]['user'].'</a>', '</td>';
      echo '<td style="text-align: center;">', '<input type="checkbox" name="delete[]" value="', $db['data'][$i]['user'], '" />', '</td>';
    }
  } else {
    echo '<td style="color: orange; text-align: center;">No Client Credentials.', '</td>';
  }
  putHtml("</tr>");
  putHtml("</table>");
  putHtml("</form>");
  putHtml("</center></td></tr></table>");
  putHtml("</center>");
} // End of HTTP GET
require_once '../common/footer.php';

?>
