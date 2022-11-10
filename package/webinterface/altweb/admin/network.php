<?php

// Copyright (C) 2008-2022 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// network.php for AstLinux
// 04-04-2008
// 04-08-2008, Added Network Services
// 04-22-2008, Added user.conf generation
// 08-01-2008, Added tftpd - dnsmasq option
// 08-05-2008, Added NTPSERVS support
// 08-07-2008, Added HTTPUSER and HTTPSUSER support
// 08-07-2008, Removed TFTPDOPTIONS and FTPDOPTIONS support
// 09-06-2008, Added Edit NTP Config... and Edit VPN Config...
// 09-15-2008, Create gui.openvpn.conf on first time
// 11-15-2008, Added 2nd and 3rd Internal Interface
// 12-01-2008, Added Dynamic DNS Update
// 12-12-2008, Added DMZ support
// 12-26-2008, Added HTTPS Cert Generation
// 03-29-2009, Added Timezone Menu List
// 05-11-2009, Added Internal interface DNS/DHCP Menu
// 03-21-2010, Added SMTP TLS support
// 10-12-2010, Added IPv6 support
// 01-22-2011, Added Safe Asterisk
// 03-24-2011, Removed deprecated HTTPUSER and HTTPSUSER support
// 03-24-2011, Added HTTP_LISTING and HTTPS_LISTING support
// 05-24-2011, Added SIP Monitoring
// 12-03-2011, Added HTTP_ACCESSLOG and HTTPS_ACCESSLOG support
// 01-28-2012, Added LOCALDNS_LOCAL_DOMAIN support
// 07-07-2012, Added Universal Plug & Play support
// 09-23-2013, Added ddclient support
// 10-21-2013, Added LDAP server support
// 01-04-2014, Added NUT UPS Monitoring support
// 12-16-2014, Added Monit Monitoring support
// 08-21-2015, Added Fossil - Software Configuration Management
// 11-01-2015, Added DHCPv6 support
// 06-07-2016, Added Avahi mDNS/DNS-SD support
// 07-15-2016, Added 4th LAN Interface
// 11-14-2016, Added IPsec strongSwan support
// 01-22-2017, Removed Dynamic DNS 'getip.krisk.org', map to default
// 01-29-2017, Added DDGETIPV6 support
// 02-16-2017, Added Restart FTP Server support
// 06-02-2017, Added selectable Prefix Delegation interfaces
// 07-12-2017, Added ACME (Let's Encrypt) Certificate configuration
// 09-10-2017, Added Data Backup / Tarsnap Backup
// 04-14-2018, Added DNS-TLS support
// 07-30-2018, Added Keepalived support
// 03-05-2019, Added NETSTAT_EXTIF support
// 04-11-2019, Changed Outbound SMTP defaults
// 06-13-2019, Added Reload WireGuard VPN
// 02-21-2020, Remove PPTP VPN support
// 05-08-2020, Dynamically add VLAN and BRIDGE entries to interface list
// 05-10-2020, Added Linux Containers (LXC)
// 12-13-2020, Replace getdns/stubby with unbound for DNS-over-TLS
// 02-04-2021, Remove IPsec (racoon) VPN support
//
// System location of rc.conf file
$CONFFILE = '/etc/rc.conf';
// System location of /mnt/kd/rc.conf.d directory
$NETCONFDIR = '/mnt/kd/rc.conf.d';
// System location of gui.network.conf file
$NETCONFFILE = '/mnt/kd/rc.conf.d/gui.network.conf';
// System location of gui.firewall.conf file
$FIREWALLCONFFILE = '/mnt/kd/rc.conf.d/gui.firewall.conf';
// System location of user.conf file
$USERCONFFILE = '/mnt/kd/rc.conf.d/user.conf';

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

require_once '../common/openssl.php';

require_once '../common/timezones.php';

$select_ntp = array (
  'User Defined&nbsp;&nbsp;&nbsp;&gt;&gt;&gt;' => '',
  'us.pool.ntp.org' => 'us.pool.ntp.org',
  'europe.pool.ntp.org' => 'europe.pool.ntp.org',
  'north-america.pool.ntp.org' => 'north-america.pool.ntp.org',
  'south-america.pool.ntp.org' => 'south-america.pool.ntp.org',
  'asia.pool.ntp.org' => 'asia.pool.ntp.org',
  'oceania.pool.ntp.org' => 'oceania.pool.ntp.org',
  'africa.pool.ntp.org' => 'africa.pool.ntp.org'
);

$select_dhcpv6_prefix_len = array (
  '64' => '64',
  '60' => '60',
  '56' => '56',
  '52' => '52',
  '48' => '48',
  '44' => '44',
  '40' => '40',
  '36' => '36',
  '32' => '32'
);

$select_dyndns = array (
  'User Defined&nbsp;&nbsp;&nbsp;&gt;&gt;&gt;' => '',
  'ChangeIP' => 'changeip',
  'Cloudflare' => 'cloudflare',
  'DNS-O-Matic' => 'default@dnsomatic.com',
  'DNS Park' => 'dnspark',
  'DtDNS' => 'dtdns',
  'DuckDNS' => 'duckdns',
  'DynDNS' => 'dyndns@dyndns.org',
  'DynDNS [custom]' => 'custom@dyndns.org',
  'DynDNS [static]' => 'statdns@dyndns.org',
  'EasyDNS' => 'easydns',
  'FreeDNS' => 'default@freedns.afraid.org',
  'HE Free DNS' => 'he',
  'IPv64' => 'ipv64',
  'NameCheap' => 'namecheap',
  'No-IP' => 'default@no-ip.com',
  'nsupdate.info' => 'default@nsupdate.info',
  'pairDomains' => 'default@pairnic.com',
  'ZoneEdit' => 'default@zoneedit.com'
);

$select_dyndns_getip = array (
  'User Defined&nbsp;&nbsp;&nbsp;&gt;&gt;&gt;' => '',
  'myip.dnsomatic.com' => 'myip.dnsomatic.com',
  'checkip.dns.he.net' => 'he',
  'checkip.dyndns.org' => 'checkip.dyndns.org',
  'External Interface' => 'interface'
);

$select_dyndns_getipv6 = array (
  'User Defined&nbsp;&nbsp;&nbsp;&gt;&gt;&gt;' => '',
  'checkip.dns.he.net' => 'he',
  'External Interface' => 'interface',
  'Disabled' => 'no'
);

$select_ldap_deref = array (
  'never' => 'never',
  'searching' => 'searching',
  'finding' => 'finding',
  'always' => 'always'
);

$select_ldap_tls_reqcert = array (
  'never' => 'never',
  'allow' => 'allow',
  'try' => 'try',
  'demand' => 'demand'
);

$select_ups_driver = array (
  'No direct connected "master" UPS' => '',
  '[usbhid-ups] Generic USB HID' => 'usbhid-ups',
  '[bcmxcp_usb] BCM/XCP protocol over USB' => 'bcmxcp_usb',
  '[blazer_usb] Megatec/Q1 protocol USB' => 'blazer_usb',
  '[richcomm_usb] Richcomm contact to USB' => 'richcomm_usb',
  '[riello_usb] Riello USB' => 'riello_usb',
  '[tripplite_usb] Tripp Lite OMNIVS/SMARTPRO' => 'tripplite_usb',
  '[snmp-ups] Generic SNMP' => 'snmp-ups',
  '[netxml-ups] Network XML' => 'netxml-ups'
);

$select_upnp = array (
  'disabled' => 'no:no',
  'NAT-PMP/PCP only' => 'yes:no',
  'UPnP only' => 'no:yes',
  'NAT-PMP/PCP &amp; UPnP' => 'yes:yes'
);

// Function: checkNETWORKsettings
//
function checkNETWORKsettings() {
  global $FIREWALLCONFFILE;

  $eth[] = $_POST['ext_eth'];
  $eth[] = $_POST['ext2_eth'];
  $eth[] = $_POST['int_eth'];
  $eth[] = $_POST['int2_eth'];
  $eth[] = $_POST['int3_eth'];
  $eth[] = $_POST['int4_eth'];
  $eth[] = $_POST['dmz_eth'];

  foreach ($eth as $ki => $i) {
    foreach ($eth as $kj => $j) {
      if ($ki != $kj && $i !== '' && $j !== '') {
        if ($i === $j) {
          return(100);
        }
      }
    }
  }

  if ($_POST['dmz_eth'] !== '') {
    if ($_POST['int_eth'] === '' && $_POST['int2_eth'] === '' && $_POST['int3_eth'] === '' && $_POST['int4_eth'] === '') {
      return(101);
    }
  }

  if ($_POST['firewall'] === 'arno') {
    if (! is_file($FIREWALLCONFFILE)) {
      return(102);
    }
  }

  $tz = ($_POST['timezone'] !== '') ? $_POST['timezone'] : tuq($_POST['other_timezone']);
  if ($tz !== '') {
    if (! is_file("/usr/share/zoneinfo/$tz")) {
      return(103);
    }
  }

  return(11);
}

