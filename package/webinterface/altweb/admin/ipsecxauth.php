<?php

// Copyright (C) 2012 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// ipsecxauth.php for AstLinux
// 16-04-2012
//
// System location of /mnt/kd/rc.conf.d directory
$IPSECXAUTHCONFDIR = '/mnt/kd/rc.conf.d';
// System location of gui.ipsecxauth.conf file
$IPSECXAUTHCONFFILE = '/mnt/kd/rc.conf.d/gui.ipsecxauth.conf';

$connections_menu = array (
  '2' => '2 Users',
  '4' => '4 Users',
  '8' => '8 Users',
  '16' => '16 Users',
  '32' => '32 Users',
  '64' => '64 Users'
);

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

// Function: ipsecGETclients
//
function ipsecGETclients($vars) {
  $id = 0;

  if (($line = getVARdef($vars, 'IPSECM_XAUTH_USER_PASS')) !== '') {
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

// Function: saveIPSECsettings
//
function saveIPSECsettings($conf_dir, $conf_file, $db, $delete = NULL) {
  $result = 11;

  if (! is_dir($conf_dir)) {
    return(3);
  }
  if (($fp = @fopen($conf_file,"wb")) === FALSE) {
    return(3);
  }
  fwrite($fp, "### gui.ipsecxauth.conf - start ###\n###\n");

  $value = 'IPSECM_XAUTH_USER_PASS="';
  fwrite($fp, "### Authentication\n".$value."\n");
  if (arrayCount($db['data']) > 0) {
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

  $value = 'IPSECM_XAUTH_POOLSIZE="'.$_POST['pool_size'].'"';
  fwrite($fp, "### Pool Size\n".$value."\n");

  $value = 'IPSECM_XAUTH_POOLBASE="'.tuq($_POST['pool_base']).'"';
  fwrite($fp, "### Pool Base\n".$value."\n");

  $value = 'IPSECM_XAUTH_POOLMASK="'.tuq($_POST['pool_mask']).'"';
  fwrite($fp, "### Pool Mask\n".$value."\n");

  $value = 'IPSECM_XAUTH_DNS="'.tuq($_POST['dns']).'"';
  fwrite($fp, "### MS DNS\n".$value."\n");

  $value = 'IPSECM_XAUTH_WINS="'.tuq($_POST['wins']).'"';
  fwrite($fp, "### MS WINS\n".$value."\n");

  $value = 'IPSECM_XAUTH_NETWORK="'.tuq($_POST['network']).'"';
  fwrite($fp, "### Network\n".$value."\n");

  $value = 'IPSECM_XAUTH_DOMAIN="'.tuq($_POST['domain']).'"';
  fwrite($fp, "### Default Domain\n".$value."\n");

  $value = 'IPSECM_XAUTH_BANNER="'.tuq($_POST['banner']).'"';
  fwrite($fp, "### Login Message\n".$value."\n");

  $value = 'IPSECM_XAUTH_SAVE_PASSWD="'.$_POST['save_passwd'].'"';
  fwrite($fp, "### Save Password\n".$value."\n");

  fwrite($fp, "### gui.ipsecxauth.conf - end ###\n");
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

if (is_file($IPSECXAUTHCONFFILE)) {
  $vars = parseRCconf($IPSECXAUTHCONFFILE);
} else {
  $vars = NULL;
}
$db = ipsecGETclients($vars);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! $global_admin) {
    $result = 999;
  } elseif (isset($_POST['submit_save']) || isset($_POST['submit_ipsec_config'])) {
    $n = arrayCount($db['data']);
    $id = $n;
    for ($i = 0; $i < $n; $i++) {
      if ($db['data'][$i]['user'] === str_replace(' ', '', stripshellsafe($_POST['user']))) {
        $id = $i;
        break;
      }
    }
    $ok = addUserPass($db, $id);
    $result = saveIPSECsettings($IPSECXAUTHCONFDIR, $IPSECXAUTHCONFFILE, $db);
    if ($result == 11 && $ok === 1) {
      $result = 12;
    }
    if (isset($_POST['submit_ipsec_config'])) {
      header('Location: /admin/ipsecmobile.php');
      exit;
    }
  } elseif (isset($_POST['submit_delete'])) {
    $delete = $_POST['delete'];
    if (arrayCount($delete) > 0) {
      $result = saveIPSECsettings($IPSECXAUTHCONFDIR, $IPSECXAUTHCONFFILE, $db, $delete);
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
    } elseif ($result == 11) {
      putHtml('<p style="color: green;">Settings saved, click "IPsec Configuration" to return to previous screen.</p>');
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
  <h2>IPsec XAuth Configuration:</h2>
  </td></tr><tr><td style="text-align: center;">
  <input type="submit" class="formbtn" value="Save Settings" name="submit_save" />
  </td><td style="text-align: center;">
  <input type="submit" value="IPsec Configuration" name="submit_ipsec_config" class="button" />
  </td><td style="text-align: center;">
  <input type="submit" class="formbtn" value="Delete Checked" name="submit_delete" />
  </td></tr></table>
<?php

  if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $n = arrayCount($db['data']);
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
  putHtml('<strong>XAuth Client Options:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Max. Connections:');
  putHtml('</td><td style="text-align: left;">');
  if (($pool_size = getVARdef($vars, 'IPSECM_XAUTH_POOLSIZE')) === '') {
    $pool_size = '8';
  }
  putHtml('<select name="pool_size">');
  foreach ($connections_menu as $key => $value) {
    $sel = ($pool_size === (string)$key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Remote IPv4 Base:');
  putHtml('</td><td style="text-align: left;">');
  if (($value = getVARdef($vars, 'IPSECM_XAUTH_POOLBASE')) === '') {
    $value = '10.9.1.1';
  }
  putHtml('<input type="text" size="36" maxlength="64" name="pool_base" value="'.$value.'" />');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Remote IPv4 Mask:');
  putHtml('</td><td style="text-align: left;">');
  if (($value = getVARdef($vars, 'IPSECM_XAUTH_POOLMASK')) === '') {
    $value = '255.255.255.0';
  }
  putHtml('<input type="text" size="36" maxlength="64" name="pool_mask" value="'.$value.'" />');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('DNS Default Domain:');
  putHtml('</td><td style="text-align: left;">');
  $value = getVARdef($vars, 'IPSECM_XAUTH_DOMAIN');
  putHtml('<input type="text" size="36" maxlength="128" name="domain" value="'.$value.'" />');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('DNS:');
  putHtml('</td><td style="text-align: left;">');
  $value = getVARdef($vars, 'IPSECM_XAUTH_DNS');
  putHtml('<input type="text" size="56" maxlength="128" name="dns" value="'.$value.'" />');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('WINS:');
  putHtml('</td><td style="text-align: left;">');
  $value = getVARdef($vars, 'IPSECM_XAUTH_WINS');
  putHtml('<input type="text" size="56" maxlength="128" name="wins" value="'.$value.'" />');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Push Network(s):');
  putHtml('</td><td style="text-align: left;">');
  $value = getVARdef($vars, 'IPSECM_XAUTH_NETWORK');
  putHtml('<input type="text" size="56" maxlength="128" name="network" value="'.$value.'" />');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Connect Message:');
  putHtml('</td><td style="text-align: left;">');
  $value = getVARdef($vars, 'IPSECM_XAUTH_BANNER');
  putHtml('<input type="text" size="56" maxlength="200" name="banner" value="'.$value.'" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Save Remote Password:');
  putHtml('</td><td style="text-align: left;">');
  putHtml('<select name="save_passwd">');
  $value = getVARdef($vars, 'IPSECM_XAUTH_SAVE_PASSWD');
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

  if (($n = arrayCount($db['data'])) > 0) {
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
