<?php

// Copyright (C) 2008-2009 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// openssl.php for AstLinux
// 12-26-2008
//

// Function: is_opensslHERE()
//
function is_opensslHERE() {
  return(function_exists('openssl_pkey_new'));
}

// Function: opensslDELETEkeys()
//
function opensslDELETEkeys($ssl) {

  if (is_dir($ssl['key_dir'])) {
    foreach (glob($ssl['key_dir'].'/*.key') as $file) {
      if (is_file($file)) {
        @unlink($file);
      }
    }
    foreach (glob($ssl['key_dir'].'/*.crt') as $file) {
      if (is_file($file)) {
        @unlink($file);
      }
    }
  }
}

// Function: opensslGETclients()
//
function opensslGETclients($ssl) {

  $client_list = array();

  if (is_dir($ssl['key_dir'])) {
    foreach (glob($ssl['key_dir'].'/*.key') as $key) {
      if (is_file($key)) {
        $client = basename($key, '.key');
        $crt = dirname($key).'/'.$client.'.crt';
        if (is_file($crt) && $client !== 'server' && $client !== 'ca') {
          $client_list[] = $client;
        }
      }
    }
  }
  return($client_list);
}

// Function: opensslCREATEdn()
//
function opensslCREATEdn($commonName, $ssl) {

  $dn = $ssl['dn'];
  if ($commonName !== '') {
    $dn['commonName'] = $commonName;
  }
  return($dn);
}

// Function: opensslCREATEselfCert()
//
function opensslCREATEselfCert($ssl) {

  if (($privkey = openssl_pkey_new($ssl['configArgs'])) === FALSE) {
    return(FALSE);
  }
  $dn = opensslCREATEdn('', $ssl);
  if (($csr = openssl_csr_new($dn, $privkey, $ssl['configArgs'])) === FALSE) {
    return(FALSE);
  }
  if (($cert = openssl_csr_sign($csr, NULL, $privkey, 3650, $ssl['sign_ca'], time())) === FALSE) {
    return(FALSE);
  }
  if (! openssl_x509_export_to_file($cert, $ssl['key_dir'].'/ca.crt', TRUE)) {
    return(FALSE);
  }
  $keyname = $ssl['key_dir'].'/ca.key';
  if (openssl_pkey_export_to_file($privkey, $keyname, NULL, $ssl['configArgs'])) {
    chmod($keyname, 0600);
  } else {
    return(FALSE);
  }
  return(TRUE);
}

// Function: opensslCREATEserverCert()
//
function opensslCREATEserverCert($ssl) {

  if (($privkey = openssl_pkey_new($ssl['configArgs'])) === FALSE) {
    return(FALSE);
  }
  $dn = opensslCREATEdn('', $ssl);
  if (($csr = openssl_csr_new($dn, $privkey, $ssl['configArgs'])) === FALSE) {
    return(FALSE);
  }
  if (($cert = openssl_csr_sign($csr, $ssl['ca_crt'], $ssl['ca_key'], 3650, $ssl['sign_server'], time())) === FALSE) {
    return(FALSE);
  }
  if (! openssl_x509_export_to_file($cert, $ssl['key_dir'].'/server.crt', TRUE)) {
    return(FALSE);
  }
  $keyname = $ssl['key_dir'].'/server.key';
  if (openssl_pkey_export_to_file($privkey, $keyname, NULL, $ssl['configArgs'])) {
    chmod($keyname, 0600);
  } else {
    return(FALSE);
  }
  return(TRUE);
}

// Function: opensslCREATEclientCert()
//
function opensslCREATEclientCert($client, $ssl) {

  if (($privkey = openssl_pkey_new($ssl['configArgs'])) === FALSE) {
    return(FALSE);
  }
  $dn = opensslCREATEdn($client, $ssl);
  if (($csr = openssl_csr_new($dn, $privkey, $ssl['configArgs'])) === FALSE) {
    return(FALSE);
  }
  if (($cert = openssl_csr_sign($csr, $ssl['ca_crt'], $ssl['ca_key'], 3650, $ssl['sign_client'], time())) === FALSE) {
    return(FALSE);
  }
  if (! openssl_x509_export_to_file($cert, $ssl['key_dir'].'/'.$client.'.crt', TRUE)) {
    return(FALSE);
  }
  $keyname = $ssl['key_dir'].'/'.$client.'.key';
  if (openssl_pkey_export_to_file($privkey, $keyname, NULL, $ssl['configArgs'])) {
    chmod($keyname, 0600);
  } else {
    return(FALSE);
  }
  return(TRUE);
}