// Function: saveNETWORKsettings
//
function saveNETWORKsettings($conf_dir, $conf_file) {
  global $global_prefs;
  global $USERCONFFILE;

  $isFIRSTtime = FALSE;
  if (is_dir($conf_dir) === FALSE) {
    if (@mkdir($conf_dir, 0755) === FALSE) {
      return(3);
    }
    $isFIRSTtime = TRUE;
  }
  if (($fp = @fopen($conf_file,"wb")) === FALSE) {
    return(3);
  }
  fwrite($fp, "### gui.network.conf - start ###\n###\n");

  $value = 'IPV6="'.$_POST['ipv6'].'"';
  fwrite($fp, "### IP Version\n".$value."\n");

  if ($_POST['ip_type'] === 'pppoe') {
    $x_value = 'PPPOEIF="'.$_POST['ext_eth'].'"';
    $value = 'EXTIF="ppp0"';
  } else {
    $x_value = 'PPPOEIF=""';
    $value = 'EXTIF="'.$_POST['ext_eth'].'"';
  }
  fwrite($fp, "### External PPPoE Interface\n".$x_value."\n");
  fwrite($fp, "### External Interface\n".$value."\n");

  if ($_POST['ip_type'] === 'dhcp' || $_POST['ip_type'] === 'dhcp-dhcpv6') {
    $value = 'EXTIP=""';
  } else {
    $value = 'EXTIP="'.tuq($_POST['static_ip']).'"';
  }
  fwrite($fp, "### External Static IPv4\n".$value."\n");

  if ($_POST['ip_type'] === 'dhcp' || $_POST['ip_type'] === 'dhcp-dhcpv6') {
    $value = 'EXTNM=""';
  } else {
    $value = 'EXTNM="'.tuq($_POST['mask_ip']).'"';
  }
  fwrite($fp, "### External Static IPv4 NetMask\n".$value."\n");

  if ($_POST['ip_type'] === 'dhcp' || $_POST['ip_type'] === 'dhcp-dhcpv6') {
    $value = 'EXTGW=""';
  } else {
    $value = 'EXTGW="'.tuq($_POST['gateway_ip']).'"';
  }
  fwrite($fp, "### External Static IPv4 Gateway\n".$value."\n");

  if ($_POST['ip_type'] === 'dhcp-dhcpv6' || $_POST['ip_type'] === 'static-dhcpv6') {
    $value = 'DHCPV6_CLIENT_ENABLE="yes"';
  } else {
    $value = 'DHCPV6_CLIENT_ENABLE="no"';
  }
  fwrite($fp, "### DHCPv6\n".$value."\n");

  $value = tuq($_POST['static_ipv6']);
  if ($value !== '' && strpos($value, '/') === FALSE) {
    $value="$value/64";
  }
  $value = 'EXTIPV6="'.$value.'"';
  fwrite($fp, "### External Static IPv6\n".$value."\n");

  $value = tuq($_POST['gateway_ipv6']);
  if (($pos = strpos($value, '/')) !== FALSE) {
    $value=substr($value, 0, $pos);
  }
  $value = 'EXTGWIPV6="'.$value.'"';
  fwrite($fp, "### External Static IPv6 Gateway\n".$value."\n");

  $value = 'PPPOEUSER="'.tuq($_POST['user_pppoe']).'"';
  fwrite($fp, "### PPPoE Username\n".$value."\n");

  $value = 'PPPOEPASS="'.string2RCconfig(trim($_POST['pass_pppoe'])).'"';
  fwrite($fp, "### PPPoE Password\n".$value."\n");

  $value = 'HOSTNAME="'.tuq($_POST['hostname']).'"';
  fwrite($fp, "### Hostname\n".$value."\n");

  $value = 'DOMAIN="'.tuq($_POST['domain']).'"';
  fwrite($fp, "### Domain\n".$value."\n");

  $value = isset($_POST['local_domain']) ? 'LOCALDNS_LOCAL_DOMAIN="yes"' : 'LOCALDNS_LOCAL_DOMAIN="no"';
  fwrite($fp, "### Local Domain\n".$value."\n");

  $value = 'DNS="'.tuq($_POST['dns']).'"';
  fwrite($fp, "### DNS Servers\n".$value."\n");

  $value = 'VLANS="'.tuq($_POST['vlans']).'"';
  fwrite($fp, "### VLAN Interfaces\n".$value."\n");

  $value = isset($_POST['vlan_cos']) ? 'VLANCOS="yes"' : 'VLANCOS=""';
  fwrite($fp, "### VLAN COS\n".$value."\n");

  $value = 'EXT2IF="'.$_POST['ext2_eth'].'"';
  fwrite($fp, "### External Failover Interface\n".$value."\n");

  $value = 'INTIF="'.$_POST['int_eth'].'"';
  fwrite($fp, "### 1st LAN Interface\n".$value."\n");

  $value = 'INTIP="'.tuq($_POST['int_ip']).'"';
  fwrite($fp, "### 1st LAN IPv4\n".$value."\n");

  $value = 'INTNM="'.tuq($_POST['int_mask_ip']).'"';
  fwrite($fp, "### 1st LAN NetMask\n".$value."\n");

  $value = tuq($_POST['int_ipv6']);
  if ($value !== '' && strpos($value, '/') === FALSE) {
    $value="$value/64";
  }
  $value = 'INTIPV6="'.$value.'"';
  fwrite($fp, "### 1st LAN IPv6\n".$value."\n");

  $value = 'INT2IF="'.$_POST['int2_eth'].'"';
  fwrite($fp, "### 2nd LAN Interface\n".$value."\n");

  $value = 'INT2IP="'.tuq($_POST['int2_ip']).'"';
  fwrite($fp, "### 2nd LAN IPv4\n".$value."\n");

  $value = 'INT2NM="'.tuq($_POST['int2_mask_ip']).'"';
  fwrite($fp, "### 2nd LAN NetMask\n".$value."\n");

  $value = tuq($_POST['int2_ipv6']);
  if ($value !== '' && strpos($value, '/') === FALSE) {
    $value="$value/64";
  }
  $value = 'INT2IPV6="'.$value.'"';
  fwrite($fp, "### 2nd LAN IPv6\n".$value."\n");

  $value = 'INT3IF="'.$_POST['int3_eth'].'"';
  fwrite($fp, "### 3rd LAN Interface\n".$value."\n");

  $value = 'INT3IP="'.tuq($_POST['int3_ip']).'"';
  fwrite($fp, "### 3rd LAN IPv4\n".$value."\n");

  $value = 'INT3NM="'.tuq($_POST['int3_mask_ip']).'"';
  fwrite($fp, "### 3rd LAN NetMask\n".$value."\n");

  $value = tuq($_POST['int3_ipv6']);
  if ($value !== '' && strpos($value, '/') === FALSE) {
    $value="$value/64";
  }
  $value = 'INT3IPV6="'.$value.'"';
  fwrite($fp, "### 3rd LAN IPv6\n".$value."\n");

  $value = 'INT4IF="'.$_POST['int4_eth'].'"';
  fwrite($fp, "### 4th LAN Interface\n".$value."\n");

  $value = 'INT4IP="'.tuq($_POST['int4_ip']).'"';
  fwrite($fp, "### 4th LAN IPv4\n".$value."\n");

  $value = 'INT4NM="'.tuq($_POST['int4_mask_ip']).'"';
  fwrite($fp, "### 4th LAN NetMask\n".$value."\n");

  $value = tuq($_POST['int4_ipv6']);
  if ($value !== '' && strpos($value, '/') === FALSE) {
    $value="$value/64";
  }
  $value = 'INT4IPV6="'.$value.'"';
  fwrite($fp, "### 4th LAN IPv6\n".$value."\n");

  $value = 'DMZIF="'.$_POST['dmz_eth'].'"';
  fwrite($fp, "### DMZ Interface\n".$value."\n");

  $value = 'DMZIP="'.tuq($_POST['dmz_ip']).'"';
  fwrite($fp, "### DMZ IPv4\n".$value."\n");

  $value = 'DMZNM="'.tuq($_POST['dmz_mask_ip']).'"';
  fwrite($fp, "### DMZ NetMask\n".$value."\n");

  $value = tuq($_POST['dmz_ipv6']);
  if ($value !== '' && strpos($value, '/') === FALSE) {
    $value="$value/64";
  }
  $value = 'DMZIPV6="'.$value.'"';
  fwrite($fp, "### DMZ IPv6\n".$value."\n");

  $value = 'NODHCP="'.getNODHCP_value().'"';
  fwrite($fp, "### No DHCP on interfaces\n".$value."\n");

  $tokens = explode('~', $_POST['int_autoconf']);
  $x_value = $tokens[0];
  $y_value = $tokens[1];
  $tokens = explode('~', $_POST['int2_autoconf']);
  $x_value .= $tokens[0];
  $y_value .= $tokens[1];
  $tokens = explode('~', $_POST['int3_autoconf']);
  $x_value .= $tokens[0];
  $y_value .= $tokens[1];
  $tokens = explode('~', $_POST['int4_autoconf']);
  $x_value .= $tokens[0];
  $y_value .= $tokens[1];
  $tokens = explode('~', $_POST['dmz_autoconf']);
  $x_value .= $tokens[0];
  $y_value .= $tokens[1];

  $value = 'IPV6_AUTOCONF="'.trim($x_value).'"';
  fwrite($fp, "### IPv6 Autoconfig\n".$value."\n");

  $value = 'IPV6_PREFIX_DELEGATION="'.trim($y_value).'"';
  fwrite($fp, "### IPv6 Prefix Delegation\n".$value."\n");

  $value = 'FWVERS="'.$_POST['firewall'].'"';
  fwrite($fp, "### Firewall Type\n".$value."\n");

  $value = 'NTPSERVS="us.pool.ntp.org"';
  if (isset($_POST['other_ntp_server'], $_POST['ntp_server'])) {
    $t_value = tuq($_POST['other_ntp_server']);
    if ($_POST['ntp_server'] !== '') {
      if ($t_value !== '') {
        $value = 'NTPSERVS="'.$_POST['ntp_server'].' '.$t_value.'"';
      } else {
        $value = 'NTPSERVS="'.$_POST['ntp_server'].'"';
      }
    } elseif ($t_value !== '') {
      $value = 'NTPSERVS="'.$t_value.'"';
    }
  }
  fwrite($fp, "### NTP Servers\n".$value."\n");

  if ($_POST['timezone'] !== '') {
    $value = 'TIMEZONE="'.$_POST['timezone'].'"';
  } else {
    $value = 'TIMEZONE="'.tuq($_POST['other_timezone']).'"';
  }
  fwrite($fp, "### UNIX Timezone\n".$value."\n");

  $value = 'SMTP_SERVER="'.tuq($_POST['smtp_server']).'"';
  fwrite($fp, "### SMTP Server\n".$value."\n");

  $value = 'SMTP_DOMAIN="'.tuq($_POST['smtp_domain']).'"';
  fwrite($fp, "### SMTP Domain\n".$value."\n");

  $value = 'SMTP_AUTH="'.$_POST['smtp_auth'].'"';
  fwrite($fp, "### SMTP Authentication Type\n".$value."\n");

  $value = 'SMTP_PORT="'.tuq($_POST['smtp_port']).'"';
  fwrite($fp, "### SMTP TCP Port\n".$value."\n");

  fwrite($fp, "### SMTP TLS\n");
  if ($_POST['smtp_tls'] === 'no') {
    $value = 'SMTP_TLS="no"';
    fwrite($fp, $value."\n");
    $value = 'SMTP_STARTTLS=""';
    fwrite($fp, $value."\n");
  } elseif ($_POST['smtp_tls'] === 'starttls') {
    $value = 'SMTP_TLS="yes"';
    fwrite($fp, $value."\n");
    $value = 'SMTP_STARTTLS="on"';
    fwrite($fp, $value."\n");
  } else {
    $value = 'SMTP_TLS="yes"';
    fwrite($fp, $value."\n");
    $value = 'SMTP_STARTTLS="off"';
    fwrite($fp, $value."\n");
  }
  $value = 'SMTP_CERTCHECK="'.$_POST['smtp_certcheck'].'"';
  fwrite($fp, $value."\n");
  if ($_POST['smtp_certcheck'] === 'on') {
    $value = 'SMTP_CA="'.tuq($_POST['smtp_ca_cert']).'"';
  } else {
    $value = 'SMTP_CA=""';
  }
  fwrite($fp, $value."\n");

  $value = 'SMTP_USER="'.tuq($_POST['smtp_user']).'"';
  fwrite($fp, "### SMTP Auth Username\n".$value."\n");

  $value = 'SMTP_PASS="'.string2RCconfig(trim($_POST['smtp_pass'])).'"';
  fwrite($fp, "### SMTP Auth Password\n".$value."\n");

  $x_value = '';
  if (isset($_POST['acme_lighttpd'])) {
    $x_value .= ' lighttpd';
  }
  if (isset($_POST['acme_asterisk'])) {
    $x_value .= ' asterisk';
  }
  if (isset($_POST['acme_prosody'])) {
    $x_value .= ' prosody';
  }
  if (isset($_POST['acme_slapd'])) {
    $x_value .= ' slapd';
  }
  $value = 'ACME_SERVICE="'.trim($x_value).'"';
  fwrite($fp, "### ACME Certificate\n".$value."\n");
  $value = 'ACME_ACCOUNT_EMAIL="'.tuq($_POST['acme_account_email']).'"';
  fwrite($fp, $value."\n");

  $value = 'FTPD="'.$_POST['ftp'].'"';
  fwrite($fp, "### FTP Server\n".$value."\n");
  $value = 'FTPD_WRITE="'.$_POST['ftpd_write'].'"';
  fwrite($fp, $value."\n");

  $value = 'TFTPD="'.$_POST['tftp'].'"';
  fwrite($fp, "### TFTP Server\n".$value."\n");

  $value = 'CLI_PROXY_SERVER="'.$_POST['cli_proxy'].'"';
  fwrite($fp, "### CLI Proxy Server\n".$value."\n");

  $value = 'NETSTAT_SERVER="'.$_POST['netstat_server'].'"';
  fwrite($fp, "### NetStat Server\n".$value."\n");

  $x_value = '';
  if (isset($_POST['netstat_EXTIF'])) {
    $x_value .= ' EXTIF';
  }
  if (isset($_POST['netstat_INTIF'])) {
    $x_value .= ' INTIF';
  }
  if (isset($_POST['netstat_INT2IF'])) {
    $x_value .= ' INT2IF';
  }
  if (isset($_POST['netstat_INT3IF'])) {
    $x_value .= ' INT3IF';
  }
  if (isset($_POST['netstat_INT4IF'])) {
    $x_value .= ' INT4IF';
  }
  if (isset($_POST['netstat_DMZIF'])) {
    $x_value .= ' DMZIF';
  }
  if ($x_value === '') {  // set default
    $x_value = 'EXTIF';
  }
  $value = 'NETSTAT_CAPTURE="'.trim($x_value).'"';
  fwrite($fp, "### NetStat Capture Interfaces\n".$value."\n");

  $x_value = $_POST['upnp'];
  $tokens = explode(':', $x_value);
  $value = 'UPNP_ENABLE_NATPMP="'.$tokens[0].'"';
  fwrite($fp, "### UPnP NAT-PMP/PCP\n".$value."\n");
  $value = 'UPNP_ENABLE_UPNP="'.$tokens[1].'"';
  fwrite($fp, "### UPnP Enable\n".$value."\n");

  $x_value = '';
  if (isset($_POST['upnp_INTIF'])) {
    $x_value .= ' INTIF';
  }
  if (isset($_POST['upnp_INT2IF'])) {
    $x_value .= ' INT2IF';
  }
  if (isset($_POST['upnp_INT3IF'])) {
    $x_value .= ' INT3IF';
  }
  if (isset($_POST['upnp_INT4IF'])) {
    $x_value .= ' INT4IF';
  }
  if (isset($_POST['upnp_DMZIF'])) {
    $x_value .= ' DMZIF';
  }
  $value = 'UPNP_LISTEN="'.trim($x_value).'"';
  fwrite($fp, "### UPnP Listen Interfaces\n".$value."\n");

  $value = 'AVAHI_ENABLE="'.$_POST['avahi'].'"';
  fwrite($fp, "### mDNS/DNS-SD\n".$value."\n");

  $value = 'HTTPDIR="'.tuq($_POST['http_dir']).'"';
  fwrite($fp, "### HTTP Server Directory\n".$value."\n");

  $value = isset($_POST['http_cgi']) ? 'HTTPCGI="yes"' : 'HTTPCGI="no"';
  fwrite($fp, "### HTTP CGI\n".$value."\n");

  $value = isset($_POST['http_listing']) ? 'HTTP_LISTING="yes"' : 'HTTP_LISTING="no"';
  fwrite($fp, "### HTTP directory listing\n".$value."\n");

  $value = isset($_POST['http_accesslog']) ? 'HTTP_ACCESSLOG="yes"' : 'HTTP_ACCESSLOG="no"';
  fwrite($fp, "### HTTP access logging\n".$value."\n");

  $value = 'HTTPSDIR="'.tuq($_POST['https_dir']).'"';
  fwrite($fp, "### HTTPS Server Directory\n".$value."\n");

  $value = isset($_POST['https_cgi']) ? 'HTTPSCGI="yes"' : 'HTTPSCGI="no"';
  fwrite($fp, "### HTTPS CGI\n".$value."\n");

  $value = isset($_POST['https_listing']) ? 'HTTPS_LISTING="yes"' : 'HTTPS_LISTING="no"';
  fwrite($fp, "### HTTPS directory listing\n".$value."\n");

  $value = isset($_POST['https_accesslog']) ? 'HTTPS_ACCESSLOG="yes"' : 'HTTPS_ACCESSLOG="no"';
  fwrite($fp, "### HTTPS access logging\n".$value."\n");

  $value = 'HTTPSCERT="'.tuq($_POST['https_cert']).'"';
  if (isset($_POST['submit_self_signed_https']) && isset($_POST['confirm_self_signed_https'])) {
    if (($countryName = getPREFdef($global_prefs, 'dn_country_name_cmdstr')) === '') {
      $countryName = 'US';
    }
    if (($stateName = getPREFdef($global_prefs, 'dn_state_name_cmdstr')) === '') {
      $stateName = 'Nebraska';
    }
    if (($localityName = getPREFdef($global_prefs, 'dn_locality_name_cmdstr')) === '') {
      $localityName = 'Omaha';
    }
    if (($orgName = getPREFdef($global_prefs, 'dn_org_name_cmdstr')) === '') {
      if (($orgName = getPREFdef($global_prefs, 'title_name_cmdstr')) === '') {
        $orgName = 'AstLinux Management';
      }
    }
    if (($orgUnit = getPREFdef($global_prefs, 'dn_org_unit_cmdstr')) === '') {
      $orgUnit = 'Web Interface';
    }
    if (($commonName = tuq($_POST['hostname'])) === '') {
      $commonName = '*';
    }
    if (($email = getPREFdef($global_prefs, 'dn_email_address_cmdstr')) === '') {
      $email = 'info@astlinux-project.org';
    }
    $fname = '/mnt/kd/ssl/webinterface.pem';
    if (opensslCREATEhttpsCert($countryName, $stateName, $localityName, $orgName, $orgUnit, $commonName, $email, $fname)) {
      $value = 'HTTPSCERT="'.$fname.'"';
    }
  }
  fwrite($fp, "### HTTPS Certificate File\n".$value."\n");
  $value = isset($_POST['acme_lighttpd']) ? 'HTTPSCHAIN="/mnt/kd/ssl/https_ca_chain.pem"' : 'HTTPSCHAIN=""';
  fwrite($fp, $value."\n");

  $value = 'PHONEPROV_ALLOW="'.tuq($_POST['phoneprov_allow']).'"';
  fwrite($fp, "### /phoneprov/ Allowed IPs\n".$value."\n");

  $x_value = '';
  if (isset($_POST['openvpn'])) {
    $x_value .= ' openvpn';
  }
  if (isset($_POST['openvpnclient'])) {
    $x_value .= ' openvpnclient';
  }
  if (isset($_POST['ipsec'])) {
    $x_value .= ' ipsec';
  }
  if (isset($_POST['wireguard'])) {
    $x_value .= ' wireguard';
  }
  $value = 'VPN="'.trim($x_value).'"';
  fwrite($fp, "### VPN Type\n".$value."\n");

  fwrite($fp, "### IPv6 DHCPv6 Client Options\n");
  $value = 'DHCPV6_CLIENT_REQUEST_ADDRESS="'.$_POST['dhcpv6_client_request_address'].'"';
  fwrite($fp, $value."\n");
  $value = 'DHCPV6_CLIENT_REQUEST_PREFIX="'.$_POST['dhcpv6_client_request_prefix'].'"';
  fwrite($fp, $value."\n");
  $value = 'DHCPV6_CLIENT_PREFIX_LEN="'.$_POST['dhcpv6_client_prefix_len'].'"';
  fwrite($fp, $value."\n");
  $value = 'DHCPV6_CLIENT_PREFIX_HINT="'.$_POST['dhcpv6_client_prefix_hint'].'"';
  fwrite($fp, $value."\n");

  if (($value = $_POST['ipv6_tunnel_type']) !== '') {
    $value .= '~'.($value === '6to4-relay' ? '0/0' : $_POST['ipv6_tunnel_server']);
    $x_value = $_POST['ipv6_tunnel_endpoint'];
    if ($x_value !== '' && strpos($x_value, '/') === FALSE) {
      $x_value="$x_value/64";
    }
    $value .= '~'.$x_value;
  }
  $value = 'IPV6_TUNNEL="'.$value.'"';
  fwrite($fp, "### IPv6 Tunnel\n".$value."\n");

  fwrite($fp, "### Dynamic DNS\n");
  $value = 'DDCLIENT="'.$_POST['dd_client'].'"';
  fwrite($fp, $value."\n");
  if ($_POST['dd_service'] !== '') {
    $value = 'DDSERVICE="'.$_POST['dd_service'].'"';
  } else {
    $value = 'DDSERVICE="'.tuq($_POST['other_dd_service']).'"';
  }
  fwrite($fp, $value."\n");
  if ($_POST['dd_getip'] !== '') {
    $value = 'DDGETIP="'.$_POST['dd_getip'].'"';
  } else {
    $value = 'DDGETIP="'.tuq($_POST['other_dd_getip']).'"';
  }
  fwrite($fp, $value."\n");
  if ($_POST['dd_getipv6'] !== '') {
    $value = 'DDGETIPV6="'.$_POST['dd_getipv6'].'"';
  } else {
    $value = 'DDGETIPV6="'.tuq($_POST['other_dd_getipv6']).'"';
  }
  fwrite($fp, $value."\n");
  $value = 'DDHOST="'.tuq($_POST['dd_host']).'"';
  fwrite($fp, $value."\n");
  $value = 'DDUSER="'.tuq($_POST['dd_user']).'"';
  fwrite($fp, $value."\n");
  $value = 'DDPASS="'.string2RCconfig(trim($_POST['dd_pass'])).'"';
  fwrite($fp, $value."\n");

  fwrite($fp, "### Safe Asterisk - SIP Monitoring\n");
  $value = 'SAFE_ASTERISK="'.$_POST['safe_asterisk'].'"';
  fwrite($fp, $value."\n");
  $value = 'SAFE_ASTERISK_NOTIFY="'.tuq($_POST['safe_asterisk_notify']).'"';
  fwrite($fp, $value."\n");
  $value = 'SAFE_ASTERISK_NOTIFY_FROM="'.tuq($_POST['safe_asterisk_notify_from']).'"';
  fwrite($fp, $value."\n");
  $value = 'MONITOR_ASTERISK_SIP_TRUNKS="'.tuq($_POST['monitor_sip_trunks']).'"';
  fwrite($fp, $value."\n");
  $value = 'MONITOR_ASTERISK_SIP_PEERS="'.tuq($_POST['monitor_sip_peers']).'"';
  fwrite($fp, $value."\n");
  $value = 'MONITOR_ASTERISK_SIP_STATUS_UPDATES="'.$_POST['monitor_status_updates'].'"';
  fwrite($fp, $value."\n");

  fwrite($fp, "### LDAP Client System Defaults\n");
  if (isset($_POST['ldap_uri'], $_POST['ldap_base'])) {
    $value = 'LDAP_URI="'.tuq($_POST['ldap_uri']).'"';
    fwrite($fp, $value."\n");
    $value = 'LDAP_BASE="'.tuq($_POST['ldap_base']).'"';
    fwrite($fp, $value."\n");
    $value = 'LDAP_DEREF="'.$_POST['ldap_deref'].'"';
    fwrite($fp, $value."\n");
    $value = 'LDAP_TLS_CACERT="'.tuq($_POST['ldap_tls_cacert']).'"';
    fwrite($fp, $value."\n");
    $value = 'LDAP_TLS_REQCERT="'.$_POST['ldap_tls_reqcert'].'"';
    fwrite($fp, $value."\n");
  } else {
    $value = 'LDAP_URI=""';
    fwrite($fp, $value."\n");
    $value = 'LDAP_BASE=""';
    fwrite($fp, $value."\n");
    $value = 'LDAP_DEREF=""';
    fwrite($fp, $value."\n");
    $value = 'LDAP_TLS_CACERT=""';
    fwrite($fp, $value."\n");
    $value = 'LDAP_TLS_REQCERT=""';
    fwrite($fp, $value."\n");
  }

  fwrite($fp, "### UPS Monitoring - Shutdown\n");
  if (isset($_POST['ups_driver'], $_POST['ups_driver_port'])) {
    $value = 'UPS_DRIVER="'.$_POST['ups_driver'].'"';
    fwrite($fp, $value."\n");
    if ($_POST['ups_driver'] === 'snmp-ups' || $_POST['ups_driver'] === 'netxml-ups' || $_POST['ups_driver'] === '') {
      $value = 'UPS_DRIVER_PORT="'.tuq($_POST['ups_driver_port']).'"';
    } else {
      $value = 'UPS_DRIVER_PORT=""';
    }
    fwrite($fp, $value."\n");
  } else {
    $value = 'UPS_DRIVER=""';
    fwrite($fp, $value."\n");
    $value = 'UPS_DRIVER_PORT=""';
    fwrite($fp, $value."\n");
  }
  $value = 'UPS_LISTEN_ALL="'.$_POST['ups_listen_all'].'"';
  fwrite($fp, $value."\n");
  if (isset($_POST['ups_driver']) && $_POST['ups_driver'] === '') {
    $value = 'UPS_MONITOR_HOST="'.tuq($_POST['ups_monitor_host']).'"';
  } else {
    $value = 'UPS_MONITOR_HOST=""';
  }
  fwrite($fp, $value."\n");
  $value = 'UPS_MONITOR_USER="'.tuq($_POST['ups_monitor_user']).'"';
  fwrite($fp, $value."\n");
  $value = 'UPS_MONITOR_PASS="'.string2RCconfig(trim($_POST['ups_monitor_pass'])).'"';
  fwrite($fp, $value."\n");

  $value = 'UPS_NOTIFY="'.tuq($_POST['ups_notify']).'"';
  fwrite($fp, $value."\n");
  $value = 'UPS_NOTIFY_FROM="'.tuq($_POST['ups_notify_from']).'"';
  fwrite($fp, $value."\n");
  $value = 'UPS_KILL_POWER="'.$_POST['ups_kill_power'].'"';
  fwrite($fp, $value."\n");

  fwrite($fp, "### Fossil - Software Configuration Management\n");
  $value = 'FOSSIL_SERVER="'.$_POST['fossil_server'].'"';
  fwrite($fp, $value."\n");
  $value = 'FOSSIL_INCLUDE_DIRS="'.tuq($_POST['fossil_include_dirs']).'"';
  fwrite($fp, $value."\n");
  $value = 'FOSSIL_INCLUDE_FILES="'.tuq($_POST['fossil_include_files']).'"';
  fwrite($fp, $value."\n");

  $value = 'ADNAME=""';
  fwrite($fp, "### Disable Bonjour Broadcasts\n".$value."\n");
  fwrite($fp, "### gui.network.conf - end ###\n");
  fclose($fp);
  if ($isFIRSTtime) {
    if (createUSERconf($USERCONFFILE) === FALSE) {
      return(3);
    }
  }
  return(checkNETWORKsettings());
}

