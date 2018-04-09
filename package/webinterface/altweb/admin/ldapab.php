<?php

// Copyright (C) 2008-2013 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// ldapad.php for AstLinux
// 10-26-2013
//
// System location of rc.conf file
$CONFFILE = '/etc/rc.conf';

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

require_once '../common/vcard-parse-convert.php';

$action_menu = array (
  '' => '&ndash; select action &ndash;',
  'export' => 'Export as LDIF',
  'revert' => 'Revert to Previous'
);

// Function: exportLDIF
//
function exportLDIF($rootpw, $ou) {
  global $global_prefs;

  if ($rootpw === '') {
    return(10);
  }

  if (($backup_name = get_HOSTNAME_DOMAIN()) === '') {
    $backup_name = $_SERVER['SERVER_NAME'];
  }
  if (getPREFdef($global_prefs, 'system_backup_hostname_domain') !== 'yes') {
    if (($pos = strpos($backup_name, '.')) !== FALSE) {
      $backup_name = substr($backup_name, 0, $pos);
    }
  }
  $prefix = '/mnt/kd/.';
  $tmpfile = $backup_name.'-'.$ou.'-'.date('Y-m-d').'.ldif.txt';
  $bdn = trim(shell_exec('. /etc/rc.conf; echo "${LDAP_SERVER_BASEDN:-dc=ldap}"'));

  $admin = tempnam("/var/tmp", "PHP_");
  $auth = '-x -D "cn=admin,'.$bdn.'" -H ldap://127.0.0.1 -y '.$admin;
  $cmd = '/usr/bin/ldapsearch '.$auth.' -b "ou='.$ou.','.$bdn.'" -LLL';
  @file_put_contents($admin, $rootpw);
  shell($cmd.' >'.$prefix.$tmpfile.' 2>/dev/null', $status);
  @unlink($admin);

  if ($status != 0) {
    @unlink($prefix.$tmpfile);
    return(($status == 49) ? 28 : 29);
  } else {
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="'.$tmpfile.'"');
    header('Content-Length: '.filesize($prefix.$tmpfile));
    ob_clean();
    flush();
    @readfile($prefix.$tmpfile);
    @unlink($prefix.$tmpfile);
    exit;
  }
}

// Function: revertLDIF
//
function revertLDIF($rootpw, $ou) {

  $bdn = trim(shell_exec('. /etc/rc.conf; echo "${LDAP_SERVER_BASEDN:-dc=ldap}"'));
  $ou_old = $ou.'-old';
  $ou_tmp = $ou.'-tmp';

  if ($rootpw === '') {
    return(10);
  }

  $admin = tempnam("/var/tmp", "PHP_");
  $auth = '-x -D "cn=admin,'.$bdn.'" -H ldap://127.0.0.1 -y '.$admin;
  $cmd  = '/usr/bin/ldapsearch '.$auth.' -b "ou='.$ou_old.','.$bdn.'" -LLL "(ou='.$ou_old.')"';
  $cmd .= ' >/dev/null 2>/dev/null ; rtn=$? ; [ $rtn -ne 0 ] && exit $rtn ; ';
  $cmd .= '/usr/bin/ldapdelete '.$auth.' -r "ou='.$ou_tmp.','.$bdn.'"';
  $cmd .= ' >/dev/null 2>/dev/null ; ';
  $cmd .= '/usr/bin/ldapmodrdn '.$auth.' -r "ou='.$ou.','.$bdn.'" "ou='.$ou_tmp.'"';
  $cmd .= ' >/dev/null 2>/dev/null ; ';
  $cmd .= '/usr/bin/ldapmodrdn '.$auth.' -r "ou='.$ou_old.','.$bdn.'" "ou='.$ou.'"';
  $cmd .= ' >/dev/null 2>/dev/null ; ';
  $cmd .= '/usr/bin/ldapmodrdn '.$auth.' -r "ou='.$ou_tmp.','.$bdn.'" "ou='.$ou_old.'"';
  @file_put_contents($admin, $rootpw);
  shell($cmd.' >/dev/null 2>/dev/null', $status);
  @unlink($admin);
  if ($status != 0) {
    if ($status == 49) {
      $rtn = 28;
    } else {
      $rtn = 31;
    }
  } else {
    $rtn = 41;
  }
  return($rtn);
}

