<?php

// Copyright (C) 2008-2010 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// openssl-ipsec.php for AstLinux
// 11-26-2010
//

// Function: ipsecSETUP()
//
function ipsecSETUP($peer) {

  if ($peer === '') {
    return(FALSE);
  }
  if (! function_exists('openssl_x509_parse')) {
    return(FALSE);
  }
  $ssl['base_dir'] = '/mnt/kd/ipsec';
  $ssl['dir'] = $ssl['base_dir'].'/webinterface';
  $ssl['peer_dir'] = $ssl['dir'].'/peer_keys';
  $ssl['key_dir'] = $ssl['peer_dir'].'/'.rawurlencode($peer);
  $ssl['ca_crt'] = $ssl['key_dir'].'/ca.crt';
  $ssl['peer_crt'] = $ssl['key_dir'].'/peer.crt';
  $ssl['peer_key'] = $ssl['key_dir'].'/peer.key';

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
  if (! is_dir($ssl['peer_dir'])) {
    if (! @mkdir($ssl['peer_dir'], 0755)) {
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
      if ($type === 'peer_crt') {
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
    $types = array ('ca_crt', 'peer_crt', 'peer_key');
    foreach ($types as $type) {
      if (is_file($ssl[$type])) {
        @unlink($ssl[$type]);
      }
    }
  }
}

// Function: ipsecDELETEpeer()
//
function ipsecDELETEpeer($peer) {

  if (($ssl = ipsecSETUP($peer)) !== FALSE) {
    opensslDELETEclientkeys($ssl);
    @rmdir($ssl['key_dir']);
  }
}

// Function: opensslIPSECis_valid()
//
function opensslIPSECis_valid($ssl) {

  if ($ssl !== FALSE) {
    if (is_file($ssl['ca_crt']) &&
        is_file($ssl['peer_crt']) &&
        is_file($ssl['peer_key'])) {
      return(TRUE);
    }
  }
  return(FALSE);
}
?>