// Function: createUSERconf
//
function createUSERconf($user_conf) {

  if (is_file($user_conf)) {
    return(TRUE);
  }
  if (($fp = @fopen($user_conf, 'wb')) === FALSE) {
    return(FALSE);
  }
  fwrite($fp, "### user.conf - start ###\n");
  fwrite($fp, "###\n###  Advanced Configuration: User System Variables\n###\n");
  fwrite($fp, "###  Define variables here that are not otherwise set in the Network tab.\n###\n");
  fwrite($fp, "###  Variables defined here will override any value set elsewhere.\n###\n");
  fwrite($fp, "###\n\n\n\n");
  fwrite($fp, "### user.conf - end ###\n");
  fclose($fp);

  return(TRUE);
}

// Function: isVARtype
//
function isVARtype($var, $db, $cur_db, $type) {

  $tokens = explode(' ', getVARdef($db, $var, $cur_db));
  foreach ($tokens as $token) {
    if ($token === $type) {
      return(TRUE);
    }
  }
  return(FALSE);
}

// Function: putDNS_DHCP_Html
//
function putDNS_DHCP_Html($db, $cur_db, $varif, $name) {

  $sel = '';
  if ($varif !== '') {
    if (($nodhcp = getVARdef($db, 'NODHCP', $cur_db)) !== '') {
      $tokens = explode(' ', $nodhcp);
      foreach ($tokens as $token) {
        if ($token === $varif) {
          $sel = ' selected="selected"';
          break;
        }
      }
    }
  }
  putHtml('&ndash;');
  putHtml('<select name="'.$name.'">');
  putHtml('<option value="">DNS &amp; DHCP</option>');
  putHtml('<option value="nodhcp"'.$sel.'>DNS only</option>');
  putHtml('</select>');
}

// Function: getNODHCP_value
//
function getNODHCP_value() {

  $entries = array (
    'int_dhcp'  => 'int_eth',
    'int2_dhcp' => 'int2_eth',
    'int3_dhcp' => 'int3_eth',
    'int4_dhcp' => 'int4_eth',
    'dmz_dhcp'  => 'dmz_eth'
  );
  $rtn = '';

  foreach ($entries as $key => $value) {
    if ($_POST[$key] === 'nodhcp') {
      if (($str = $_POST[$value]) !== '') {
        $rtn .= ' '.$str;
      }
    }
  }

  return(trim($rtn));
}

