<?php

// Copyright (C) 2008-2009 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// openssl-openvpn.php for AstLinux
// 05-24-2009
//

// Function: openvpnSETUP()
//
function openvpnSETUP($countryName, $stateName, $localityName, $orgName, $orgUnit, $commonName, $email) {
  // System location of OpenSSL default configuration file
  $OPENSSL_CNF = '/usr/lib/ssl/openssl.cnf';
  
  if (! function_exists('openssl_pkey_new')) {
    return(FALSE);
  }

  $ssl['base_dir'] = '/mnt/kd/openvpn';
  $ssl['dir'] = $ssl['base_dir'].'/webinterface';
  $ssl['key_dir'] = $ssl['dir'].'/keys';
  $ssl['config'] = $ssl['dir'].'/openssl.cnf';
  $ssl['dh_pem'] = $ssl['dir'].'/dh1024.pem';
  $ssl['ca_crt'] = 'file://'.$ssl['key_dir'].'/ca.crt';
  $ssl['ca_key'] = 'file://'.$ssl['key_dir'].'/ca.key';
  $ssl['add_server'] = array(
    '',
    '[ openvpn_server ]',
    'basicConstraints=CA:FALSE',
    'nsCertType=server',
    'nsComment="OpenVPN Server Certificate"',
    'subjectKeyIdentifier=hash',
    'authorityKeyIdentifier=keyid,issuer:always',
    'extendedKeyUsage=serverAuth',
    'keyUsage=digitalSignature,keyEncipherment',
    ''
  );
  $ssl['configArgs'] = array(
    'config' => $ssl['config'],
    'digest_alg' => 'sha1',
    'private_key_bits' => 1024,
    'encrypt_key' => FALSE
  );
  $ssl['sign_ca'] = array(
    'config' => $ssl['config'],
    'digest_alg' => 'sha1',
    'private_key_bits' => 1024,
    'x509_extensions' => 'v3_ca',
    'encrypt_key' => FALSE
  );
  $ssl['sign_server'] = array(
    'config' => $ssl['config'],
    'digest_alg' => 'sha1',
    'private_key_bits' => 1024,
    'x509_extensions' => 'openvpn_server',
    'encrypt_key' => FALSE
  );
  $ssl['sign_client'] = array(
    'config' => $ssl['config'],
    'digest_alg' => 'sha1',
    'private_key_bits' => 1024,
    'x509_extensions' => 'usr_cert',
    'encrypt_key' => FALSE
  );
  
  $ssl['dn'] = array(
    'countryName' => $countryName,
    'stateOrProvinceName' => $stateName,
    'localityName' => $localityName,
    'organizationName' => $orgName,
    'organizationalUnitName' => $orgUnit,
    'commonName' => $commonName,
    'emailAddress' => $email
  );
  
  if (! is_file($ssl['config'])) {
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
    if (! copy($OPENSSL_CNF, $ssl['config'])) {
      return(FALSE);
    }
    chmod($ssl['config'], 0644);
    if (($fp = fopen($ssl['config'], 'a')) === FALSE) {
      return(FALSE);
    }
    foreach($ssl['add_server'] as $line) {
      fwrite($fp, $line."\n");
    }
    fclose($fp);
  }
  if (! is_dir($ssl['key_dir'])) {
    if (! @mkdir($ssl['key_dir'], 0700)) {
      return(FALSE);
    }
  }
  return($ssl);
}

// Function: opensslOPENVPNis_valid()
//
function opensslOPENVPNis_valid($ssl) {

  if ($ssl !== FALSE) {
    if (is_file($ssl['dh_pem']) &&
        is_file($ssl['key_dir'].'/ca.crt') &&
        is_file($ssl['key_dir'].'/ca.key') &&
        is_file($ssl['key_dir'].'/server.crt') &&
        is_file($ssl['key_dir'].'/server.key')) {
      return(TRUE);
    }
  }
  return(FALSE);
}

// Function: opensslCREATEdh_pem()
//
function opensslCREATEdh_pem($ssl) {

  if (is_file($ssl['dh_pem'])) {
    return(TRUE);
  }
  shell('openssl dhparam -out '.$ssl['dh_pem'].' 1024 >/dev/null 2>/dev/null', $status);
  if ($status != 0) {
    @unlink($ssl['dh_pem']);
    return(FALSE);
  }
  chmod($ssl['dh_pem'], 0644);
  return(TRUE);
}
?>