// Function: opensslCREATEhttpsCert()
//
function opensslCREATEhttpsCert($countryName, $stateName, $localityName, $orgName, $orgUnit, $commonName, $email, $fname) {

  $dn = array(
    'countryName' => $countryName,
    'stateOrProvinceName' => $stateName,
    'localityName' => $localityName,
    'organizationName' => $orgName,
    'organizationalUnitName' => $orgUnit,
    'commonName' => $commonName,
    'emailAddress' => $email
  );
  $configArgs = array(
    'digest_alg' => 'sha256',
    'private_key_bits' => 2048,
    'encrypt_key' => FALSE
  );
  $sign_ca = array(
    'digest_alg' => 'sha256',
    'private_key_bits' => 2048,
    'x509_extensions' => 'v3_ca',
    'encrypt_key' => FALSE
  );

  if (($privkey = openssl_pkey_new($configArgs)) === FALSE) {
    return(FALSE);
  }
  if (($csr = openssl_csr_new($dn, $privkey, $configArgs)) === FALSE) {
    return(FALSE);
  }
  if (($cert = openssl_csr_sign($csr, NULL, $privkey, 3650, $sign_ca, time())) === FALSE) {
    return(FALSE);
  }
  if (! openssl_pkey_export($privkey, $key_pem, NULL, $configArgs)) {
    return(FALSE);
  }
  if (! openssl_x509_export($cert, $crt_pem, TRUE)) {
    return(FALSE);
  }
  $dir = dirname($fname);
  if (! is_dir($dir)) {
    if (! @mkdir($dir, 0755)) {
      return(FALSE);
    }
  }
  if (($fp = fopen($fname, 'w')) === FALSE) {
    return(FALSE);
  }
  fwrite($fp, $key_pem);
  fwrite($fp, $crt_pem);
  fclose($fp);
  chmod($fname, 0600);
  return(TRUE);
}

// Function: opensslRANDOMpass()
//
function opensslRANDOMpass($length = 6) {
  $pass = '';

  // Old Method:
  // $data = trim(shell_exec('openssl rand -base64 24 2>/dev/null'));
  $data = base64_encode(openssl_random_pseudo_bytes(24));
  $dataLen = strlen($data);

  $mask = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

  $n = 0;
  for ($i = 0; $i < $dataLen; $i++) {
    if (strpos($mask, $data[$i]) !== FALSE) {
      $pass .= $data[$i];
      $n++;
      if ($n >= $length) {
        break;
      }
    }
  }

  return($pass);
}

// Function: opensslPKCS12str()
//
function opensslPKCS12str($ssl, $commonName, $pass) {
  $p12 = '';

  if (strlen($pass) > 3) {
    $extraArgs['friendly_name'] = $commonName;
    if (($caStr = @file_get_contents($ssl['key_dir'].'/ca.crt')) !== FALSE) {
      $extraArgs['extracerts'] = $caStr;
    }
    if (! openssl_pkcs12_export('file://'.$ssl['key_dir'].'/'.$commonName.'.crt', $p12,
        'file://'.$ssl['key_dir'].'/'.$commonName.'.key', $pass, $extraArgs)) {
      $p12 = '';
    }
  }

  return($p12);
}

// Function: opensslREADMEstr()
//
function opensslREADMEstr($type, $commonName, $pass) {

  $readme = "Client \"$commonName\" Credentials\n\n";
  $readme .= "ca.crt - A self-signed Certificate Authority (CA).\n\n";
  $readme .= "$commonName.crt - This client's public key certificate, signed by ca.crt.\n\n";
  $readme .= "$commonName.key - This client's private key.\n";
  $readme .= "Note: File '$commonName.key' is not encrypted and must be kept secure.\n\n";
  if ($type === 'p12' || $type === 'ovpn' || $type === 'ovpn-ta') {
    $readme .= "$commonName.p12 - A password protected PKCS#12 container combining the credentials from the above three files.\n\n";
    $readme .= "PKCS#12 Container Password: $pass\n";
    $readme .= "Keep it secure.\n\n";
    if ($type === 'ovpn' || $type === 'ovpn-ta') {
      if ($type === 'ovpn-ta') {
        $readme .= "$commonName-ta.key - TLS-Auth key which adds an additional HMAC signature to all SSL/TLS handshake packets.\n";
        $readme .= "Note: File '$commonName-ta.key' is not encrypted and must be kept secure.\n\n";
      }
      $readme .= "Folder: 'openvpn-cert-key'\n";
      $readme .= "$commonName.ovpn - OpenVPN CA-CERT-KEY profile, contains client certificate and private key.\n";
      $readme .= "Note: File 'openvpn-cert-key/$commonName.ovpn' is not encrypted and must be kept secure.\n\n";

      $readme .= "Folder: 'openvpn-nocert-nokey'\n";
      $readme .= "$commonName.ovpn - OpenVPN CA profile, use separately with the above '$commonName.p12' file for client devices.\n\n";

      $readme .= "Folder: 'openvpn-pkcs12'\n";
      $readme .= "$commonName.ovpn - OpenVPN profile, use paired with the file '$commonName.p12'.\n";
      $readme .= "$commonName.p12 - A password protected PKCS#12 container, use paired with the file '$commonName.ovpn'.\n\n";
    }
  }

  return($readme);
}
?>