// Function: get_new_ETHinterfaces
//
function get_new_ETHinterfaces(&$eth, $vlans_str) {
  global $USERCONFFILE;

  $vars = explode(' ', $vlans_str);

  if (is_file($USERCONFFILE)) {
    $user_vars = parseRCconf($USERCONFFILE);
    $br_values = array("BRIDGE0" => "br0", "BRIDGE1" => "br1", "BRIDGE2" => "br2", "LXC_BRIDGE0" => "lxcbr0");
    foreach ($br_values as $br_value => $value) {
      if (getVARdef($user_vars, $br_value) !== '') {
        $vars[] = $value;
      }
    }
  }

  foreach ($vars as $var) {
    if (($new = $var) !== '') {
      foreach ($eth as $active_eth) {
        if ($active_eth === $var) {
          $new = '';
          break;
        }
      }
      if ($new !== '') {
        $eth[] = $new;
      }
    }
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! $global_admin) {
    $result = 999;
  } elseif (isset($_POST['submit_save'])) {
    $result = saveNETWORKsettings($NETCONFDIR, $NETCONFFILE);
  } elseif (isset($_POST['submit_edit_failover'])) {
    if (($result = saveNETWORKsettings($NETCONFDIR, $NETCONFFILE)) == 11) {
      header('Location: /admin/failover.php');
      exit;
    }
  } elseif (isset($_POST['submit_edit_firewall'])) {
    $result = saveNETWORKsettings($NETCONFDIR, $NETCONFFILE);
    header('Location: /admin/firewall.php');
    exit;
  } elseif (isset($_POST['submit_edit_plugin'])) {
    $result = saveNETWORKsettings($NETCONFDIR, $NETCONFFILE);
    if (is_writable($file = $_POST['firewall_plugin'])) {
      header('Location: /admin/edit.php?file='.$file);
      exit;
    }
  } elseif (isset($_POST['submit_edit_ntp'])) {
    $result = saveNETWORKsettings($NETCONFDIR, $NETCONFFILE);
    if (is_writable($file = '/mnt/kd/chrony.conf')) {
      header('Location: /admin/edit.php?file='.$file);
      exit;
    }
  } elseif (isset($_POST['submit_test_smtp'])) {
    $result = saveNETWORKsettings($NETCONFDIR, $NETCONFFILE);
    header('Location: /admin/testmail.php');
    exit;
  } elseif (isset($_POST['submit_smtp_aliases'])) {
    $result = saveNETWORKsettings($NETCONFDIR, $NETCONFFILE);
    if (is_writable($file = '/mnt/kd/msmtp-aliases.conf')) {
      header('Location: /admin/edit.php?file='.$file);
      exit;
    }
  } elseif (isset($_POST['submit_dns_hosts'])) {
    $result = saveNETWORKsettings($NETCONFDIR, $NETCONFFILE);
    header('Location: /admin/dnshosts.php');
    exit;
  } elseif (isset($_POST['submit_dns_tls'])) {
    $result = saveNETWORKsettings($NETCONFDIR, $NETCONFFILE);
    header('Location: /admin/dnstls.php');
    exit;
  } elseif (isset($_POST['submit_dnscrypt'])) {
    $result = saveNETWORKsettings($NETCONFDIR, $NETCONFFILE);
    header('Location: /admin/dnscrypt.php');
    exit;
  } elseif (isset($_POST['submit_self_signed_https'])) {
    if (isset($_POST['confirm_self_signed_https'])) {
      if (($result = saveNETWORKsettings($NETCONFDIR, $NETCONFFILE)) == 11) {
        $result = 12;
      }
    } else {
      $result = 2;
    }
  } elseif (isset($_POST['submit_self_signed_sip_tls'])) {
    $result = saveNETWORKsettings($NETCONFDIR, $NETCONFFILE);
    header('Location: /admin/siptlscert.php');
    exit;
  } elseif (isset($_POST['submit_kamailio'])) {
    $result = saveNETWORKsettings($NETCONFDIR, $NETCONFFILE);
    if (is_writable($file = '/mnt/kd/kamailio/kamailio-local.cfg')) {
      header('Location: /admin/edit.php?file='.$file);
      exit;
    } elseif (is_writable($file = '/mnt/kd/kamailio/kamailio.cfg')) {
      header('Location: /admin/edit.php?file='.$file);
      exit;
    }
  } elseif (isset($_POST['submit_slapd'])) {
    $result = saveNETWORKsettings($NETCONFDIR, $NETCONFFILE);
    if (is_writable($file = '/mnt/kd/slapd.conf')) {
      header('Location: /admin/edit.php?file='.$file);
    } else {
      header('Location: /admin/slapd.php');
    }
    exit;
  } elseif (isset($_POST['submit_xmpp'])) {
    $result = saveNETWORKsettings($NETCONFDIR, $NETCONFFILE);
    header('Location: /admin/xmpp.php');
    exit;
  } elseif (isset($_POST['submit_snmp_agent'])) {
    $result = saveNETWORKsettings($NETCONFDIR, $NETCONFFILE);
    if (is_writable($file = '/mnt/kd/snmp/snmpd.conf')) {
      header('Location: /admin/edit.php?file='.$file);
      exit;
    }
  } elseif (isset($_POST['submit_keepalived'])) {
    $result = saveNETWORKsettings($NETCONFDIR, $NETCONFFILE);
    if (is_writable($file = '/mnt/kd/keepalived/keepalived.conf')) {
      header('Location: /admin/edit.php?file='.$file);
      exit;
    }
  } elseif (isset($_POST['submit_monit'])) {
    $result = saveNETWORKsettings($NETCONFDIR, $NETCONFFILE);
    header('Location: /admin/monitconfig.php');
    exit;
  } elseif (isset($_POST['submit_zabbix'])) {
    $result = saveNETWORKsettings($NETCONFDIR, $NETCONFFILE);
    header('Location: /admin/zabbix.php');
    exit;
  } elseif (isset($_POST['submit_edit_vsftpd_conf'])) {
    $result = saveNETWORKsettings($NETCONFDIR, $NETCONFFILE);
    if (is_writable($file = '/mnt/kd/vsftpd.conf')) {
      header('Location: /admin/edit.php?file='.$file);
      exit;
    }
  } elseif (isset($_POST['submit_avahi'])) {
    $result = saveNETWORKsettings($NETCONFDIR, $NETCONFFILE);
    if (is_writable($file = '/mnt/kd/avahi/avahi-daemon.conf')) {
      header('Location: /admin/edit.php?file='.$file);
      exit;
    }
  } elseif (isset($_POST['submit_edit_dnsmasq_conf'])) {
    $result = saveNETWORKsettings($NETCONFDIR, $NETCONFFILE);
    if (is_writable($file = '/mnt/kd/dnsmasq.conf')) {
      header('Location: /admin/edit.php?file='.$file);
      exit;
    }
  } elseif (isset($_POST['submit_edit_dnsmasq_static'])) {
    $result = saveNETWORKsettings($NETCONFDIR, $NETCONFFILE);
    if (is_writable($file = '/mnt/kd/dnsmasq.static')) {
      header('Location: /admin/edit.php?file='.$file);
      exit;
    }
  } elseif (isset($_POST['submit_edit_ipsec'])) {
    $result = saveNETWORKsettings($NETCONFDIR, $NETCONFFILE);
    if (is_writable($file = '/mnt/kd/ipsec/strongswan/ipsec.conf')) {
      header('Location: /admin/edit.php?file='.$file);
      exit;
    }
  } elseif (isset($_POST['submit_edit_wireguard'])) {
    $result = saveNETWORKsettings($NETCONFDIR, $NETCONFFILE);
    header('Location: /admin/wireguard.php');
    exit;
  } elseif (isset($_POST['submit_edit_ddclient'])) {
    $result = saveNETWORKsettings($NETCONFDIR, $NETCONFFILE);
    if (is_writable($file = '/mnt/kd/ddclient.conf')) {
      header('Location: /admin/edit.php?file='.$file);
      exit;
    }
  } elseif (isset($_POST['submit_edit_ldap'])) {
    $result = saveNETWORKsettings($NETCONFDIR, $NETCONFFILE);
    if (is_writable($file = '/mnt/kd/ldap.conf')) {
      header('Location: /admin/edit.php?file='.$file);
      exit;
    }
  } elseif (isset($_POST['submit_edit_ups'])) {
    $result = saveNETWORKsettings($NETCONFDIR, $NETCONFFILE);
    if (is_writable($file = '/mnt/kd/ups/ups.conf')) {
      header('Location: /admin/edit.php?file='.$file);
      exit;
    }
  } elseif (isset($_POST['submit_edit_openvpn'])) {
    $result = saveNETWORKsettings($NETCONFDIR, $NETCONFFILE);
    if (is_writable($file = '/mnt/kd/openvpn/openvpn.conf')) {
      if (is_file($tmpfile = '/mnt/kd/rc.conf.d/gui.openvpn.conf')) {
        @unlink($tmpfile);
      }
      header('Location: /admin/edit.php?file='.$file);
    } else {
      header('Location: /admin/openvpn.php');
    }
    exit;
  } elseif (isset($_POST['submit_edit_openvpnclient'])) {
    $result = saveNETWORKsettings($NETCONFDIR, $NETCONFFILE);
    if (is_writable($file = '/mnt/kd/openvpn/openvpnclient.conf')) {
      if (is_file($tmpfile = '/mnt/kd/rc.conf.d/gui.openvpnclient.conf')) {
        @unlink($tmpfile);
      }
      header('Location: /admin/edit.php?file='.$file);
    } else {
      header('Location: /admin/openvpnclient.php');
    }
    exit;
  } elseif (isset($_POST['submit_tarsnap_backup'])) {
    $result = saveNETWORKsettings($NETCONFDIR, $NETCONFFILE);
    header('Location: /admin/backup.php');
    exit;
  } elseif (isset($_POST['submit_edit_user_conf'])) {
    $result = saveNETWORKsettings($NETCONFDIR, $NETCONFFILE);
    if (createUSERconf($file = $USERCONFFILE) === FALSE) {
      $result = 3;
    }
    if (is_writable($file)) {
      header('Location: /admin/edit.php?file='.$file);
      exit;
    }
  } elseif (isset($_POST['submit_reboot'])) {
    $result = 99;
    $process = $_POST['reboot_restart'];
    if (isset($_POST['confirm_reboot'])) {
      if ($process === 'system') {
        systemREBOOT($myself, 10);
      } elseif ($process === 'pppoe') {
        $result = restartPROCESS($process, 21, $result);
      } elseif ($process === 'ntpd') {
        $result = restartPROCESS($process, 22, $result, 'init');
      } elseif ($process === 'msmtp') {
        $result = restartPROCESS($process, 31, $result, 'init');
      } elseif ($process === 'openvpn') {
        $result = restartPROCESS($process, 24, $result, 'init');
      } elseif ($process === 'openvpnclient') {
        $result = restartPROCESS($process, 29, $result, 'init');
      } elseif ($process === 'asterisk') {
        $result = restartPROCESS($process, 25, $result);
      } elseif ($process === 'iptables') {
        $result = restartPROCESS($process, 26, $result, 'init');
      } elseif ($process === 'dynamicdns') {
        $result = restartPROCESS($process, 27, $result, 'init');
      } elseif ($process === 'dnsmasq') {
        $result = restartPROCESS($process, 28, $result, 'init');
      } elseif ($process === 'miniupnpd') {
        $result = restartPROCESS($process, 34, $result, 'init');
      } elseif ($process === 'ups') {
        $result = restartPROCESS($process, 35, $result, 'init');
      } elseif ($process === 'zabbix') {
        $result = restartPROCESS($process, 36, $result, 'init', 4);
      } elseif ($process === 'stunnel') {
        $result = restartPROCESS($process, 37, $result, 'init');
      } elseif ($process === 'prosody') {
        $result = restartPROCESS($process, 38, $result, 'init');
      } elseif ($process === 'snmpd') {
        $result = restartPROCESS($process, 39, $result, 'init');
      } elseif ($process === 'ldap') {
        $result = restartPROCESS($process, 40, $result, 'init');
      } elseif ($process === 'fop2') {
        $result = restartPROCESS($process, 41, $result, 'init');
      } elseif ($process === 'FOP2') {
        $result = restartPROCESS('fop2', 42, $result, 'reload');
      } elseif ($process === 'slapd') {
        $result = restartPROCESS($process, 43, $result, 'init');
      } elseif ($process === 'darkstat') {
        $result = restartPROCESS($process, 44, $result, 'init');
      } elseif ($process === 'kamailio') {
        $result = restartPROCESS($process, 45, $result, 'init');
      } elseif ($process === 'monit') {
        $result = restartPROCESS($process, 46, $result, 'init');
      } elseif ($process === 'fossil') {
        $result = restartPROCESS($process, 47, $result, 'init');
      } elseif ($process === 'avahi') {
        $result = restartPROCESS($process, 48, $result, 'init');
      } elseif ($process === 'ipsec') {
        $result = restartPROCESS($process, 49, $result, 'init');
      } elseif ($process === 'vsftpd') {
        $result = restartPROCESS($process, 50, $result, 'init');
      } elseif ($process === 'wireguard') {
        $result = restartPROCESS($process, 51, $result, 'init');
      } elseif ($process === 'WIREGUARD') {
        $result = restartPROCESS('wireguard', 52, $result, 'reload');
      } elseif ($process === 'keepalived') {
        $result = restartPROCESS($process, 53, $result, 'init');
      } elseif ($process === 'lxc') {
        $result = restartPROCESS($process, 54, $result, 'init');
      }
    } else {
      $result = 2;
      header('Location: '.$myself.'?reboot_restart='.$process.'&result='.$result);
      exit;
    }
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} else { // Start of HTTP GET
$ACCESS_RIGHTS = 'admin';
require_once '../common/header.php';

  if (is_file($NETCONFFILE)) {
    $db = parseRCconf($NETCONFFILE);
    $cur_db = parseRCconf($CONFFILE);
  } else {
    $db = parseRCconf($CONFFILE);
    $cur_db = NULL;
  }

  if (isset($_GET['reboot_restart'])) {
    $reboot_restart = $_GET['reboot_restart'];
  } else {
    $reboot_restart = 'system';
  }

  $eth = getETHinterfaces();
  get_new_ETHinterfaces($eth, getVARdef($db, 'VLANS', $cur_db));

  putHtml("<center>");
  if (isset($_GET['result'])) {
    $result = $_GET['result'];
    if ($result == 2) {
      putHtml('<p style="color: red;">No Action, check "Confirm" for this action.</p>');
    } elseif ($result == 3) {
      putHtml('<p style="color: red;">Error creating file.</p>');
    } elseif ($result == 10) {
      putHtml('<p style="color: green;">System is Rebooting... back in <span id="count_down"><script language="JavaScript" type="text/javascript">document.write(count_down_secs);</script></span> seconds.</p>');
    } elseif ($result == 11) {
      putHtml('<p style="color: green;">Settings saved, click "Reboot/Restart" to apply any changed settings, a "Reboot System" is required for Interface changes.</p>');
    } elseif ($result == 12) {
      putHtml('<p style="color: green;">Settings saved, a new Self-Signed HTTPS certificate is installed, a "Reboot System" is required to apply changes.</p>');
    } elseif ($result == 21) {
      putHtml('<p style="color: green;">PPPoE has Restarted.</p>');
    } elseif ($result == 22) {
      putHtml('<p style="color: green;">NTP Time'.statusPROCESS('ntpd').'.</p>');
    } elseif ($result == 24) {
      putHtml('<p style="color: green;">OpenVPN Server'.statusPROCESS('openvpn').'.</p>');
    } elseif ($result == 25) {
      putHtml('<p style="color: green;">Asterisk'.statusPROCESS('asterisk').'.</p>');
    } elseif ($result == 26) {
      putHtml('<p style="color: green;">Firewall'.statusPROCESS('iptables').'.</p>');
    } elseif ($result == 27) {
      putHtml('<p style="color: green;">Dynamic DNS'.statusPROCESS('dynamicdns').'.</p>');
    } elseif ($result == 28) {
      putHtml('<p style="color: green;">DNS &amp; DHCP Server'.statusPROCESS('dnsmasq').'.</p>');
    } elseif ($result == 29) {
      putHtml('<p style="color: green;">OpenVPN Client'.statusPROCESS('openvpnclient').'.</p>');
    } elseif ($result == 31) {
      putHtml('<p style="color: green;">SMTP Mail has Restarted.</p>');
    } elseif ($result == 34) {
      putHtml('<p style="color: green;">Universal Plug\'n\'Play'.statusPROCESS('miniupnpd').'.</p>');
    } elseif ($result == 35) {
      putHtml('<p style="color: green;">UPS Daemon'.statusPROCESS('ups').'.</p>');
    } elseif ($result == 36) {
      putHtml('<p style="color: green;">Zabbix Monitoring'.statusPROCESS('zabbix').'.</p>');
    } elseif ($result == 37) {
      putHtml('<p style="color: green;">Stunnel Proxy'.statusPROCESS('stunnel').'.</p>');
    } elseif ($result == 38) {
      putHtml('<p style="color: green;">XMPP Server'.statusPROCESS('prosody').'.</p>');
    } elseif ($result == 39) {
      putHtml('<p style="color: green;">SNMP Server'.statusPROCESS('snmpd').'.</p>');
    } elseif ($result == 40) {
      putHtml('<p style="color: green;">LDAP Client Defaults has been Reloaded.</p>');
    } elseif ($result == 41) {
      putHtml('<p style="color: green;">Asterisk Flash Operating Panel2'.statusPROCESS('fop2').'.</p>');
    } elseif ($result == 42) {
      putHtml('<p style="color: green;">Asterisk Flash Operating Panel2 has been Reloaded.</p>');
    } elseif ($result == 43) {
      putHtml('<p style="color: green;">LDAP Server'.statusPROCESS('slapd').'.</p>');
    } elseif ($result == 44) {
      putHtml('<p style="color: green;">NetStat Server (darkstat)'.statusPROCESS('darkstat').'.</p>');
    } elseif ($result == 45) {
      putHtml('<p style="color: green;">Kamailio SIP Server'.statusPROCESS('kamailio').'.</p>');
    } elseif ($result == 46) {
      putHtml('<p style="color: green;">Monit Monitoring'.statusPROCESS('monit').'.</p>');
    } elseif ($result == 47) {
      putHtml('<p style="color: green;">Fossil Server'.statusPROCESS('fossil').'.</p>');
    } elseif ($result == 48) {
      putHtml('<p style="color: green;">mDNS/DNS-SD (Avahi)'.statusPROCESS('avahi').'.</p>');
    } elseif ($result == 49) {
      putHtml('<p style="color: green;">IPsec VPN (strongSwan)'.statusPROCESS('ipsec').'.</p>');
    } elseif ($result == 50) {
      putHtml('<p style="color: green;">FTP Server'.statusPROCESS('vsftpd').'.</p>');
    } elseif ($result == 51) {
      putHtml('<p style="color: green;">WireGuard VPN'.statusPROCESS('wireguard').'.</p>');
    } elseif ($result == 52) {
      putHtml('<p style="color: green;">WireGuard VPN has been Reloaded.</p>');
    } elseif ($result == 53) {
      putHtml('<p style="color: green;">Keepalived'.statusPROCESS('keepalived').'.</p>');
    } elseif ($result == 54) {
      putHtml('<p style="color: green;">Linux Containers'.statusPROCESS('lxc').'.</p>');
    } elseif ($result == 99) {
      putHtml('<p style="color: red;">Action Failed.</p>');
    } elseif ($result == 100) {
      putHtml('<p style="color: red;">Error in Network Configuration, an Interface is used more than once.</p>');
    } elseif ($result == 101) {
      putHtml('<p style="color: red;">Error in Network Configuration, DMZ requires a LAN to also be defined.</p>');
    } elseif ($result == 102) {
      putHtml('<p style="color: red;">Warning! Firewall is enabled, but not configured, click "Firewall Configuration" and save.</p>');
    } elseif ($result == 103) {
      putHtml('<p style="color: red;">Error in Network Configuration, Invalid Timezone setting.</p>');
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
  <script language="JavaScript" type="text/javascript">
  //<![CDATA[
  function upnp_change() {
    var form = document.getElementById("iform");
    switch (form.upnp.selectedIndex) {
      case 0: // disabled
        break;
      case 1:
      case 2:
      case 3:
        alert('WARNING: Enabling either NAT-PMP/PCP or UPnP has security implications!\
\n\nNAT EXT->LAN rules can be created automatically.\
\n\nIf you must, try NAT-PMP/PCP only.');
        break;
    }
  }
  //]]>
  </script>
  <center>
  <table class="layout"><tr><td><center>
  <form id="iform" method="post" action="<?php echo $myself;?>">
  <table width="100%" class="stdtable">
  <tr><td style="text-align: center;" colspan="2">
  <h2>Network Configuration Settings:</h2>
  </td></tr><tr><td width="240" style="text-align: center;">
  <input type="submit" class="formbtn" value="Save Settings" name="submit_save" />
  </td><td class="dialogText" style="text-align: center;">
  <input type="submit" class="formbtn" value="Reboot/Restart" name="submit_reboot" />
<?php
  putHtml('&ndash;');
  putHtml('<select name="reboot_restart">');
  $sel = ($reboot_restart === 'system') ? ' selected="selected"' : '';
  putHtml('<option value="system"'.$sel.'>Reboot System</option>');
  if (is_file('/etc/ppp/pppoe.conf')) {
    $sel = ($reboot_restart === 'pppoe') ? ' selected="selected"' : '';
    putHtml('<option value="pppoe"'.$sel.'>Restart PPPoE</option>');
  }
  $sel = ($reboot_restart === 'iptables') ? ' selected="selected"' : '';
  putHtml('<option value="iptables"'.$sel.'>Restart Firewall</option>');
  $sel = ($reboot_restart === 'dnsmasq') ? ' selected="selected"' : '';
  putHtml('<option value="dnsmasq"'.$sel.'>Restart DNS &amp; DHCP</option>');
  $sel = ($reboot_restart === 'dynamicdns') ? ' selected="selected"' : '';
  putHtml('<option value="dynamicdns"'.$sel.'>Restart Dynamic DNS</option>');
  $sel = ($reboot_restart === 'ntpd') ? ' selected="selected"' : '';
  putHtml('<option value="ntpd"'.$sel.'>Restart NTP Time</option>');
  $sel = ($reboot_restart === 'msmtp') ? ' selected="selected"' : '';
  putHtml('<option value="msmtp"'.$sel.'>Restart SMTP Mail</option>');
  $sel = ($reboot_restart === 'openvpn') ? ' selected="selected"' : '';
  putHtml('<option value="openvpn"'.$sel.'>Restart OpenVPN Server</option>');
  $sel = ($reboot_restart === 'openvpnclient') ? ' selected="selected"' : '';
  putHtml('<option value="openvpnclient"'.$sel.'>Restart OpenVPN Client</option>');
  $sel = ($reboot_restart === 'ipsec') ? ' selected="selected"' : '';
  putHtml('<option value="ipsec"'.$sel.'>Restart IPsec strongSwan</option>');
  $sel = ($reboot_restart === 'wireguard') ? ' selected="selected"' : '';
  putHtml('<option value="wireguard"'.$sel.'>Restart WireGuard VPN</option>');
  $sel = ($reboot_restart === 'WIREGUARD') ? ' selected="selected"' : '';
  putHtml('<option value="WIREGUARD"'.$sel.'>Reload WireGuard VPN</option>');
  $sel = ($reboot_restart === 'fossil') ? ' selected="selected"' : '';
  putHtml('<option value="fossil"'.$sel.'>Restart Fossil Server</option>');
  $sel = ($reboot_restart === 'vsftpd') ? ' selected="selected"' : '';
  putHtml('<option value="vsftpd"'.$sel.'>Restart FTP Server</option>');
  $sel = ($reboot_restart === 'ldap') ? ' selected="selected"' : '';
  putHtml('<option value="ldap"'.$sel.'>Reload LDAP Client</option>');
  $sel = ($reboot_restart === 'slapd') ? ' selected="selected"' : '';
  putHtml('<option value="slapd"'.$sel.'>Restart LDAP Server</option>');
  $sel = ($reboot_restart === 'avahi') ? ' selected="selected"' : '';
  putHtml('<option value="avahi"'.$sel.'>Restart mDNS/DNS-SD</option>');
  $sel = ($reboot_restart === 'monit') ? ' selected="selected"' : '';
  putHtml('<option value="monit"'.$sel.'>Restart Monit Monitor</option>');
  $sel = ($reboot_restart === 'darkstat') ? ' selected="selected"' : '';
  putHtml('<option value="darkstat"'.$sel.'>Restart NetStat Server</option>');
  $sel = ($reboot_restart === 'snmpd') ? ' selected="selected"' : '';
  putHtml('<option value="snmpd"'.$sel.'>Restart SNMP Server</option>');
  $sel = ($reboot_restart === 'stunnel') ? ' selected="selected"' : '';
  putHtml('<option value="stunnel"'.$sel.'>Restart Stunnel Proxy</option>');
  $sel = ($reboot_restart === 'miniupnpd') ? ' selected="selected"' : '';
  putHtml('<option value="miniupnpd"'.$sel.'>Restart Univ. Plug\'n\'Play</option>');
  $sel = ($reboot_restart === 'ups') ? ' selected="selected"' : '';
  putHtml('<option value="ups"'.$sel.'>Restart UPS Daemon</option>');
  $sel = ($reboot_restart === 'prosody') ? ' selected="selected"' : '';
  putHtml('<option value="prosody"'.$sel.'>Restart XMPP Server</option>');
  if (is_file('/etc/init.d/zabbix')) {
    $sel = ($reboot_restart === 'zabbix') ? ' selected="selected"' : '';
    putHtml('<option value="zabbix"'.$sel.'>Restart Zabbix Monitor</option>');
  }
  $sel = ($reboot_restart === 'asterisk') ? ' selected="selected"' : '';
  putHtml('<option value="asterisk"'.$sel.'>Restart Asterisk</option>');
  if (is_file('/etc/init.d/keepalived')) {
    $sel = ($reboot_restart === 'keepalived') ? ' selected="selected"' : '';
    putHtml('<option value="keepalived"'.$sel.'>Restart Keepalived</option>');
  }
  if (is_file('/etc/init.d/lxc')) {
    $sel = ($reboot_restart === 'lxc') ? ' selected="selected"' : '';
    putHtml('<option value="lxc"'.$sel.'>Restart Linux Containers</option>');
  }
  if (is_addon_package('fop2')) {
    $sel = ($reboot_restart === 'fop2') ? ' selected="selected"' : '';
    putHtml('<option value="fop2"'.$sel.'>Restart Asterisk FOP2</option>');
    $sel = ($reboot_restart === 'FOP2') ? ' selected="selected"' : '';
    putHtml('<option value="FOP2"'.$sel.'>Reload Asterisk FOP2</option>');
  }
  if (is_file('/etc/init.d/kamailio')) {
    $sel = ($reboot_restart === 'kamailio') ? ' selected="selected"' : '';
    putHtml('<option value="kamailio"'.$sel.'>Restart Kamailio</option>');
  }
  putHtml('</select>');
  putHtml('&ndash;');
?>
  <input type="checkbox" value="reboot" name="confirm_reboot" />&nbsp;Confirm
  </td></tr></table>
  <table class="stdtable">
  <tr class="dtrow0"><td width="120">&nbsp;</td><td width="120">&nbsp;</td><td>&nbsp;</td><td width="120">&nbsp;</td><td width="120">&nbsp;</td><td width="120">&nbsp;</td></tr>
  <tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="3">
  <strong>External Interface:</strong>
  <select name="ext_eth">
<?php
  if (($n = arrayCount($eth)) > 0) {
    for ($i = 0; $i < $n; $i++) {
      if (getVARdef($db, 'EXTIF', $cur_db) === 'ppp0') {
        $sel = (getVARdef($db, 'PPPOEIF', $cur_db) === $eth[$i]) ? ' selected="selected"' : '';
      } else {
        $sel = (getVARdef($db, 'EXTIF', $cur_db) === $eth[$i]) ? ' selected="selected"' : '';
      }
      putHtml('<option value="'.$eth[$i].'"'.$sel.'>'.$eth[$i].'</option>');
    }
  }
  putHtml('</select>');
  putHtml('</td><td class="dialogText" style="text-align: right;" colspan="3">');
  putHtml('<strong>IP Version:</strong>');
  putHtml('<select name="ipv6">');
  putHtml('<option value="">IPv4-Only</option>');
  $sel = (getVARdef($db, 'IPV6', $cur_db) === 'yes') ? ' selected="selected"' : '';
  putHtml('<option value="yes"'.$sel.'>IPv4 &amp; IPv6</option>');
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('Connection Type:');
  putHtml('<select name="ip_type">');
  putHtml('<option value="dhcp">DHCP</option>');
  $sel = (getVARdef($db, 'EXTIP', $cur_db) === '' && getVARdef($db, 'DHCPV6_CLIENT_ENABLE', $cur_db) === 'yes') ? ' selected="selected"' : '';
  putHtml('<option value="dhcp-dhcpv6"'.$sel.'>DHCP/DHCPv6</option>');
  $sel = (getVARdef($db, 'EXTIP', $cur_db) !== '' && getVARdef($db, 'EXTIF', $cur_db) !== 'ppp0' && getVARdef($db, 'DHCPV6_CLIENT_ENABLE', $cur_db) !== 'yes') ? ' selected="selected"' : '';
  putHtml('<option value="static"'.$sel.'>Static IP</option>');
  $sel = (getVARdef($db, 'EXTIP', $cur_db) !== '' && getVARdef($db, 'EXTIF', $cur_db) !== 'ppp0' && getVARdef($db, 'DHCPV6_CLIENT_ENABLE', $cur_db) === 'yes') ? ' selected="selected"' : '';
  putHtml('<option value="static-dhcpv6"'.$sel.'>Static IPv4/DHCPv6</option>');
  $sel = (getVARdef($db, 'EXTIF', $cur_db) === 'ppp0') ? ' selected="selected"' : '';
  putHtml('<option value="pppoe"'.$sel.'>PPPoE</option>');
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="2">');
  $value = getVARdef($db, 'HOSTNAME', $cur_db);
  putHtml('Hostname:<input type="text" size="24" maxlength="32" value="'.$value.'" name="hostname" /></td>');
  putHtml('<td style="text-align: center;" colspan="4">');
  $value = getVARdef($db, 'DOMAIN', $cur_db);
  putHtml('Domain:<input type="text" size="36" maxlength="128" value="'.$value.'" name="domain" />');
  $sel = (getVARdef($db, 'LOCALDNS_LOCAL_DOMAIN', $cur_db) !== 'no') ? ' checked="checked"' : '';
  putHtml('&ndash;&nbsp;<input type="checkbox" value="local_domain" name="local_domain"'.$sel.' />&nbsp;Local Domain</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  $value = getVARdef($db, 'DNS', $cur_db);
  if (isDNS_TLS() || isDNSCRYPT()) {
    putHtml('DNS:&nbsp;['.(isDNS_TLS() ? 'DNS-TLS' : 'DNSCrypt').' Proxy Server Enabled]<input type="hidden" value="'.$value.'" name="dns" /></td></tr>');
  } else {
    putHtml('DNS:<input type="text" size="72" maxlength="256" value="'.$value.'" name="dns" />&nbsp;<i>(IPv4 and/or IPv6)</i></td></tr>');
  }
  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="4">');
  $value = getVARdef($db, 'VLANS', $cur_db);
  putHtml('VLANS:<input type="text" size="36" maxlength="64" value="'.$value.'" name="vlans" />&nbsp;<i>(ethN.NN&nbsp;ethN.NN)</i></td>');
  putHtml('<td style="text-align: left;" colspan="2">');
  $sel = (getVARdef($db, 'VLANCOS', $cur_db) !== '') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="vlan_cos" name="vlan_cos"'.$sel.' />&nbsp;VLAN COS</td></tr>');

  putHtml('<tr class="dtrow1"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>External Static IPv4 Settings:</strong>&nbsp;<i>(Cleared for DHCP)</i>');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="2">');
  $value = getVARdef($db, 'EXTIP', $cur_db);
  putHtml('Static IPv4:<input type="text" size="18" maxlength="15" value="'.$value.'" name="static_ip" /></td>');
  putHtml('<td style="text-align: center;" colspan="2">');
  $value = getVARdef($db, 'EXTNM', $cur_db);
  putHtml('NetMask:<input type="text" size="18" maxlength="15" value="'.$value.'" name="mask_ip" /></td>');
  putHtml('<td style="text-align: right;" colspan="2">');
  $value = getVARdef($db, 'EXTGW', $cur_db);
  putHtml('IPv4 Gateway:<input type="text" size="18" maxlength="15" value="'.$value.'" name="gateway_ip" /></td></tr>');

  putHtml('<tr class="dtrow1"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>External Static IPv6 Settings:</strong>');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="3">');
  $value = getVARdef($db, 'EXTIPV6', $cur_db);
  putHtml('Static IPv6/nn:<input type="text" size="38" maxlength="43" value="'.$value.'" name="static_ipv6" /></td>');
  putHtml('<td style="text-align: left;" colspan="3">');
  $value = getVARdef($db, 'EXTGWIPV6', $cur_db);
  putHtml('IPv6 Gateway:<input type="text" size="38" maxlength="39" value="'.$value.'" name="gateway_ipv6" /></td></tr>');

  putHtml('<tr class="dtrow1"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>External DHCPv6 Client Settings:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="3">');
  $value = getVARdef($db, 'DHCPV6_CLIENT_REQUEST_ADDRESS', $cur_db);
  putHtml('DHCPv6 Client Address:');
  putHtml('<select name="dhcpv6_client_request_address">');
  putHtml('<option value="no">disabled</option>');
  $sel = ($value !== 'no') ? ' selected="selected"' : '';
  putHtml('<option value="yes"'.$sel.'>enabled</option>');
  putHtml('</select></td>');
  putHtml('<td style="text-align: left;" colspan="3">');
  if (($dhcpv6_client_prefix_len = getVARdef($db, 'DHCPV6_CLIENT_PREFIX_LEN', $cur_db)) === '') {
    $dhcpv6_client_prefix_len = '60';
  }
  putHtml('DHCPv6 Prefix Length:');
  putHtml('<select name="dhcpv6_client_prefix_len">');
  foreach ($select_dhcpv6_prefix_len as $key => $value) {
    $sel = ($dhcpv6_client_prefix_len == $value) ? ' selected="selected"' : '';
    putHtml('<option value="'.$value.'"'.$sel.'>'.$key.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="3">');
  $value = getVARdef($db, 'DHCPV6_CLIENT_REQUEST_PREFIX', $cur_db);
  putHtml('DHCPv6 Prefix Delegation:');
  putHtml('<select name="dhcpv6_client_request_prefix">');
  putHtml('<option value="no">disabled</option>');
  $sel = ($value !== 'no') ? ' selected="selected"' : '';
  putHtml('<option value="yes"'.$sel.'>enabled</option>');
  putHtml('</select></td>');
  putHtml('<td style="text-align: left;" colspan="3">');
  $value = getVARdef($db, 'DHCPV6_CLIENT_PREFIX_HINT', $cur_db);
  putHtml('DHCPv6 Prefix Length Hint:');
  putHtml('<select name="dhcpv6_client_prefix_hint">');
  putHtml('<option value="no">disabled</option>');
  $sel = ($value !== 'no') ? ' selected="selected"' : '';
  putHtml('<option value="yes"'.$sel.'>enabled</option>');
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>External PPPoE Settings:</strong>');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="3">');
  $value = getVARdef($db, 'PPPOEUSER', $cur_db);
  putHtml('PPPoE Username:<input type="text" size="38" maxlength="64" value="'.$value.'" name="user_pppoe" /></td>');
  putHtml('<td style="text-align: left;" colspan="3">');
  $value = getVARdef($db, 'PPPOEPASS', $cur_db);
  $value = htmlspecialchars(RCconfig2string($value));
  putHtml('PPPoE Password:<input type="password" size="24" maxlength="64" value="'.$value.'" name="pass_pppoe" /></td></tr>');

  putHtml('<tr class="dtrow0"><td colspan="6">&nbsp;</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>External Failover Interface:</strong>');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('<strong>Failover Interface:</strong>');
  putHtml('<select name="ext2_eth">');
  putHtml('<option value="">none</option>');
  $varif = getVARdef($db, 'EXT2IF', $cur_db);
  if (($n = arrayCount($eth)) > 0) {
    for ($i = 0; $i < $n; $i++) {
      $sel = ($varif === $eth[$i]) ? ' selected="selected"' : '';
      putHtml('<option value="'.$eth[$i].'"'.$sel.'>'.$eth[$i].'</option>');
    }
  }
  putHtml('</select>');
  putHtml('&ndash;');
  putHtml('<input type="submit" value="WAN Failover Configuration" name="submit_edit_failover" class="button" /></td></tr>');

  putHtml('<tr class="dtrow0"><td colspan="6">&nbsp;</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Internal Interfaces:</strong>');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('<strong>1st LAN Interface:</strong>');
  putHtml('<select name="int_eth">');
  putHtml('<option value="">none</option>');
  $varif = getVARdef($db, 'INTIF', $cur_db);
  if (($n = arrayCount($eth)) > 0) {
    for ($i = 0; $i < $n; $i++) {
      $sel = ($varif === $eth[$i]) ? ' selected="selected"' : '';
      putHtml('<option value="'.$eth[$i].'"'.$sel.'>'.$eth[$i].'</option>');
    }
  }
  putHtml('</select>');
  putDNS_DHCP_Html($db, $cur_db, $varif, 'int_dhcp');
  $value = getVARdef($db, 'INTIP', $cur_db);
  putHtml('&ndash;&nbsp;IPv4:<input type="text" size="16" maxlength="15" value="'.$value.'" name="int_ip" />');
  if (($value = getVARdef($db, 'INTNM', $cur_db)) === '') {
    $value = '255.255.255.0';
  }
  putHtml('NetMask:<input type="text" size="16" maxlength="15" value="'.$value.'" name="int_mask_ip" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('&nbsp;&nbsp;IPv6 Autoconfig:');
  putHtml('<select name="int_autoconf">');
  putHtml('<option value="">disabled</option>');
  if (isVARtype('IPV6_PREFIX_DELEGATION', $db, $cur_db, 'INTIF')) {
    $sel1 = '';
    $sel2 = ' selected="selected"';
  } else {
    $sel1 = isVARtype('IPV6_AUTOCONF', $db, $cur_db, 'INTIF') ? ' selected="selected"' : '';
    $sel2 = '';
  }
  putHtml('<option value=" INTIF~"'.$sel1.'>enabled</option>');
  putHtml('<option value=" INTIF~ INTIF"'.$sel2.'>Assign GUA Prefix</option>');
  putHtml('</select>');
  $value = getVARdef($db, 'INTIPV6', $cur_db);
  putHtml('&ndash;&nbsp;IPv6/nn:<input type="text" size="45" maxlength="43" value="'.$value.'" name="int_ipv6" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('<strong>2nd LAN Interface:</strong>');
  putHtml('<select name="int2_eth">');
  putHtml('<option value="">none</option>');
  $varif = getVARdef($db, 'INT2IF', $cur_db);
  if (($n = arrayCount($eth)) > 0) {
    for ($i = 0; $i < $n; $i++) {
      $sel = ($varif === $eth[$i]) ? ' selected="selected"' : '';
      putHtml('<option value="'.$eth[$i].'"'.$sel.'>'.$eth[$i].'</option>');
    }
  }
  putHtml('</select>');
  putDNS_DHCP_Html($db, $cur_db, $varif, 'int2_dhcp');
  $value = getVARdef($db, 'INT2IP', $cur_db);
  putHtml('&ndash;&nbsp;IPv4:<input type="text" size="16" maxlength="15" value="'.$value.'" name="int2_ip" />');
  if (($value = getVARdef($db, 'INT2NM', $cur_db)) === '') {
    $value = '255.255.255.0';
  }
  putHtml('NetMask:<input type="text" size="16" maxlength="15" value="'.$value.'" name="int2_mask_ip" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('&nbsp;&nbsp;IPv6 Autoconfig:');
  putHtml('<select name="int2_autoconf">');
  putHtml('<option value="">disabled</option>');
  if (isVARtype('IPV6_PREFIX_DELEGATION', $db, $cur_db, 'INT2IF')) {
    $sel1 = '';
    $sel2 = ' selected="selected"';
  } else {
    $sel1 = isVARtype('IPV6_AUTOCONF', $db, $cur_db, 'INT2IF') ? ' selected="selected"' : '';
    $sel2 = '';
  }
  putHtml('<option value=" INT2IF~"'.$sel1.'>enabled</option>');
  putHtml('<option value=" INT2IF~ INT2IF"'.$sel2.'>Assign GUA Prefix</option>');
  putHtml('</select>');
  $value = getVARdef($db, 'INT2IPV6', $cur_db);
  putHtml('&ndash;&nbsp;IPv6/nn:<input type="text" size="45" maxlength="43" value="'.$value.'" name="int2_ipv6" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('<strong>3rd LAN Interface:</strong>');
  putHtml('<select name="int3_eth">');
  putHtml('<option value="">none</option>');
  $varif = getVARdef($db, 'INT3IF', $cur_db);
  if (($n = arrayCount($eth)) > 0) {
    for ($i = 0; $i < $n; $i++) {
      $sel = ($varif === $eth[$i]) ? ' selected="selected"' : '';
      putHtml('<option value="'.$eth[$i].'"'.$sel.'>'.$eth[$i].'</option>');
    }
  }
  putHtml('</select>');
  putDNS_DHCP_Html($db, $cur_db, $varif, 'int3_dhcp');
  $value = getVARdef($db, 'INT3IP', $cur_db);
  putHtml('&ndash;&nbsp;IPv4:<input type="text" size="16" maxlength="15" value="'.$value.'" name="int3_ip" />');
  if (($value = getVARdef($db, 'INT3NM', $cur_db)) === '') {
    $value = '255.255.255.0';
  }
  putHtml('NetMask:<input type="text" size="16" maxlength="15" value="'.$value.'" name="int3_mask_ip" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('&nbsp;&nbsp;IPv6 Autoconfig:');
  putHtml('<select name="int3_autoconf">');
  putHtml('<option value="">disabled</option>');
  if (isVARtype('IPV6_PREFIX_DELEGATION', $db, $cur_db, 'INT3IF')) {
    $sel1 = '';
    $sel2 = ' selected="selected"';
  } else {
    $sel1 = isVARtype('IPV6_AUTOCONF', $db, $cur_db, 'INT3IF') ? ' selected="selected"' : '';
    $sel2 = '';
  }
  putHtml('<option value=" INT3IF~"'.$sel1.'>enabled</option>');
  putHtml('<option value=" INT3IF~ INT3IF"'.$sel2.'>Assign GUA Prefix</option>');
  putHtml('</select>');
  $value = getVARdef($db, 'INT3IPV6', $cur_db);
  putHtml('&ndash;&nbsp;IPv6/nn:<input type="text" size="45" maxlength="43" value="'.$value.'" name="int3_ipv6" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('<strong>4th LAN Interface:</strong>');
  putHtml('<select name="int4_eth">');
  putHtml('<option value="">none</option>');
  $varif = getVARdef($db, 'INT4IF', $cur_db);
  if (($n = arrayCount($eth)) > 0) {
    for ($i = 0; $i < $n; $i++) {
      $sel = ($varif === $eth[$i]) ? ' selected="selected"' : '';
      putHtml('<option value="'.$eth[$i].'"'.$sel.'>'.$eth[$i].'</option>');
    }
  }
  putHtml('</select>');
  putDNS_DHCP_Html($db, $cur_db, $varif, 'int4_dhcp');
  $value = getVARdef($db, 'INT4IP', $cur_db);
  putHtml('&ndash;&nbsp;IPv4:<input type="text" size="16" maxlength="15" value="'.$value.'" name="int4_ip" />');
  if (($value = getVARdef($db, 'INT4NM', $cur_db)) === '') {
    $value = '255.255.255.0';
  }
  putHtml('NetMask:<input type="text" size="16" maxlength="15" value="'.$value.'" name="int4_mask_ip" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('&nbsp;&nbsp;IPv6 Autoconfig:');
  putHtml('<select name="int4_autoconf">');
  putHtml('<option value="">disabled</option>');
  if (isVARtype('IPV6_PREFIX_DELEGATION', $db, $cur_db, 'INT4IF')) {
    $sel1 = '';
    $sel2 = ' selected="selected"';
  } else {
    $sel1 = isVARtype('IPV6_AUTOCONF', $db, $cur_db, 'INT4IF') ? ' selected="selected"' : '';
    $sel2 = '';
  }
  putHtml('<option value=" INT4IF~"'.$sel1.'>enabled</option>');
  putHtml('<option value=" INT4IF~ INT4IF"'.$sel2.'>Assign GUA Prefix</option>');
  putHtml('</select>');
  $value = getVARdef($db, 'INT4IPV6', $cur_db);
  putHtml('&ndash;&nbsp;IPv6/nn:<input type="text" size="45" maxlength="43" value="'.$value.'" name="int4_ipv6" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('<strong>The DMZ Interface:</strong>');
  putHtml('<select name="dmz_eth">');
  putHtml('<option value="">none</option>');
  $varif = getVARdef($db, 'DMZIF', $cur_db);
  if (($n = arrayCount($eth)) > 0) {
    for ($i = 0; $i < $n; $i++) {
      $sel = ($varif === $eth[$i]) ? ' selected="selected"' : '';
      putHtml('<option value="'.$eth[$i].'"'.$sel.'>'.$eth[$i].'</option>');
    }
  }
  putHtml('</select>');
  putDNS_DHCP_Html($db, $cur_db, $varif, 'dmz_dhcp');
  $value = getVARdef($db, 'DMZIP', $cur_db);
  putHtml('&ndash;&nbsp;IPv4:<input type="text" size="16" maxlength="15" value="'.$value.'" name="dmz_ip" />');
  if (($value = getVARdef($db, 'DMZNM', $cur_db)) === '') {
    $value = '255.255.255.0';
  }
  putHtml('NetMask:<input type="text" size="16" maxlength="15" value="'.$value.'" name="dmz_mask_ip" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('&nbsp;&nbsp;IPv6 Autoconfig:');
  putHtml('<select name="dmz_autoconf">');
  putHtml('<option value="">disabled</option>');
  if (isVARtype('IPV6_PREFIX_DELEGATION', $db, $cur_db, 'DMZIF')) {
    $sel1 = '';
    $sel2 = ' selected="selected"';
  } else {
    $sel1 = isVARtype('IPV6_AUTOCONF', $db, $cur_db, 'DMZIF') ? ' selected="selected"' : '';
    $sel2 = '';
  }
  putHtml('<option value=" DMZIF~"'.$sel1.'>enabled</option>');
  putHtml('<option value=" DMZIF~ DMZIF"'.$sel2.'>Assign GUA Prefix</option>');
  putHtml('</select>');
  $value = getVARdef($db, 'DMZIPV6', $cur_db);
  putHtml('&ndash;&nbsp;IPv6/nn:<input type="text" size="45" maxlength="43" value="'.$value.'" name="dmz_ipv6" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow0"><td colspan="6">&nbsp;</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Firewall Configuration:</strong>');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('Firewall:');
  putHtml('<select name="firewall">');
  putHtml('<option value="">disabled</option>');
  $sel = (getVARdef($db, 'FWVERS', $cur_db) === 'arno') ? ' selected="selected"' : '';
  putHtml('<option value="arno"'.$sel.'>enabled</option>');
  putHtml('</select>');
  putHtml('&ndash;');
  putHtml('<input type="submit" value="Firewall Configuration" name="submit_edit_firewall" class="button" /></td></tr>');
  if (($plugins = getARNOplugins()) !== FALSE) {
    putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
    putHtml('Firewall Plugins:');
    putHtml('<select name="firewall_plugin">');
    foreach ($plugins as $key => $value) {
      putHtml('<option value="'.$key.'">'.basename($key, '.conf').'&nbsp;['.substr($value, 2).']</option>');
    }
    putHtml('</select>');
    putHtml('&ndash;');
    putHtml('<input type="submit" value="Configure Plugin" name="submit_edit_plugin" class="button" /></td></tr>');
  }

  putHtml('<tr class="dtrow0"><td colspan="6">&nbsp;</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Network Time Settings:</strong>');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('NTP Server:');
  if (! is_file('/mnt/kd/chrony.conf')) {
    if (($t_value = getVARdef($db, 'NTPSERVS', $cur_db)) === '') {
      $t_value = getVARdef($db, 'NTPSERV', $cur_db);
    }
    putHtml('<select name="ntp_server">');
    foreach ($select_ntp as $key => $value) {
      if (strcasecmp($t_value, $value) == 0) {
        $sel = ' selected="selected"';
        $t_value = '';
      } else {
        $sel = '';
      }
      putHtml('<option value="'.$value.'"'.$sel.'>'.$key.'</option>');
    }
    putHtml('</select>');
    putHtml('<input type="text" size="56" maxlength="200" value="'.$t_value.'" name="other_ntp_server" /></td></tr>');
  } else {
    putHtml('<input type="submit" value="NTP Configuration" name="submit_edit_ntp" class="button" /></td></tr>');
  }
  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('Timezone:');
  $timezones = getTimezoneList();
  if (($t_value = getVARdef($db, 'TIMEZONE', $cur_db)) === '') {
    $t_value = 'UTC';
  }
  putHtml('<select name="timezone">');
  putHtml('<option value="">User Defined&nbsp;&nbsp;&nbsp;&gt;&gt;&gt;</option>');
  foreach ($timezones as $value) {
    if (strcmp($t_value, $value) == 0) {
      $sel = ' selected="selected"';
      $t_value = '';
    } else {
      $sel = '';
    }
    putHtml('<option value="'.$value.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('<input type="text" size="32" maxlength="64" value="'.$t_value.'" name="other_timezone" /></td></tr>');

  putHtml('<tr class="dtrow0"><td colspan="6">&nbsp;</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Outbound SMTP Mail Relay:</strong>');
  if (is_file('/usr/sbin/testmail')) {
    putHtml('&nbsp;<input type="submit" value="Test SMTP Mail Relay" name="submit_test_smtp" class="button" />');
  }
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="3">');
  $value = getVARdef($db, 'SMTP_SERVER', $cur_db);
  putHtml('SMTP Server:<input type="text" size="32" maxlength="64" value="'.$value.'" name="smtp_server" /></td>');
  putHtml('<td style="text-align: left;" colspan="3">');
  $value = getVARdef($db, 'SMTP_DOMAIN', $cur_db);
  putHtml('SMTP Domain:<input type="text" size="32" maxlength="64" value="'.$value.'" name="smtp_domain" /></td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="3">');
  putHtml('SMTP Authentication:');
  putHtml('<select name="smtp_auth">');
  putHtml('<option value="plain">plain</option>');
  $sel = (getVARdef($db, 'SMTP_AUTH', $cur_db) === 'login') ? ' selected="selected"' : '';
  putHtml('<option value="login"'.$sel.'>login</option>');
  $sel = (getVARdef($db, 'SMTP_AUTH', $cur_db) === 'off') ? ' selected="selected"' : '';
  putHtml('<option value="off"'.$sel.'>none</option>');
  putHtml('</select>');
  putHtml('</td><td style="text-align: left;" colspan="3">');
  if (($value = getVARdef($db, 'SMTP_PORT', $cur_db)) === '') {
    $value = '465';
  }
  putHtml('SMTP Port:<input type="text" size="8" maxlength="6" value="'.$value.'" name="smtp_port" /></td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="3">');
  putHtml('SMTP Encryption:');
  putHtml('<select name="smtp_tls">');
  putHtml('<option value="tls_ssl">TLS/SSL</option>');
  $sel = (getVARdef($db, 'SMTP_TLS', $cur_db) === 'yes' && getVARdef($db, 'SMTP_STARTTLS', $cur_db) === 'on') ? ' selected="selected"' : '';
  putHtml('<option value="starttls"'.$sel.'>STARTTLS</option>');
  $sel = (getVARdef($db, 'SMTP_TLS', $cur_db) === 'no') ? ' selected="selected"' : '';
  putHtml('<option value="no"'.$sel.'>none</option>');
  putHtml('</select>');
  putHtml('&ndash;');
  putHtml('<select name="smtp_certcheck">');
  putHtml('<option value="on">Check Cert</option>');
  $sel = (getVARdef($db, 'SMTP_CERTCHECK', $cur_db) === 'off') ? ' selected="selected"' : '';
  putHtml('<option value="off"'.$sel.'>Ignore Cert</option>');
  putHtml('</select>');
  putHtml('</td><td style="text-align: left;" colspan="3">');
  $value = getVARdef($db, 'SMTP_CA', $cur_db);
  putHtml('SMTP Cert File:<input type="text" size="24" maxlength="64" value="'.$value.'" name="smtp_ca_cert" /></td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="3">');
  $value = getVARdef($db, 'SMTP_USER', $cur_db);
  putHtml('SMTP Username:<input type="text" size="32" maxlength="64" value="'.$value.'" name="smtp_user" /></td>');
  putHtml('<td style="text-align: left;" colspan="3">');
  $value = getVARdef($db, 'SMTP_PASS', $cur_db);
  $value = htmlspecialchars(RCconfig2string($value));
  putHtml('SMTP Password:<input type="password" size="24" maxlength="64" value="'.$value.'" name="smtp_pass" /></td></tr>');
  if (is_file('/mnt/kd/msmtp-aliases.conf')) {
    putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
    putHtml('SMTP Local Aliases:');
    putHtml('<input type="submit" value="Edit Local Aliases" name="submit_smtp_aliases" class="button" /></td></tr>');
  }

  putHtml('<tr class="dtrow0"><td colspan="6">&nbsp;</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>ACME (Let\'s Encrypt) Certificate:</strong>'.includeTOPICinfo('ACME-Certificate'));
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('ACME Deploy Service:');
  $sel = isVARtype('ACME_SERVICE', $db, $cur_db, 'lighttpd') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="acme_lighttpd" name="acme_lighttpd"'.$sel.' />&nbsp;HTTPS Server');
  $sel = isVARtype('ACME_SERVICE', $db, $cur_db, 'asterisk') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="acme_asterisk" name="acme_asterisk"'.$sel.' />&nbsp;Asterisk SIP-TLS');
  $sel = isVARtype('ACME_SERVICE', $db, $cur_db, 'prosody') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="acme_prosody" name="acme_prosody"'.$sel.' />&nbsp;XMPP Server');
  $sel = isVARtype('ACME_SERVICE', $db, $cur_db, 'slapd') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="acme_slapd" name="acme_slapd"'.$sel.' />&nbsp;LDAP Server');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  $value = getVARdef($db, 'ACME_ACCOUNT_EMAIL', $cur_db);
  putHtml('ACME Account Email Address:<input type="text" size="36" maxlength="128" value="'.$value.'" name="acme_account_email" /></td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('Non-ACME Self-Signed HTTPS Certificate:');
  putHtml('<input type="submit" value="Self-Signed HTTPS Cert" name="submit_self_signed_https" class="button" />');
  putHtml('&ndash;');
  putHtml('<input type="checkbox" value="self_signed_https" name="confirm_self_signed_https" />&nbsp;Confirm</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('Non-ACME Self-Signed SIP-TLS Certificate:');
  putHtml('<input type="submit" value="Self-Signed SIP-TLS Cert" name="submit_self_signed_sip_tls" class="button" /></td></tr>');

  putHtml('<tr class="dtrow0"><td colspan="6">&nbsp;</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Network Services:</strong>');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('DNS&nbsp;Forwarder &amp; DHCP Server:');
  putHtml('<input type="submit" value="Configure DNS Hosts" name="submit_dns_hosts" class="button" /></td></tr>');

  if (is_file('/etc/init.d/unbound')) {
    putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
    putHtml('DNS-TLS Proxy Server:');
    putHtml('<input type="submit" value="Configure DNS-TLS" name="submit_dns_tls" class="button" /></td></tr>');
  }
  if (is_file('/etc/init.d/dnscrypt')) {
    putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
    putHtml('DNSCrypt Proxy Server:');
    putHtml('<input type="submit" value="Configure DNSCrypt" name="submit_dnscrypt" class="button" /></td></tr>');
  }
  if (is_file('/etc/init.d/kamailio') &&
     (is_file('/mnt/kd/kamailio/kamailio.cfg') || is_file('/mnt/kd/kamailio/kamailio-local.cfg'))) {
    putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
    putHtml('Kamailio&nbsp;SIP&nbsp;Server:');
    putHtml('<input type="submit" value="Configure Kamailio" name="submit_kamailio" class="button" /></td></tr>');
  }

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('XMPP Server, Messaging and Presence:');
  putHtml('<input type="submit" value="Configure XMPP" name="submit_xmpp" class="button" /></td></tr>');

  if (is_file('/etc/init.d/slapd')) {
    putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
    putHtml('LDAP Server, Directory Information:');
    putHtml('<input type="submit" value="Configure LDAP Server" name="submit_slapd" class="button" /></td></tr>');
  }
  if (is_file('/etc/init.d/snmpd') && is_file('/mnt/kd/snmp/snmpd.conf')) {
    putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
    putHtml('SNMP&nbsp;Agent&nbsp;Server:');
    putHtml('<input type="submit" value="Configure SNMP Agent" name="submit_snmp_agent" class="button" /></td></tr>');
  }
  if (is_file('/etc/init.d/keepalived') && is_file('/mnt/kd/keepalived/keepalived.conf')) {
    putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
    putHtml('VRRP High Availability Daemon:');
    putHtml('<input type="submit" value="Configure Keepalived" name="submit_keepalived" class="button" /></td></tr>');
  }
  if (is_file('/etc/init.d/monit')) {
    putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
    putHtml('Monit&nbsp;Monitoring:');
    putHtml('<input type="submit" value="Configure Monit" name="submit_monit" class="button" /></td></tr>');
  }
  if (is_file('/etc/init.d/zabbix')) {
    putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
    putHtml('Zabbix&nbsp;Monitoring:');
    putHtml('<input type="submit" value="Configure Zabbix" name="submit_zabbix" class="button" /></td></tr>');
  }
  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('FTP&nbsp;&nbsp;Server:');
  putHtml('<select name="ftp">');
  putHtml('<option value="">disabled</option>');
  $value = getVARdef($db, 'FTPD', $cur_db);
  $sel = ($value === 'vsftpd' || $value === 'inetd') ? ' selected="selected"' : '';
  putHtml('<option value="vsftpd"'.$sel.'>enabled</option>');
  putHtml('</select>');
  putHtml('&ndash;');
  putHtml('<select name="ftpd_write">');
  putHtml('<option value="yes">read/write</option>');
  $value = getVARdef($db, 'FTPD_WRITE', $cur_db);
  $sel = ($value === 'no') ? ' selected="selected"' : '';
  putHtml('<option value="no"'.$sel.'>read-only</option>');
  putHtml('</select>');
  if (is_writable('/mnt/kd/vsftpd.conf')) {
    putHtml('&ndash;');
    putHtml('<input type="submit" value="FTP Server Configuration" name="submit_edit_vsftpd_conf" class="button" />');
  }
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('TFTP&nbsp;Server:');
  putHtml('<select name="tftp">');
  putHtml('<option value="">disabled</option>');
  $value = getVARdef($db, 'TFTPD', $cur_db);
  $sel = ($value === 'dnsmasq' || $value === 'tftpd' || $value === 'inetd') ? ' selected="selected"' : '';
  putHtml('<option value="dnsmasq"'.$sel.'>enabled</option>');
  putHtml('</select></td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('CLI&nbsp;&nbsp;Proxy&nbsp;Server:');
  putHtml('<select name="cli_proxy">');
  putHtml('<option value="">disabled</option>');
  $value = getVARdef($db, 'CLI_PROXY_SERVER', $cur_db);
  $sel = ($value === 'shellinaboxd') ? ' selected="selected"' : '';
  putHtml('<option value="shellinaboxd"'.$sel.'>enabled</option>');
  putHtml('</select>');
  putHtml('&nbsp;<i>(https://'.$_SERVER['HTTP_HOST'].'/admin/cli/ or CLI Tab)</i>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('NetStat&nbsp;Server:');
  putHtml('<select name="netstat_server">');
  putHtml('<option value="">disabled</option>');
  $value = getVARdef($db, 'NETSTAT_SERVER', $cur_db);
  $sel = ($value === 'darkstat') ? ' selected="selected"' : '';
  putHtml('<option value="darkstat"'.$sel.'>enabled</option>');
  putHtml('</select>');
  putHtml('&nbsp;<i>(https://'.$_SERVER['HTTP_HOST'].'/admin/netstat/ or NetStat Tab)</i>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('NetStat&nbsp;Interfaces:');
  if (($value = getVARdef($db, 'NETSTAT_EXTIF', $cur_db)) === '') {    // set in user.conf
    $value = 'External';
  }
  $sel = isVARtype('NETSTAT_CAPTURE', $db, $cur_db, 'EXTIF') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="netstat_EXTIF" name="netstat_EXTIF"'.$sel.' />&nbsp;'.$value);
  $sel = isVARtype('NETSTAT_CAPTURE', $db, $cur_db, 'INTIF') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="netstat_INTIF" name="netstat_INTIF"'.$sel.' />&nbsp;1st LAN');
  $sel = isVARtype('NETSTAT_CAPTURE', $db, $cur_db, 'INT2IF') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="netstat_INT2IF" name="netstat_INT2IF"'.$sel.' />&nbsp;2nd LAN');
  $sel = isVARtype('NETSTAT_CAPTURE', $db, $cur_db, 'INT3IF') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="netstat_INT3IF" name="netstat_INT3IF"'.$sel.' />&nbsp;3rd LAN');
  $sel = isVARtype('NETSTAT_CAPTURE', $db, $cur_db, 'INT4IF') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="netstat_INT4IF" name="netstat_INT4IF"'.$sel.' />&nbsp;4th LAN');
  $sel = isVARtype('NETSTAT_CAPTURE', $db, $cur_db, 'DMZIF') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="netstat_DMZIF" name="netstat_DMZIF"'.$sel.' />&nbsp;DMZ');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml("Universal Plug'n'Play Server:");
  $upnp_natpmp = getVARdef($db, 'UPNP_ENABLE_NATPMP', $cur_db) === 'yes' ? 'yes' : 'no';
  $upnp_upnp = getVARdef($db, 'UPNP_ENABLE_UPNP', $cur_db) === 'yes' ? 'yes' : 'no';
  putHtml('<select name="upnp" onchange="upnp_change()">');
  foreach ($select_upnp as $key => $value) {
    $sel = ("$upnp_natpmp:$upnp_upnp" === $value) ? ' selected="selected"' : '';
    putHtml('<option value="'.$value.'"'.$sel.'>'.$key.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml("Universal Plug'n'Play Interfaces:");
  $sel = isVARtype('UPNP_LISTEN', $db, $cur_db, 'INTIF') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="upnp_INTIF" name="upnp_INTIF"'.$sel.' />&nbsp;1st LAN');
  $sel = isVARtype('UPNP_LISTEN', $db, $cur_db, 'INT2IF') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="upnp_INT2IF" name="upnp_INT2IF"'.$sel.' />&nbsp;2nd LAN');
  $sel = isVARtype('UPNP_LISTEN', $db, $cur_db, 'INT3IF') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="upnp_INT3IF" name="upnp_INT3IF"'.$sel.' />&nbsp;3rd LAN');
  $sel = isVARtype('UPNP_LISTEN', $db, $cur_db, 'INT4IF') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="upnp_INT4IF" name="upnp_INT4IF"'.$sel.' />&nbsp;4th LAN');
  $sel = isVARtype('UPNP_LISTEN', $db, $cur_db, 'DMZIF') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="upnp_DMZIF" name="upnp_DMZIF"'.$sel.' />&nbsp;DMZ');
  putHtml('</td></tr>');

  if (is_file('/etc/init.d/avahi')) {
    putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
    putHtml('mDNS/DNS-SD&nbsp;Service&nbsp;Discovery:');
    putHtml('<select name="avahi">');
    putHtml('<option value="no">disabled</option>');
    $sel = (getVARdef($db, 'AVAHI_ENABLE', $cur_db) === 'yes') ? ' selected="selected"' : '';
    putHtml('<option value="yes"'.$sel.'>enabled</option>');
    putHtml('</select>');
    if (is_writable('/mnt/kd/avahi/avahi-daemon.conf')) {
      putHtml('&ndash;');
      putHtml('<input type="submit" value="Configure mDNS/DNS-SD" name="submit_avahi" class="button" />');
    }
    putHtml('</td></tr>');
  }

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  $value = getVARdef($db, 'HTTPDIR', $cur_db);
  putHtml('HTTP&nbsp;&nbsp;Server Directory:<input type="text" size="45" maxlength="64" value="'.$value.'" name="http_dir" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('HTTP&nbsp;&nbsp;Server Options:');
  $sel = (getVARdef($db, 'HTTPCGI', $cur_db) === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="http_cgi" name="http_cgi"'.$sel.' />&nbsp;HTTP&nbsp;&nbsp;CGI&nbsp;');
  $sel = (getVARdef($db, 'HTTP_LISTING', $cur_db) === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="http_listing" name="http_listing"'.$sel.' />&nbsp;Allow Listing');
  $sel = (getVARdef($db, 'HTTP_ACCESSLOG', $cur_db) === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="http_accesslog" name="http_accesslog"'.$sel.' />&nbsp;Access Logging');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  $value = getVARdef($db, 'HTTPSDIR', $cur_db);
  putHtml('HTTPS&nbsp;Server Directory:<input type="text" size="45" maxlength="64" value="'.$value.'" name="https_dir" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('HTTPS&nbsp;Server Options:');
  $sel = (getVARdef($db, 'HTTPSCGI', $cur_db) === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="https_cgi" name="https_cgi"'.$sel.' />&nbsp;HTTPS&nbsp;CGI&nbsp;');
  $sel = (getVARdef($db, 'HTTPS_LISTING', $cur_db) === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="https_listing" name="https_listing"'.$sel.' />&nbsp;Allow Listing');
  $sel = (getVARdef($db, 'HTTPS_ACCESSLOG', $cur_db) === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="https_accesslog" name="https_accesslog"'.$sel.' />&nbsp;Access Logging');
  putHtml('</td></tr>');

  $value = getVARdef($db, 'HTTPSCERT', $cur_db);
  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('HTTPS&nbsp;Certificate File:<input type="text" size="45" maxlength="64" value="'.$value.'" name="https_cert" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  $value = getVARdef($db, 'PHONEPROV_ALLOW', $cur_db);
  putHtml('HTTP &amp; HTTPS /phoneprov/ Allowed IP\'s:<input type="text" size="45" maxlength="200" value="'.$value.'" name="phoneprov_allow" />');
  putHtml('<i>(10.1.2.* 2001:db8:1:*)</i>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="color: orange; text-align: center;" colspan="6">');
  putHtml('Note: Changing HTTPS values effects this web interface.');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: right;">');
  putHtml('<strong>VPN Type:</strong>');
  putHtml('</td><td style="text-align: left;" colspan="5">&nbsp;</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = isVARtype('VPN', $db, $cur_db, 'openvpnclient') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="openvpnclient" name="openvpnclient"'.$sel.' />');
  putHtml('</td><td style="text-align: left;" colspan="5">');
  putHtml('OpenVPN Client');
  putHtml('&ndash;');
  putHtml('<input type="submit" value="OpenVPN Configuration" name="submit_edit_openvpnclient" class="button" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = isVARtype('VPN', $db, $cur_db, 'openvpn') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="openvpn" name="openvpn"'.$sel.' />');
  putHtml('</td><td style="text-align: left;" colspan="5">');
  putHtml('OpenVPN Server');
  putHtml('&ndash;');
  putHtml('<input type="submit" value="OpenVPN Configuration" name="submit_edit_openvpn" class="button" />');
  putHtml('</td></tr>');

if (is_file('/etc/init.d/ipsec')) {
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = isVARtype('VPN', $db, $cur_db, 'ipsec') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="ipsec" name="ipsec"'.$sel.' />');
  putHtml('</td><td style="text-align: left;" colspan="5">');
  putHtml('IPsec strongSwan');
  if (is_writable('/mnt/kd/ipsec/strongswan/ipsec.conf')) {
    putHtml('&ndash;');
    putHtml('<input type="submit" value="IPsec Configuration" name="submit_edit_ipsec" class="button" />');
  }
  putHtml('</td></tr>');
}

if (is_file('/etc/init.d/wireguard')) {
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = isVARtype('VPN', $db, $cur_db, 'wireguard') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="wireguard" name="wireguard"'.$sel.' />');
  putHtml('</td><td style="text-align: left;" colspan="5">');
  putHtml('WireGuard VPN');
  putHtml('&ndash;');
  putHtml('<input type="submit" value="WireGuard Configuration" name="submit_edit_wireguard" class="button" />');
  putHtml('</td></tr>');
}

  putHtml('<tr class="dtrow0"><td colspan="6">&nbsp;</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>IPv6 Tunnel (6in4, 6to4):</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('IPv6 Tunnel Type:');
  $ipv6_tunnel = explode('~', getVARdef($db, 'IPV6_TUNNEL', $cur_db));
  putHtml('<select name="ipv6_tunnel_type">');
  putHtml('<option value="">disabled</option>');
  $sel = ($ipv6_tunnel[0] === '6in4-static') ? ' selected="selected"' : '';
  putHtml('<option value="6in4-static"'.$sel.'>6in4-static</option>');
  $sel = ($ipv6_tunnel[0] === '6to4-relay') ? ' selected="selected"' : '';
  putHtml('<option value="6to4-relay"'.$sel.'>6to4-relay</option>');
  putHtml('</select></td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  $value = isset($ipv6_tunnel[1]) ? $ipv6_tunnel[1] : '';
  putHtml('Remote Server IPv4:<input type="text" size="16" maxlength="15" value="'.$value.'" name="ipv6_tunnel_server" />');
  $value = isset($ipv6_tunnel[2]) ? $ipv6_tunnel[2] : '';
  putHtml('&ndash;&nbsp;Endpoint IPv6/nn:<input type="text" size="45" maxlength="43" value="'.$value.'" name="ipv6_tunnel_endpoint" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow0"><td colspan="6">&nbsp;</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Dynamic DNS Update:</strong>');
  if (($dd_client = getVARdef($db, 'DDCLIENT', $cur_db)) === '') {
    if (getVARdef($db, 'DDUSER', $cur_db) !== '' && getVARdef($db, 'DDPASS', $cur_db) !== '') {
      $dd_client = 'ddclient';
    }
  }
  putHtml('<select name="dd_client">');
  putHtml('<option value="none">disabled</option>');
  $sel = ($dd_client === 'ddclient' || $dd_client === 'inadyn') ? ' selected="selected"' : '';
  putHtml('<option value="ddclient"'.$sel.'>enabled</option>');
  putHtml('</select>');
  if (is_writable('/mnt/kd/ddclient.conf')) {
    putHtml('&ndash;');
    putHtml('<input type="submit" value="Dynamic DNS Configuration" name="submit_edit_ddclient" class="button" />');
  }
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('DNS Service Type:');
  if (($t_value = getVARdef($db, 'DDSERVICE', $cur_db)) === '') {
    $t_value = 'dyndns@dyndns.org';
  }
  putHtml('<select name="dd_service">');
  foreach ($select_dyndns as $key => $value) {
    if (strcasecmp($t_value, $value) == 0) {
      $sel = ' selected="selected"';
      $t_value = '';
    } else {
      $sel = '';
    }
    putHtml('<option value="'.$value.'"'.$sel.'>'.$key.'</option>');
  }
  putHtml('</select>');
  putHtml('<input type="text" size="56" maxlength="200" value="'.$t_value.'" name="other_dd_service" /></td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('DNS Get IPv4 Address:');
  $t_value = getVARdef($db, 'DDGETIP', $cur_db);
  if ($t_value  === '' || $t_value  === 'getip.krisk.org') {
    $t_value = 'myip.dnsomatic.com';
  }
  putHtml('<select name="dd_getip">');
  foreach ($select_dyndns_getip as $key => $value) {
    if (strcasecmp($t_value, $value) == 0) {
      $sel = ' selected="selected"';
      $t_value = '';
    } else {
      $sel = '';
    }
    putHtml('<option value="'.$value.'"'.$sel.'>'.$key.'</option>');
  }
  putHtml('</select>');
  putHtml('<input type="text" size="36" maxlength="128" value="'.$t_value.'" name="other_dd_getip" /></td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('DNS Get IPv6 Address:');
  $t_value = getVARdef($db, 'DDGETIPV6', $cur_db);
  if ($t_value  === '') {
    $t_value = 'no';
  }
  putHtml('<select name="dd_getipv6">');
  foreach ($select_dyndns_getipv6 as $key => $value) {
    if (strcasecmp($t_value, $value) == 0) {
      $sel = ' selected="selected"';
      $t_value = '';
    } else {
      $sel = '';
    }
    putHtml('<option value="'.$value.'"'.$sel.'>'.$key.'</option>');
  }
  putHtml('</select>');
  putHtml('<input type="text" size="36" maxlength="128" value="'.$t_value.'" name="other_dd_getipv6" /></td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  $value = getVARdef($db, 'DDHOST', $cur_db);
  putHtml('DNS Hostname:<input type="text" size="36" maxlength="128" value="'.$value.'" name="dd_host" /></td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="3">');
  $value = getVARdef($db, 'DDUSER', $cur_db);
  putHtml('DNS Username:<input type="text" size="24" maxlength="64" value="'.$value.'" name="dd_user" /></td>');
  putHtml('<td style="text-align: left;" colspan="3">');
  $value = getVARdef($db, 'DDPASS', $cur_db);
  $value = htmlspecialchars(RCconfig2string($value));
  putHtml('DNS Password:<input type="password" size="24" maxlength="64" value="'.$value.'" name="dd_pass" /></td></tr>');

  putHtml('<tr class="dtrow0"><td colspan="6">&nbsp;</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Safe Asterisk &amp; SIP Monitoring:</strong>');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('Asterisk Automatic Restart on Crash:');
  putHtml('<select name="safe_asterisk">');
  putHtml('<option value="">disabled</option>');
  $sel = (getVARdef($db, 'SAFE_ASTERISK', $cur_db) === 'yes') ? ' selected="selected"' : '';
  putHtml('<option value="yes"'.$sel.'>enabled</option>');
  putHtml('</select></td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  $value = getVARdef($db, 'SAFE_ASTERISK_NOTIFY', $cur_db);
  putHtml('Notify Email Addresses To:<input type="text" size="72" maxlength="256" value="'.$value.'" name="safe_asterisk_notify" /></td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  $value = getVARdef($db, 'SAFE_ASTERISK_NOTIFY_FROM', $cur_db);
  putHtml('Notify Email Address From:<input type="text" size="36" maxlength="128" value="'.$value.'" name="safe_asterisk_notify_from" /></td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  $value = getVARdef($db, 'MONITOR_ASTERISK_SIP_TRUNKS', $cur_db);
  putHtml('Monitor SIP Trunks:<input type="text" size="82" maxlength="256" value="'.$value.'" name="monitor_sip_trunks" /></td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  $value = getVARdef($db, 'MONITOR_ASTERISK_SIP_PEERS', $cur_db);
  putHtml('Monitor SIP Peers:&nbsp;<input type="text" size="82" maxlength="256" value="'.$value.'" name="monitor_sip_peers" /></td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('Monitor SIP Status Emails following SIP Failure Email:');
  putHtml('<select name="monitor_status_updates">');
  putHtml('<option value="">disabled</option>');
  $sel = (getVARdef($db, 'MONITOR_ASTERISK_SIP_STATUS_UPDATES', $cur_db) === 'yes') ? ' selected="selected"' : '';
  putHtml('<option value="yes"'.$sel.'>enabled</option>');
  putHtml('</select></td></tr>');

  putHtml('<tr class="dtrow0"><td colspan="6">&nbsp;</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');

  putHtml('<strong>LDAP Client System Defaults:</strong>');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  if (! is_file('/mnt/kd/ldap.conf')) {
    $value = getVARdef($db, 'LDAP_URI', $cur_db);
    putHtml('LDAP Server URI(s):<input type="text" size="82" maxlength="256" value="'.$value.'" name="ldap_uri" /></td></tr>');
    putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
    $value = getVARdef($db, 'LDAP_BASE', $cur_db);
    putHtml('LDAP Base DN:<input type="text" size="82" maxlength="256" value="'.$value.'" name="ldap_base" /></td></tr>');

    putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
    putHtml('LDAP Dereferencing:');
    $ldap_deref = getVARdef($db, 'LDAP_DEREF', $cur_db);
    putHtml('<select name="ldap_deref">');
    foreach ($select_ldap_deref as $key => $value) {
      $sel = ($ldap_deref === $value) ? ' selected="selected"' : '';
      putHtml('<option value="'.$value.'"'.$sel.'>'.$key.'</option>');
    }
    putHtml('</select>');
    putHtml('</td></tr>');

    putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
    putHtml('LDAP TLS Cert Check:');
    $ldap_tls_reqcert = getVARdef($db, 'LDAP_TLS_REQCERT', $cur_db);
    putHtml('<select name="ldap_tls_reqcert">');
    foreach ($select_ldap_tls_reqcert as $key => $value) {
      $sel = ($ldap_tls_reqcert === $value) ? ' selected="selected"' : '';
      putHtml('<option value="'.$value.'"'.$sel.'>'.$key.'</option>');
    }
    putHtml('</select>');
    putHtml('&ndash;&nbsp;Server CA Cert File:');
    if (($value = getVARdef($db, 'LDAP_TLS_CACERT', $cur_db)) === '') {
      $value = '/mnt/kd/ssl/ca-ldap.pem';
    }
    putHtml('<input type="text" size="24" maxlength="64" value="'.$value.'" name="ldap_tls_cacert" /></td></tr>');
  } else {
    putHtml('LDAP Defaults:');
    putHtml('<input type="submit" value="LDAP Configuration" name="submit_edit_ldap" class="button" /></td></tr>');
  }

  putHtml('<tr class="dtrow0"><td colspan="6">&nbsp;</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');

  putHtml('<strong>UPS Monitoring &amp; Shutdown:</strong>');
  putHtml('</td></tr>');
  if (! is_file('/mnt/kd/ups/ups.conf')) {
    putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="3">');
    putHtml('UPS Driver:');
    $ups_driver = getVARdef($db, 'UPS_DRIVER', $cur_db);
    putHtml('<select name="ups_driver">');
    foreach ($select_ups_driver as $key => $value) {
      $sel = ($ups_driver === $value) ? ' selected="selected"' : '';
      putHtml('<option value="'.$value.'"'.$sel.'>'.$key.'</option>');
    }
    putHtml('</select>');
    putHtml('</td><td style="text-align: left;" colspan="3">');
    $value = getVARdef($db, 'UPS_DRIVER_PORT', $cur_db);
    putHtml('Driver Data:<input type="text" size="36" maxlength="200" value="'.$value.'" name="ups_driver_port" />');
    putHtml('</td></tr>');
  } else {
    putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
    putHtml('UPS Driver Configuration File:');
    putHtml('<input type="submit" value="UPS Configuration" name="submit_edit_ups" class="button" /></td></tr>');
  }
  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="3">');
  putHtml('Network UPS Server:');
  putHtml('<select name="ups_listen_all">');
  putHtml('<option value="no">disabled</option>');
  $sel = (getVARdef($db, 'UPS_LISTEN_ALL', $cur_db) === 'yes') ? ' selected="selected"' : '';
  putHtml('<option value="yes"'.$sel.'>enabled</option>');
  putHtml('</select>');
  putHtml('</td><td style="text-align: left;" colspan="3">');
  $value = getVARdef($db, 'UPS_MONITOR_HOST', $cur_db);
  putHtml('Network NUT ups@host:<input type="text" size="24" maxlength="256" value="'.$value.'" name="ups_monitor_host" /></td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="3">');
  if (($value = getVARdef($db, 'UPS_MONITOR_USER', $cur_db)) === '') {
    $value = 'monuser';
  }
  putHtml('UPS Username:<input type="text" size="24" maxlength="64" value="'.$value.'" name="ups_monitor_user" /></td>');
  putHtml('<td style="text-align: left;" colspan="3">');
  if (($value = getVARdef($db, 'UPS_MONITOR_PASS', $cur_db)) === '') {
    $value = 'astlinux';
  }
  $value = htmlspecialchars(RCconfig2string($value));
  putHtml('UPS Password:<input type="password" size="24" maxlength="64" value="'.$value.'" name="ups_monitor_pass" /></td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  $value = getVARdef($db, 'UPS_NOTIFY', $cur_db);
  putHtml('Notify Email Addresses To:<input type="text" size="72" maxlength="256" value="'.$value.'" name="ups_notify" /></td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  $value = getVARdef($db, 'UPS_NOTIFY_FROM', $cur_db);
  putHtml('Notify Email Address From:<input type="text" size="36" maxlength="128" value="'.$value.'" name="ups_notify_from" /></td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('Kill Power on the UPS after a Powerfail Shutdown:');
  putHtml('<select name="ups_kill_power">');
  putHtml('<option value="no">disabled</option>');
  $sel = (getVARdef($db, 'UPS_KILL_POWER', $cur_db) === 'yes') ? ' selected="selected"' : '';
  putHtml('<option value="yes"'.$sel.'>enabled</option>');
  putHtml('</select></td></tr>');

  putHtml('<tr class="dtrow0"><td colspan="6">&nbsp;</td></tr>');

if (is_file('/etc/init.d/fossil')) {
  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');

  putHtml('<strong>Fossil &ndash; Software Configuration Management:</strong>');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('Fossil Server:');
  putHtml('<select name="fossil_server">');
  putHtml('<option value="no">disabled</option>');
  $sel = (getVARdef($db, 'FOSSIL_SERVER', $cur_db) === 'yes') ? ' selected="selected"' : '';
  putHtml('<option value="yes"'.$sel.'>enabled</option>');
  putHtml('</select></td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  $value = getVARdef($db, 'FOSSIL_INCLUDE_DIRS', $cur_db);
  putHtml('Fossil Include Dirs:&nbsp;<input type="text" size="82" maxlength="256" value="'.$value.'" name="fossil_include_dirs" /></td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  $value = getVARdef($db, 'FOSSIL_INCLUDE_FILES', $cur_db);
  putHtml('Fossil Include Files:<input type="text" size="82" maxlength="256" value="'.$value.'" name="fossil_include_files" /></td></tr>');

  putHtml('<tr class="dtrow0"><td colspan="6">&nbsp;</td></tr>');
}

if (is_file('/usr/bin/tarsnap-backup')) {
  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');

  putHtml('<strong>Data Backup:</strong>');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('Tarsnap Backup:');
  putHtml('<input type="submit" value="Tarsnap Backup Options" name="submit_tarsnap_backup" class="button" /></td></tr>');

  putHtml('<tr class="dtrow0"><td colspan="6">&nbsp;</td></tr>');
}

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');

  putHtml('<strong>Advanced Configuration:</strong>');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
  putHtml('User System Variables:');
  putHtml('<input type="submit" value="Edit User Variables" name="submit_edit_user_conf" class="button" /></td></tr>');
  if (is_writable('/mnt/kd/dnsmasq.conf')) {
    putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
    putHtml('Full DNS &amp; DHCP Configuration:');
    putHtml('<input type="submit" value="Edit DNSMasq Conf" name="submit_edit_dnsmasq_conf" class="button" /></td></tr>');
  } elseif (is_writable('/mnt/kd/dnsmasq.static')) {
    putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
    putHtml('Additional DNS &amp; DHCP Configuration:');
    putHtml('<input type="submit" value="Edit DNSMasq Static" name="submit_edit_dnsmasq_static" class="button" /></td></tr>');
  }
  putHtml('</table>');
  putHtml('</form>');
  putHtml('</center></td></tr></table>');
  putHtml('</center>');
} // End of HTTP GET
require_once '../common/footer.php';

?>
