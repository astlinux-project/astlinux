<?php

// Copyright (C) 2008-2009 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// openssl-openvpnclient.php for AstLinux
// 05-24-2009
//

// Function: openvpnclientSETUP()
//
function openvpnclientSETUP() {

  if (! function_exists('openssl_x509_parse')) {
    return(FALSE);
  }
  $ssl['base_dir'] = '/mnt/kd/openvpn';
  $ssl['dir'] = $ssl['base_dir'].'/webinterface';
  $ssl['key_dir'] = $ssl['dir'].'/client_keys';
  $ssl['ca_crt'] = $ssl['key_dir'].'/ca.crt';
  $ssl['client_crt'] = $ssl['key_dir'].'/client.crt';
  $ssl['client_key'] = $ssl['key_dir'].'/client.key';
  $ssl['tls_auth_key'] = $ssl['key_dir'].'/ta.key';

  if (! is_dir($ssl['base_dir'])) {
    if (! @mkdir($ssl['base_dir'], 0755)) {
      return(FALSE);
    }
  }
  if (! is_dir($ssl['dir'])) {
    if (! @mkdir($ssl['dir'], 0755)) {
      return(FALSE);
    }
  }
  if (! is_dir($ssl['key_dir'])) {
    if (! @mkdir($ssl['key_dir'], 0700)) {
      return(FALSE);
    }
  }
  return($ssl);
}

// Function: getCREDinfo()
//
function getCREDinfo($ssl, $type, &$CommonName) {

  $CommonName = '';
  $str = 'Undefined&nbsp;&ndash;&nbsp;';
  if ($ssl !== FALSE) {
    if (is_file($ssl[$type])) {
      if ($type === 'client_crt') {
        if (($cert_str = @file_get_contents($ssl[$type])) !== FALSE) {
          if (($cert_array = openssl_x509_parse($cert_str)) !== FALSE) {
            if (isset($cert_array['subject']['CN'])) {
              $CommonName = $cert_array['subject']['CN'];
            }
          }
        }
      }
      $str = basename($ssl[$type]).'&nbsp;&ndash;&nbsp;';
    }
  }
  return($str);
}

// Function: opensslDELETEclientkeys()
//
function opensslDELETEclientkeys($ssl) {

  if ($ssl !== FALSE) {
    $types = array ('ca_crt', 'client_crt', 'client_key', 'tls_auth_key');
    foreach ($types as $type) {
      if (is_file($ssl[$type])) {
        @unlink($ssl[$type]);
      }
    }
  }
}

// Function: opensslOPENVPNCis_valid()
//
function opensslOPENVPNCis_valid($ssl) {

  if ($ssl !== FALSE) {
    if (is_file($ssl['ca_crt']) &&
        is_file($ssl['client_crt']) &&
        is_file($ssl['client_key'])) {
      return(TRUE);
    }
  }
  return(FALSE);
}
?>