// Function: importLDIF
//
function importLDIF($rootpw, $ou, $name, &$count) {

  $count = 0;
  $bdn = trim(shell_exec('. /etc/rc.conf; echo "${LDAP_SERVER_BASEDN:-dc=ldap}"'));
  $ou_old = $ou.'-old';

  $cmd = 'grep -qi "^dn:[^,]*ou='.$ou.','.$bdn.'$" '.$name;
  shell($cmd.' >/dev/null 2>/dev/null', $status);
  if ($status != 0) {
    // Add required organizationalUnit
    $ou_dn = "dn: ou=$ou,$bdn\nobjectClass: organizationalUnit\nou: $ou\n\n";
    $context = stream_context_create();
    if (($fp = @fopen($name, 'r', FALSE, $context)) !== FALSE) {
      $tmp_name = tempnam("/mnt/kd", ".PHP_");
      @file_put_contents($tmp_name, $ou_dn);
      @file_put_contents($tmp_name, $fp, FILE_APPEND);
      fclose($fp);
      @unlink($name);
      @rename($tmp_name, $name);
    } else {
      return(23);
    }
  }
  if ($rootpw === '') {
    return(10);
  }
  $count = (int)trim(shell_exec('grep -ci "^dn:" '.$name));

  $error_log = '/var/log/ldapadd-error.log';
  if (is_file($error_log)) {
    @unlink($error_log);
  }

  $admin = tempnam("/var/tmp", "PHP_");
  $auth = '-x -D "cn=admin,'.$bdn.'" -H ldap://127.0.0.1 -y '.$admin;
  $cmd  = '/usr/bin/ldapdelete '.$auth.' -r "ou='.$ou_old.','.$bdn.'"';
  $cmd .= ' >/dev/null 2>/dev/null ; ';
  $cmd .= '/usr/bin/ldapmodrdn '.$auth.' -r "ou='.$ou.','.$bdn.'" "ou='.$ou_old.'"';
  $cmd .= ' >/dev/null 2>/dev/null ; ';
  $cmd .= '/usr/bin/ldapadd '.$auth.' -c -S '.$error_log.' -f '.$name;
  $cmd .= ' >/dev/null 2>/dev/null ; rtn=$? ; [ $rtn -eq 0 -o $rtn -eq 49 ] && exit $rtn ; ';
  $cmd .= '/usr/bin/ldapdelete '.$auth.' -r "ou='.$ou.','.$bdn.'"';
  $cmd .= ' >/dev/null 2>/dev/null ; ';
  $cmd .= '/usr/bin/ldapmodrdn '.$auth.' -r "ou='.$ou_old.','.$bdn.'" "ou='.$ou.'"';
  @file_put_contents($admin, $rootpw);
  shell($cmd.' >/dev/null 2>/dev/null ; exit $rtn', $status);
  @unlink($admin);
  if ($status != 0) {
    if ($status == 49) {
      $rtn = 28;
    } else {
      $rtn = 30;
    }
  } else {
    $rtn = 40;
  }
  return($rtn);
}

// Function: importVCARD
//
function importVCARD($rootpw, $ou, $name, &$count) {

  $count = 0;
  $bdn = trim(shell_exec('. /etc/rc.conf; echo "${LDAP_SERVER_BASEDN:-dc=ldap}"'));
  $out_file = tempnam("/mnt/kd", ".PHP_");

  if (vcard_export($ou, $bdn, $name, $out_file) === FALSE) {
    @unlink($out_file);
    return(23);
  }
  $rtn = importLDIF($rootpw, $ou, $out_file, $count);
  @unlink($out_file);
  return($rtn);
}

function vcard_export($ou, $bdn, $in_file, $out_file) {

  $options = array(
    'mailonly' => isset($_POST['opt_m']),
    'phoneonly' => isset($_POST['opt_p']),
    'sanitize' => isset($_POST['opt_s']),
    'sanitize_dash' => isset($_POST['opt_S'])
  );
  if (isset($_POST['opt_n'])) {
    $df_opts = explode('~', $_POST['opt_n']);
    $options['internationalprefix'] = isset($df_opts[0]) ? $df_opts[0] : '';
    $options['nationalprefix'] = isset($df_opts[1]) ? $df_opts[1] : '';
    $options['countryprefix'] = isset($df_opts[2]) ? $df_opts[2] : '';
  }
  if (isset($_POST['opt_dialprefix'])) {
    if (($dialprefix = preg_replace('/[^0-9+-]/', '', $_POST['opt_dialprefix'])) !== '') {
      $options['dialprefix'] = $dialprefix;
    }
  }

  // parse a vCard file
  $conv = new vcard_convert($options);
  if (! $conv->fromFile($in_file)) {
    return(FALSE);
  }
  $out = $conv->toLdif("ou=$ou,$bdn");
  $ou_dn = "dn: ou=$ou,$bdn\nobjectClass: organizationalUnit\nou: $ou\n\n";
  if (($fp = @fopen($out_file,"wb")) === FALSE) {
    return(FALSE);
  }
  fwrite($fp, $ou_dn);
  fwrite($fp, $out);
  fclose($fp);

  return(TRUE);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  $rootpw = isset($_POST['rootpw']) ? tuqd($_POST['rootpw']) : '';
  if (! $global_staff) {
    $result = 999;
  } elseif (isset($_POST['submit_action'])) {
    $action = $_POST['addressbook_action'];
    if ($action === 'export') {
      $result = exportLDIF($rootpw, 'addressbook');
    } elseif ($action === 'revert') {
      $result = revertLDIF($rootpw, 'addressbook');
    } else {
      $result = 11;
    }
  } elseif (isset($_POST['submit_ldif'], $_FILES['import_ldif'])) {
    $result = 1;
    $error = $_FILES['import_ldif']['error'];
    $tmp_name = $_FILES['import_ldif']['tmp_name'];
    $name = basename($_FILES['import_ldif']['name']);
    if ($error == 0) {
      $size = filesize($tmp_name);
      if ($size === FALSE || $size > 5000000 || $size == 0) {
        $result = 20;
      } else {
        $suffix = '.ldif.txt';
        if (($len = strlen($name) - strlen($suffix)) < 0) {
          $len = 0;
        }
        if (stripos($name, $suffix, $len) === FALSE) {
          $result = 22;
        }
      }
    } elseif ($error == 1 || $error == 2) {
      $result = 20;
    } else {
      $result = 21;
    }
    if ($result == 1) {
      $result = 99;
      $name = '/mnt/kd/.import_ldif'.$suffix;
      if (move_uploaded_file($tmp_name, $name)) {
        $result = importLDIF($rootpw, 'addressbook', $name, $count);
      }
      if (is_file($name)) {
        @unlink($name);
      }
      header('Location: '.$myself.'?result='.$result.'&count='.$count);
      exit;
    }
  } elseif (isset($_POST['submit_vcard'], $_FILES['import_vcard'])) {
    $result = 1;
    $error = $_FILES['import_vcard']['error'];
    $tmp_name = $_FILES['import_vcard']['tmp_name'];
    $name = basename($_FILES['import_vcard']['name']);
    if ($error == 0) {
      $size = filesize($tmp_name);
      if ($size === FALSE || $size > 5000000 || $size == 0) {
        $result = 20;
      } else {
        $suffix = '.vcf';
        if (($len = strlen($name) - strlen($suffix)) < 0) {
          $len = 0;
        }
        if (stripos($name, $suffix, $len) === FALSE) {
          $result = 26;
        }
      }
    } elseif ($error == 1 || $error == 2) {
      $result = 20;
    } else {
      $result = 27;
    }
    if ($result == 1) {
      $result = 99;
      $name = '/mnt/kd/.import_vcard'.$suffix;
      if (move_uploaded_file($tmp_name, $name)) {
        $result = importVCARD($rootpw, 'addressbook', $name, $count);
      }
      if (is_file($name)) {
        @unlink($name);
      }
      header('Location: '.$myself.'?result='.$result.'&count='.$count);
      exit;
    }
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} else { // Start of HTTP GET
$ACCESS_RIGHTS = 'staff';
require_once '../common/header.php';

  putHtml("<center>");
  if (isset($_GET['result'])) {
    $result = $_GET['result'];
    if ($result == 10) {
      putHtml('<p style="color: red;">"cn=admin" Password not specified.</p>');
    } elseif ($result == 11) {
      putHtml('<p style="color: red;">No Action, select an action command.</p>');
    } elseif ($result == 20) {
      putHtml('<p style="color: red;">File size must be less then 5 MBytes.</p>');
    } elseif ($result == 21) {
      putHtml('<p style="color: red;">An input .ldif.txt file must be defined.</p>');
    } elseif ($result == 22) {
      putHtml('<p style="color: red;">Invalid suffix, only LDIF files ending with .ldif.txt are allowed.</p>');
    } elseif ($result == 23) {
      putHtml('<p style="color: red;">Invalid file format, no data changed.</p>');
    } elseif ($result == 26) {
      putHtml('<p style="color: red;">Invalid suffix, only vCard files ending with .vcf are allowed.</p>');
    } elseif ($result == 27) {
      putHtml('<p style="color: red;">An input .vcf file must be defined.</p>');
    } elseif ($result == 28) {
      putHtml('<p style="color: red;">Invalid "cn=admin" credentials.</p>');
    } elseif ($result == 29) {
      putHtml('<p style="color: red;">Address Book export failed.</p>');
    } elseif ($result == 30) {
      putHtml('<p style="color: red;">Address Book Import failed, no data changed. More info click: <a href="/admin/view.php?file=/var/log/ldapadd-error.log" class="headerText">LDIF Import Errors</a></p>');
    } elseif ($result == 31) {
      putHtml('<p style="color: red;">No previous Address Book to revert to.</p>');
    } elseif ($result == 40) {
      $count = (isset($_GET['count'])) ? $_GET['count'] : '0';
      putHtml('<p style="color: green;">Successful LDAP Address Book import.  Count: '.$count.' entries.</p>');
    } elseif ($result == 41) {
      putHtml('<p style="color: green;">Reverted to previous LDAP Address Book.</p>');
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
  <form method="post" action="<?php echo $myself;?>" enctype="multipart/form-data">
  <table width="100%" class="stdtable">
  <tr><td style="text-align: center;" colspan="2">
  <h2>LDAP Address Book Management:</h2>
  </td></tr>
<?php

if (is_file('/var/run/slapd/slapd.pid')) {
  $db = parseRCconf($CONFFILE);

  $rootpw = getVARdef($db, 'LDAP_SERVER_PASS');
  putHtml('<tr><td style="text-align: center;" colspan="2">');
  putHtml('"cn=admin" Password:');
  if ($rootpw !== '') {
    putHtml('**********');
    putHtml('<input type="hidden" name="rootpw" value="'.$rootpw.'" />');
  } else {
    putHtml('<input type="password" size="18" maxlength="128" name="rootpw" value="" />');
  }
  putHtml('</td></tr>');

  putHtml('<tr><td style="text-align: center;" colspan="2">');
  putHtml('<select name="addressbook_action">');
  foreach ($action_menu as $key => $value) {
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('&ndash;');
  putHtml('<input type="submit" value="LDAP Address Book" name="submit_action" />');
  putHtml('</td></tr>');

  putHtml('<tr><td style="text-align: center;" colspan="2">');
  putHtml('<h2>Import LDIF File to Address Book:</h2>');
  putHtml('<input type="hidden" name="MAX_FILE_SIZE" value="5000000" />');
  putHtml('</td></tr><tr><td style="text-align: center;" colspan="2">');
  putHtml('<input type="file" name="import_ldif" />');
  putHtml('&ndash;');
  putHtml('<input type="submit" name="submit_ldif" value="Import LDIF" />');
  putHtml('</td></tr>');

  putHtml('<tr><td style="text-align: center;" colspan="2">');
  putHtml('<h2>Import vCard File to Address Book:</h2>');
  putHtml('<input type="hidden" name="MAX_FILE_SIZE" value="5000000" />');
  putHtml('</td></tr>');
  putHtml('<tr><td class="dialogText" style="text-align: right;" width="60">');
  putHtml('<strong>Filter:</strong>');
  putHtml('</td><td class="dialogText">Options</td></tr>');
  putHtml('<tr><td class="dialogText" style="text-align: right;">');
  putHtml('<input type="checkbox" value="opt_s" name="opt_s" /></td><td>Sanitize phone numbers to only<br />include "+0123456789" characters</td></tr>');
  putHtml('<tr><td class="dialogText" style="text-align: right;">');
  putHtml('<input type="checkbox" value="opt_S" name="opt_S" checked="checked" /></td><td>Sanitize as above but replace<br />sequential non-numbers with a dash "-"</td></tr>');
  if (($df = trim(shell_exec('. /etc/rc.conf; echo "$DIALING_PREFIX_NUMBERS"'))) !== '') {
    putHtml('<tr><td class="dialogText" style="text-align: right;">');
    putHtml('<input type="checkbox" value="'.$df.'" name="opt_n" /></td><td>Normalize International E.164 prefixes');
    putHtml('</td></tr>');
  }
  putHtml('<tr><td class="dialogText" style="text-align: right;">');
  putHtml('<input type="checkbox" value="opt_p" name="opt_p" /></td><td>Skip vCards without phone numbers</td></tr>');
  putHtml('<tr><td class="dialogText" style="text-align: right;">');
  putHtml('<input type="checkbox" value="opt_m" name="opt_m" /></td><td>Skip vCards without e-mail addresses</td></tr>');
  putHtml('<tr><td class="dialogText" style="text-align: right;">');
  putHtml('<input type="text" size="3" maxlength="8" name="opt_dialprefix" value="" />');
  putHtml('</td><td>Dial Prefix added to all phone numbers</td></tr>');
  putHtml('<tr><td style="text-align: center;" colspan="2">');
  putHtml('<input type="file" name="import_vcard" />');
  putHtml('&ndash;');
  putHtml('<input type="submit" name="submit_vcard" value="Import vCard" />');
  putHtml('</td></tr>');
} else {
  putHtml('<tr><td style="text-align: center;" colspan="2">');
  putHtml('<p style="color: red;">LDAP Server is not enabled.</p>');
  putHtml('</td></tr>');
}

  putHtml('</table>');
  putHtml('</form>');

  putHtml("</center></td></tr></table>");
  putHtml("</center>");
} // End of HTTP GET
require_once '../common/footer.php';

?>
