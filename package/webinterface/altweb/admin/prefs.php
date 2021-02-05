<?php

// Copyright (C) 2008-2021 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// prefs.php for AstLinux
// 04-06-2008
// 04-20-2008, Never-ending Additions
// 08-19-2008, Added CDR Log Format Menu
// 08-24-2008, Added /mnt/kd/ prefs file support
// 01-02-2012, Added Show Jabber Status/Command
// 09-28-2012, Added Show Adaptive Ban Plugin Status
// 09-28-2012, Added Show Latest System Logs/Hide Log Words
// 09-28-2012, Added Show Custom Asterisk Command
// 01-20-2013, Added Show XMPP Server Status
// 09-06-2013, Added Edit Tab Shortcut support
// 09-04-2014, Added Show Kamailio SIP Server Status
// 12-16-2014, Added Show Monit Tab
// 08-12-2015, Added Show Fossil Tab
// 02-16-2017, Added Disable CLI Tab for "staff" user
// 07-16-2017, Added Show ACME Certificates
// 11-06-2017, Added Show WireGuard VPN Status
// 07-11-2019, Added Backup Exclude Suffixes
// 07-31-2019, Added Disable CodeMirror Editor
// 10-29-2019, Added Wiki tab and link
// 02-21-2020, Remove PPTP VPN support
// 05-10-2020, Added Linux Containers (LXC)
// 02-05-2021, Added Show vnStat Tab
//

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! $global_admin) {
    $result = 999;
  } elseif (isset($_POST['submit_save'])) {
    $result = 6;
    if (($prefs_loc = getPREFSlocation()) === '') {
      header('Location: '.$myself.'?result='.$result);
      exit;
    }
    $result = 3;
    if (($fp = @fopen($prefs_loc, "wb")) === FALSE) {
      header('Location: '.$myself.'?result='.$result);
      exit;
    }
    $result = 11;
    fwrite($fp, "### Preferences -- ".$prefs_loc." -- ###\n");

    if (! isset($_POST['pppoe_connection'])) {
      $value = 'status_pppoe_connection = no';
      fwrite($fp, $value."\n");
    }
    if (! isset($_POST['linux_containers'])) {
      $value = 'status_linux_containers = no';
      fwrite($fp, $value."\n");
    }
    if (! isset($_POST['disk_usage'])) {
      $value = 'status_disk_usage = no';
      fwrite($fp, $value."\n");
    }
    if (! isset($_POST['wan_failover'])) {
      $value = 'status_show_wan_failover = no';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['acme_certificates'])) {
      $value = 'status_show_acme_certificates = yes';
      fwrite($fp, $value."\n");
    }
    if (! isset($_POST['ntp_sessions'])) {
      $value = 'status_ntp_sessions = no';
      fwrite($fp, $value."\n");
    }
    if (! isset($_POST['dhcp_leases'])) {
      $value = 'status_show_dhcp_leases = no';
      fwrite($fp, $value."\n");
    }
    if (! isset($_POST['openvpn_client_server'])) {
      $value = 'status_openvpn_client_server = no';
      fwrite($fp, $value."\n");
    }
    if (! isset($_POST['ipsec_associations'])) {
      $value = 'status_ipsec_associations = no';
      fwrite($fp, $value."\n");
    }
    if (! isset($_POST['wireguard_vpn'])) {
      $value = 'status_wireguard_vpn = no';
      fwrite($fp, $value."\n");
    }
    if (! isset($_POST['active_chan'])) {
      $value = 'status_show_active_chan = no';
      fwrite($fp, $value."\n");
    }
    if (! isset($_POST['sip_reg'])) {
      $value = 'status_sip_show_registry = no';
      fwrite($fp, $value."\n");
    }
    if (! isset($_POST['sip_peers'])) {
      $value = 'status_sip_show_peers = no';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['iax2_reg'])) {
      $value = 'status_iax2_show_registry = yes';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['iax2_peers'])) {
      $value = 'status_iax2_show_peers = yes';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['voicemail_users'])) {
      $value = 'status_voicemail_show_users = yes';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['dahdi_status'])) {
      $value = 'status_dahdi_show_status = yes';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['jabber_status'])) {
      $value = 'status_jabber_show_status = yes';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['custom_asterisk'])) {
      $value = 'status_custom_asterisk_status = yes';
      fwrite($fp, $value."\n");
    }
    if (($value = tuqp($_POST['asterisk_name'])) !== '') {
      $value = 'status_custom_asterisk_name_cmdstr = "'.$value.'"';
      fwrite($fp, $value."\n");
    }
    if (($value = tuqp($_POST['asterisk_cmd'])) !== '') {
      $value = 'status_custom_asterisk_cmdstr = "'.$value.'"';
      fwrite($fp, $value."\n");
    }
    if (! isset($_POST['kamailio_server'])) {
      $value = 'status_show_kamailio_server = no';
      fwrite($fp, $value."\n");
    }
    if (! isset($_POST['xmpp_server'])) {
      $value = 'status_show_xmpp_server = no';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['adaptive_ban'])) {
      $value = 'status_show_adaptive_ban = yes';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['firewall_states'])) {
      $value = 'status_show_firewall_states = yes';
      fwrite($fp, $value."\n");
    }
    if (($value = tuqp($_POST['firewall_sports'])) !== '') {
      $value = 'status_firewall_sports_cmdstr = "'.$value.'"';
      fwrite($fp, $value."\n");
    }
    if (($value = tuqp($_POST['firewall_dports'])) !== '') {
      $value = 'status_firewall_dports_cmdstr = "'.$value.'"';
      fwrite($fp, $value."\n");
    }
    if (! isset($_POST['ups_status'])) {
      $value = 'status_ups_show_status = no';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['hardware_monitoring'])) {
      $value = 'status_show_hardware_monitoring = yes';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['smart_monitoring'])) {
      $value = 'status_show_smart_monitoring = yes';
      fwrite($fp, $value."\n");
    }
    if (! isset($_POST['system_logs'])) {
      $value = 'status_show_system_logs = no';
      fwrite($fp, $value."\n");
    }
    if (($value = tuqp($_POST['exclude_logs'])) !== '') {
      $value = 'status_exclude_logs_cmdstr = "'.$value.'"';
      fwrite($fp, $value."\n");
    }

    if (isset($_POST['exclude_extensions'])) {
      $value = 'status_exclude_extensions = yes';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['status_auth'])) {
      $value = 'status_require_auth = yes';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['directory_auth'])) {
      $value = 'directory_require_auth = yes';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['disable_ami'])) {
      $value = 'status_asterisk_manager = no';
      fwrite($fp, $value."\n");
    }
    $value = 'status_active_chan_cmdstr = "'.tuqp($_POST['active_cmd']).'"';
    fwrite($fp, $value."\n");
    $value = 'status_voicemail_users_cmdstr = "'.tuqp($_POST['voicemail_cmd']).'"';
    fwrite($fp, $value."\n");
    $value = 'status_dahdi_status_cmdstr = "'.tuqp($_POST['dahdi_cmd']).'"';
    fwrite($fp, $value."\n");
    $value = 'status_jabber_status_cmdstr = "'.tuqp($_POST['jabber_cmd']).'"';
    fwrite($fp, $value."\n");

    $value = 'sysdial_ext_prefix_cmdstr = "'.tuqp($_POST['ext_prefix']).'"';
    fwrite($fp, $value."\n");
    $value = 'sysdial_ext_digits_cmdstr = "'.$_POST['ext_digits'].'"';
    fwrite($fp, $value."\n");

    $value = 'number_format_cmdstr = "'.tuqp($_POST['num_format']).'"';
    fwrite($fp, $value."\n");
    $value = 'number_error_cmdstr = "'.tuqp($_POST['num_error']).'"';
    fwrite($fp, $value."\n");
    $value = 'blacklist_action_menu_cmdstr = "'.tuqp($_POST['blacklist_menu']).'"';
    fwrite($fp, $value."\n");
    $value = 'whitelist_action_menu_cmdstr = "'.tuqp($_POST['whitelist_menu']).'"';
    fwrite($fp, $value."\n");

    $value = 'actionlist_format_cmdstr = "'.tuqp($_POST['actionlist_key_format']).'"';
    fwrite($fp, $value."\n");
    $value = 'actionlist_error_cmdstr = "'.tuqp($_POST['actionlist_key_error']).'"';
    fwrite($fp, $value."\n");
    $value = 'actionlist_action_menu_cmdstr = "'.tuqp($_POST['actionlist_menu']).'"';
    fwrite($fp, $value."\n");

    $value = tuqp($_POST['cidname_maxlen']);
    if ($value > 7 && $value != 15) {
      $value = 'cidname_maxlen_cmdstr = "'.$value.'"';
      fwrite($fp, $value."\n");
    }

    $value = 'followme_numbers_displayed = "'.$_POST['followme_maxnum'].'"';
    fwrite($fp, $value."\n");
    if (($value = tuqp($_POST['followme_menu'])) !== '') {
      $value = 'followme_schedule_menu_cmdstr = "'.$value.'"';
      fwrite($fp, $value."\n");
    }
    if (($value = tuqp($_POST['followme_number_context'])) !== '') {
      $value = 'followme_number_context_cmdstr = "'.$value.'"';
      fwrite($fp, $value."\n");
    }
    if (($value = tuqp($_POST['followme_music_class'])) !== '') {
      $value = 'followme_music_class_cmdstr = "'.$value.'"';
      fwrite($fp, $value."\n");
    }
    if (! isset($_POST['followme_format'])) {
      $value = 'followme_use_number_format = no';
      fwrite($fp, $value."\n");
    }

    if (($value = str_replace(' ', '', tuqp($_POST['meetme_redirect']))) !== '') {
      $value = 'meetme_redirect_path_cmdstr = "'.$value.'"';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['meetme_channel'])) {
      $value = 'meetme_channel_show = yes';
      fwrite($fp, $value."\n");
    }

    $value = 'cdrlog_default_format = "'.$_POST['cdr_default'].'"';
    fwrite($fp, $value."\n");
    $value = 'cdrlog_log_file_cmdstr = "'.tuqp($_POST['cdr_logfile']).'"';
    fwrite($fp, $value."\n");
    if (isset($_POST['cdr_databases'])) {
      $value = 'cdrlog_databases_show = yes';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['cdr_extra'])) {
      $value = 'cdrlog_extra_show = yes';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['cdr_last'])) {
      $value = 'cdrlog_last_show = yes';
      fwrite($fp, $value."\n");
    }
    $value = 'cdrlog_last_cmd = "'.$_POST['cdr_last_cmd'].'"';
    fwrite($fp, $value."\n");

    if (isset($_POST['extern_notify'])) {
      $value = 'voicemail_extern_notify = yes';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['show_context'])) {
      $value = 'voicemail_show_context = yes';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['hour_format'])) {
      $value = 'voicemail_24_hour_format = yes';
      fwrite($fp, $value."\n");
    }
    if (($value = $_POST['play_inline']) !== '') {
      $value = 'monitor_play_inline = "'.$value.'"';
      fwrite($fp, $value."\n");
    }

    if (! isset($_POST['sqldata_create_schema'])) {
      $value = 'sqldata_create_schema = no';
      fwrite($fp, $value."\n");
    }

    $value = 'phoneprov_extensions_displayed = "'.$_POST['phoneprov_maxnum'].'"';
    fwrite($fp, $value."\n");

    if (isset($_POST['users_hide_pass'])) {
      $value = 'users_voicemail_hide_pass = yes';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['users_delete_vmdata'])) {
      $value = 'users_voicemail_delete_vmdata = yes';
      fwrite($fp, $value."\n");
    }
    $value = 'users_voicemail_context_cmdstr = "'.tuqp($_POST['voicemail_context']).'"';
    fwrite($fp, $value."\n");
    $value = 'users_voicemail_reload_cmdstr = "'.tuqp($_POST['voicemail_reload']).'"';
    fwrite($fp, $value."\n");

    if (! isset($_POST['codemirror_editor'])) {
      $value = 'disable_codemirror_editor = yes';
      fwrite($fp, $value."\n");
    }
    if (($value = $_POST['codemirror_theme']) !== '') {
      $value = 'edit_text_codemirror_theme = "'.$value.'"';
      fwrite($fp, $value."\n");
    }

    if (isset($_POST['bak_files'])) {
      $value = 'edit_keep_bak_files = yes';
      fwrite($fp, $value."\n");
    }
    $value = tuqp($_POST['text_cols']);
    if ($value > 79 && $value != 95 && $value < 161) {
      $value = 'edit_text_cols_cmdstr = "'.$value.'"';
      fwrite($fp, $value."\n");
    }
    $value = tuqp($_POST['text_rows']);
    if ($value > 19 && $value != 30 && $value < 61) {
      $value = 'edit_text_rows_cmdstr = "'.$value.'"';
      fwrite($fp, $value."\n");
    }
    $value = tuqp(str_replace(chr(13), ' ', $_POST['edittext_shortcut']));
    $value = str_replace(chr(10), '', $value);
    if (strlen($value) > 900) {  // 1024 total line limit for prefs
      $value = substr($value, 0, 900);
    }
    $value = 'edit_text_shortcut_cmdstr = "'.$value.'"';
    fwrite($fp, $value."\n");

    if (isset($_POST['backup_hostname_domain'])) {
      $value = 'system_backup_hostname_domain = yes';
      fwrite($fp, $value."\n");
    }
    if (! isset($_POST['backup_gzip'])) {
      $value = 'system_backup_compress_gzip = no';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['backup_asturw'])) {
      $value = 'system_backup_asturw = yes';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['backup_temp'])) {
      $value = 'system_backup_temp_disk = yes';
      fwrite($fp, $value."\n");
    }
    if (($value = tuqp($_POST['backup_exclude_suffix'])) !== '') {
      $value = 'system_backup_exclude_suffix_cmdstr = "'.$value.'"';
      fwrite($fp, $value."\n");
    }
    if (system_image_arch() === 'genx86_64-vm') {
      if (! isset($_POST['reboot_vm_classic_full'])) {
        $value = 'system_reboot_vm_classic_full = no';
        fwrite($fp, $value."\n");
      }
    } else {
      if (isset($_POST['reboot_classic_full'])) {
        $value = 'system_reboot_classic_full = yes';
        fwrite($fp, $value."\n");
      }
    }
    $value = 'system_reboot_timer_adjust = "'.$_POST['reboot_timer'].'"';
    fwrite($fp, $value."\n");
    $value = 'system_asterisk_reload_cmdstr = "'.tuqp($_POST['asterisk_reload']).'"';
    fwrite($fp, $value."\n");
    $value = 'system_firmware_repository_url = "'.tuqp($_POST['repository_url']).'"';
    fwrite($fp, $value."\n");
    $value = 'system_asterisk_sounds_url = "'.tuqp($_POST['sounds_url']).'"';
    fwrite($fp, $value."\n");

    if (($value = trim(preg_replace('/[^a-zA-Z]+/', '', $_POST['dn_country_name']))) !== '') {
      if (strlen($value) == 2) {
        $value = strtoupper($value);
        $value = 'dn_country_name_cmdstr = "'.$value.'"';
        fwrite($fp, $value."\n");
      }
    }
    if (($value = trim(preg_replace('/[^a-zA-Z0-9._ -]+/', '', $_POST['dn_state_name']))) !== '') {
      $value = 'dn_state_name_cmdstr = "'.$value.'"';
      fwrite($fp, $value."\n");
    }
    if (($value = trim(preg_replace('/[^a-zA-Z0-9._ -]+/', '', $_POST['dn_locality_name']))) !== '') {
      $value = 'dn_locality_name_cmdstr = "'.$value.'"';
      fwrite($fp, $value."\n");
    }
    if (($value = trim(preg_replace('/[^a-zA-Z0-9._ -]+/', '', $_POST['dn_org_name']))) !== '') {
      $value = 'dn_org_name_cmdstr = "'.$value.'"';
      fwrite($fp, $value."\n");
    }
    if (($value = trim(preg_replace('/[^a-zA-Z0-9._ -]+/', '', $_POST['dn_org_unit']))) !== '') {
      $value = 'dn_org_unit_cmdstr = "'.$value.'"';
      fwrite($fp, $value."\n");
    }
    if (($value = preg_replace('/[^a-zA-Z0-9._-]+/', '', $_POST['dn_common_name'])) !== '') {
      $value = 'dn_common_name_cmdstr = "'.$value.'"';
      fwrite($fp, $value."\n");
    }
    if (($value = preg_replace('/[^a-zA-Z0-9._@-]+/', '', $_POST['dn_email_address'])) !== '') {
      $value = 'dn_email_address_cmdstr = "'.$value.'"';
      fwrite($fp, $value."\n");
    }

    $value = 'title_name_cmdstr = "'.tuqp($_POST['title_name']).'"';
    fwrite($fp, $value."\n");
    if (($value = tuqp($_POST['external_url_link'])) !== '') {
      $value = 'external_url_link_cmdstr = "'.$value.'"';
      fwrite($fp, $value."\n");
    }
    if (($value = tuqp($_POST['external_url_name'])) !== '') {
      $value = 'external_url_name_cmdstr = "'.$value.'"';
      fwrite($fp, $value."\n");
    }
    if (($value = tuqp($_POST['external_wiki_link'])) !== '') {
      $value = 'external_wiki_link_cmdstr = "'.$value.'"';
      fwrite($fp, $value."\n");
    }
    if (($value = tuqp($_POST['external_cli_link'])) !== '') {
      $value = 'external_cli_link_cmdstr = "'.$value.'"';
      fwrite($fp, $value."\n");
    }

    if (isset($_POST['external_fop2_https'])) {
      $value = 'external_fop2_https = yes';
      fwrite($fp, $value."\n");
    }

    if (isset($_POST['tab_directory'])) {
      $value = 'tab_directory_show = yes';
      fwrite($fp, $value."\n");
    }
    if (! isset($_POST['tab_sysdial'])) {
      $value = 'tab_sysdial_show = no';
      fwrite($fp, $value."\n");
    }
    if (! isset($_POST['tab_cidname'])) {
      $value = 'tab_cidname_show = no';
      fwrite($fp, $value."\n");
    }
    if (! isset($_POST['tab_blacklist'])) {
      $value = 'tab_blacklist_show = no';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['tab_whitelist'])) {
      $value = 'tab_whitelist_show = yes';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['tab_actionlist'])) {
      $value = 'tab_actionlist_show = yes';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['tab_sqldata'])) {
      $value = 'tab_sqldata_show = yes';
      fwrite($fp, $value."\n");
    }
    if (! isset($_POST['sqldata_disable_staff'])) {
      $value = 'tab_sqldata_disable_staff = no';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['tab_ldapab'])) {
      $value = 'tab_ldapab_show = yes';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['tab_phoneprov'])) {
      $value = 'tab_phoneprov_show = yes';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['tab_users'])) {
      $value = 'tab_users_show = yes';
      fwrite($fp, $value."\n");
    }
    if (! isset($_POST['tab_cdrlog'])) {
      $value = 'tab_cdrlog_show = no';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['tab_voicemail'])) {
      $value = 'tab_voicemail_show = yes';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['voicemail_disable_staff'])) {
      $value = 'tab_voicemail_disable_staff = yes';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['tab_monitor'])) {
      $value = 'tab_monitor_show = yes';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['monitor_disable_staff'])) {
      $value = 'tab_monitor_disable_staff = yes';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['tab_followme'])) {
      $value = 'tab_followme_show = yes';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['followme_disable_staff'])) {
      $value = 'tab_followme_disable_staff = yes';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['tab_meetme'])) {
      $value = 'tab_meetme_show = yes';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['tab_confbridge'])) {
      $value = 'tab_confbridge_show = yes';
      fwrite($fp, $value."\n");
    }
    if (! isset($_POST['tab_network'])) {
      $value = 'tab_network_show = no';
      fwrite($fp, $value."\n");
    }
    if (! isset($_POST['dnshosts_disable_staff'])) {
      $value = 'tab_dnshosts_disable_staff = no';
      fwrite($fp, $value."\n");
    }
    if (! isset($_POST['xmpp_disable_staff'])) {
      $value = 'tab_xmpp_disable_staff = no';
      fwrite($fp, $value."\n");
    }
    if (! isset($_POST['tab_edit'])) {
      $value = 'tab_edit_show = no';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['tab_cli'])) {
      $value = 'tab_cli_show = yes';
      fwrite($fp, $value."\n");
    }
    if (! isset($_POST['cli_disable_staff'])) {
      $value = 'tab_cli_disable_staff = no';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['tab_vnstat'])) {
      $value = 'tab_vnstat_show = yes';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['tab_netstat'])) {
      $value = 'tab_netstat_show = yes';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['tab_monit'])) {
      $value = 'tab_monit_show = yes';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['tab_fossil'])) {
      $value = 'tab_fossil_show = yes';
      fwrite($fp, $value."\n");
    }
    if (! isset($_POST['tab_staff'])) {
      $value = 'tab_staff_disable_staff = yes';
      fwrite($fp, $value."\n");
    }
    if (isset($_POST['tab_wiki'])) {
      $value = 'tab_wiki_show = yes';
      fwrite($fp, $value."\n");
    }
    // Special non-editable options, retain from save to save
    if (getPREFdef($global_prefs, 'tab_prefs_show') === 'no') {
      $value = 'tab_prefs_show = no';
      fwrite($fp, $value."\n");
    }
    if (($value = getPREFdef($global_prefs, 'custom_tab_list_cmdstr')) !== '') {
      $value = 'custom_tab_list_cmdstr = "'.$value.'"';
      fwrite($fp, $value."\n");
    }
    fclose($fp);
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} else { // Start of HTTP GET
$ACCESS_RIGHTS = 'admin';
require_once '../common/header.php';

  putHtml("<center>");
  if (isset($_GET['result'])) {
    $result = $_GET['result'];
    if ($result == 3) {
      putHtml('<p style="color: red;">Error creating file.</p>');
    } elseif ($result == 6) {
      putHtml('<p style="color: red;">Unable to calculate web root directory.</p>');
    } elseif ($result == 11) {
      putHtml('<p style="color: green;">Preferences saved.</p>');
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
  <tr><td style="text-align: center;">
  <h2>Management Preferences:</h2>
  </td></tr><tr><td style="text-align: center;">
  <input type="submit" value="Save/Apply Preferences" name="submit_save" />
  </td></tr></table>
  <table class="stdtable">
  <tr class="dtrow0"><td width="50">&nbsp;</td><td width="90">&nbsp;</td><td>&nbsp;</td><td width="90">&nbsp;</td><td width="90">&nbsp;</td><td width="90">&nbsp;</td></tr>
  <tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">
  <strong>Status Tab Options:</strong>
<?php
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'status_require_auth') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="status_auth" name="status_auth"'.$sel.' /></td><td colspan="5">Require Authentication for Status Tab</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'status_pppoe_connection') !== 'no') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="pppoe_connection" name="pppoe_connection"'.$sel.' /></td><td colspan="5">Show PPPoE Connection Status when Active</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'status_linux_containers') !== 'no') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="linux_containers" name="linux_containers"'.$sel.' /></td><td colspan="5">Show Linux Containers Status</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'status_disk_usage') !== 'no') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="disk_usage" name="disk_usage"'.$sel.' /></td><td colspan="5">Show Disk Usage</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'status_show_wan_failover') !== 'no') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="wan_failover" name="wan_failover"'.$sel.' /></td><td colspan="5">Show WAN Failover Status</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'status_show_acme_certificates') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="acme_certificates" name="acme_certificates"'.$sel.' /></td><td colspan="5">Show ACME Certificates</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'status_ntp_sessions') !== 'no') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="ntp_sessions" name="ntp_sessions"'.$sel.' /></td><td colspan="5">Show NTP Time Sources</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'status_show_dhcp_leases') !== 'no') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="dhcp_leases" name="dhcp_leases"'.$sel.' /></td><td colspan="5">Show DHCP Leases</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'status_openvpn_client_server') !== 'no') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="openvpn_client_server" name="openvpn_client_server"'.$sel.' /></td><td colspan="5">Show OpenVPN Client/Server Status</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'status_ipsec_associations') !== 'no') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="ipsec_associations" name="ipsec_associations"'.$sel.' /></td><td colspan="5">Show IPsec Associations</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'status_wireguard_vpn') !== 'no') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="wireguard_vpn" name="wireguard_vpn"'.$sel.' /></td><td colspan="5">Show WireGuard VPN Status</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'status_show_active_chan') !== 'no') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="active_chan" name="active_chan"'.$sel.' /></td><td colspan="5">Show Active Channels</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="3">Active Channels Command:</td><td colspan="3">');
  if (($value = getPREFdef($global_prefs, 'status_active_chan_cmdstr')) === '') {
    $value = 'core show channels';
  }
  putHtml('<input type="text" size="28" maxlength="64" value="'.$value.'" name="active_cmd" /></td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'status_sip_show_registry') !== 'no') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="sip_reg" name="sip_reg"'.$sel.' /></td><td colspan="5">Show SIP Trunk Registrations</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'status_sip_show_peers') !== 'no') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="sip_peers" name="sip_peers"'.$sel.' /></td><td colspan="5">Show SIP Peer Status</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'status_iax2_show_registry') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="iax2_reg" name="iax2_reg"'.$sel.' /></td><td colspan="5">Show IAX2 Trunk Registrations</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'status_iax2_show_peers') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="iax2_peers" name="iax2_peers"'.$sel.' /></td><td colspan="5">Show IAX2 Peer Status</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'status_voicemail_show_users') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="voicemail_users" name="voicemail_users"'.$sel.' /></td><td colspan="5">Show Voicemail User Status</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="3">Voicemail Users Command:</td><td colspan="3">');
  if (($value = getPREFdef($global_prefs, 'status_voicemail_users_cmdstr')) === '') {
    $value = 'voicemail show users';
  }
  putHtml('<input type="text" size="28" maxlength="64" value="'.$value.'" name="voicemail_cmd" /></td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'status_dahdi_show_status') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="dahdi_status" name="dahdi_status"'.$sel.' /></td><td colspan="5">Show DAHDI Status</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="3">DAHDI Status Command:</td><td colspan="3">');
  if (($value = getPREFdef($global_prefs, 'status_dahdi_status_cmdstr')) === '') {
    $value = 'dahdi show status';
  }
  putHtml('<input type="text" size="28" maxlength="64" value="'.$value.'" name="dahdi_cmd" /></td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'status_jabber_show_status') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="jabber_status" name="jabber_status"'.$sel.' /></td><td colspan="5">Show Jabber Status</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="3">Jabber Status Command:</td><td colspan="3">');
  if (($value = getPREFdef($global_prefs, 'status_jabber_status_cmdstr')) === '') {
    $value = 'jabber show connections';
  }
  putHtml('<input type="text" size="28" maxlength="64" value="'.$value.'" name="jabber_cmd" /></td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'status_custom_asterisk_status') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="custom_asterisk" name="custom_asterisk"'.$sel.' /></td><td colspan="5">Custom Asterisk Name:');
  if (($value = getPREFdef($global_prefs, 'status_custom_asterisk_name_cmdstr')) === '') {
    $value = 'Asterisk Command Status';
  }
  putHtml('<input type="text" size="28" maxlength="64" value="'.$value.'" name="asterisk_name" /></td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="3">Custom Asterisk Command:</td><td colspan="3">');
  $value = getPREFdef($global_prefs, 'status_custom_asterisk_cmdstr');
  putHtml('<input type="text" size="28" maxlength="64" value="'.$value.'" name="asterisk_cmd" /></td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'status_show_kamailio_server') !== 'no') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="kamailio_server" name="kamailio_server"'.$sel.' /></td><td colspan="5">Show Kamailio SIP Server Status</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'status_show_xmpp_server') !== 'no') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="xmpp_server" name="xmpp_server"'.$sel.' /></td><td colspan="5">Show XMPP Server Status</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'status_show_adaptive_ban') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="adaptive_ban" name="adaptive_ban"'.$sel.' /></td><td colspan="5">Show Adaptive Ban Plugin Status</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'status_show_firewall_states') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="firewall_states" name="firewall_states"'.$sel.' /></td><td colspan="5">Show Firewall States</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">Hide SRC Ports:</td><td colspan="4">');
  $value = getPREFdef($global_prefs, 'status_firewall_sports_cmdstr');
  putHtml('<input type="text" size="48" maxlength="128" value="'.$value.'" name="firewall_sports" /></td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">Hide DST Ports:</td><td colspan="4">');
  $value = getPREFdef($global_prefs, 'status_firewall_dports_cmdstr');
  putHtml('<input type="text" size="48" maxlength="128" value="'.$value.'" name="firewall_dports" /></td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'status_ups_show_status') !== 'no') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="ups_status" name="ups_status"'.$sel.' /></td><td colspan="5">Show UPS Monitoring Status</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'status_show_hardware_monitoring') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="hardware_monitoring" name="hardware_monitoring"'.$sel.' /></td><td colspan="5">Show Hardware Monitoring</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'status_show_smart_monitoring') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="smart_monitoring" name="smart_monitoring"'.$sel.' /></td><td colspan="5">Show S.M.A.R.T Monitoring</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'status_show_system_logs') !== 'no') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="system_logs" name="system_logs"'.$sel.' /></td><td colspan="5">Show Latest System Logs</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">Hide Log Words:</td><td colspan="4">');
  $value = getPREFdef($global_prefs, 'status_exclude_logs_cmdstr');
  putHtml('<input type="text" size="48" maxlength="512" value="'.$value.'" name="exclude_logs" /></td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'status_exclude_extensions') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="exclude_extensions" name="exclude_extensions"'.$sel.' /></td><td colspan="5">Exclude 4-digit Extensions in SIP/IAX2 Peer Status</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'status_asterisk_manager') === 'no') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="disable_ami" name="disable_ami"'.$sel.' /></td><td colspan="5">Disable Asterisk Manager Interface for Asterisk Commands</td></tr>');

  putHtml('<tr class="dtrow0"><td colspan="6">&nbsp;</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Directory Tab Options:</strong>');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'directory_require_auth') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="directory_auth" name="directory_auth"'.$sel.' /></td><td colspan="5">Require Authentication for Directory Tab</td></tr>');

  putHtml('<tr class="dtrow0"><td colspan="6">&nbsp;</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Voicemail &amp; Monitor Tab Options:</strong>');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'voicemail_extern_notify') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="extern_notify" name="extern_notify"'.$sel.' /></td><td colspan="5">Enable Voicemail External Notify Script</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'voicemail_show_context') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="show_context" name="show_context"'.$sel.' /></td><td colspan="5">Show Voicemail Context in Voicemail Tab</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'voicemail_24_hour_format') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="hour_format" name="hour_format"'.$sel.' /></td><td colspan="5">Display 24 Hour Time Format instead of 12 Hour am/pm</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="3">Play WAV audio recordings:</td><td colspan="3">');
  $value = getPREFdef($global_prefs, 'monitor_play_inline');
  putHtml('<select name="play_inline">');
  putHtml('<option value="">as a download - HTTP/HTTPS</option>');
  $sel = ($value === 'html4' || $value === 'yes') ? ' selected="selected"' : '';  // allow deprecated value of 'yes'
  putHtml('<option value="html4"'.$sel.'>in HTML4 browser - HTTP/HTTPS</option>');
  $sel = ($value === 'html4-http') ? ' selected="selected"' : '';
  putHtml('<option value="html4-http"'.$sel.'>in HTML4 browser - HTTP only</option>');
  $sel = ($value === 'html5') ? ' selected="selected"' : '';
  putHtml('<option value="html5"'.$sel.'>in HTML5 browser - HTTP/HTTPS</option>');
  $sel = ($value === 'html5-http') ? ' selected="selected"' : '';
  putHtml('<option value="html5-http"'.$sel.'>in HTML5 browser - HTTP only</option>');
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow0"><td colspan="6">&nbsp;</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Follow-Me Tab Options:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="3">Follow-Me Numbers Displayed:</td><td colspan="3">');
  if (($value = getPREFdef($global_prefs, 'followme_numbers_displayed')) === '') {
    $value = '4';
  }
  putHtml('<select name="followme_maxnum">');
  for ($i = 1; $i <= 6; $i++) {
    $sel = ($i == $value) ? ' selected="selected"' : '';
    putHtml('<option value="'.$i.'"'.$sel.'>&nbsp;'.$i.'&nbsp;</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">Schedule Menu:</td><td colspan="4">');
  $value = getPREFdef($global_prefs, 'followme_schedule_menu_cmdstr');
  putHtml('<input type="text" size="48" maxlength="128" value="'.$value.'" name="followme_menu" /></td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="3">Dialed Number Context:</td><td colspan="3">');
  $value = getPREFdef($global_prefs, 'followme_number_context_cmdstr');
  putHtml('<input type="text" size="28" maxlength="64" value="'.$value.'" name="followme_number_context" /></td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="3">Caller Music Class:</td><td colspan="3">');
  $value = getPREFdef($global_prefs, 'followme_music_class_cmdstr');
  putHtml('<input type="text" size="28" maxlength="64" value="'.$value.'" name="followme_music_class" /></td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'followme_use_number_format') !== 'no') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="followme_format" name="followme_format"'.$sel.' /></td><td colspan="5">Use Caller*ID Tab Number Format Rules for Follow-Me</td></tr>');

  putHtml('<tr class="dtrow0"><td colspan="6">&nbsp;</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>MeetMe &amp; ConfBridge Tab Options:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">Redirect Path:</td><td colspan="4">');
  if (($value = getPREFdef($global_prefs, 'meetme_redirect_path_cmdstr')) === '') {
    $value = 'default,s,1';
  }
  putHtml('<input type="text" size="48" maxlength="96" value="'.$value.'" name="meetme_redirect" /></td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'meetme_channel_show') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="meetme_channel" name="meetme_channel"'.$sel.' /></td><td colspan="5">Display channel values in MeetMe and ConfBridge Tabs</td></tr>');

  putHtml('<tr class="dtrow0"><td colspan="6">&nbsp;</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>CDR Log Tab Options:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">CDR Log Format:</td><td colspan="4">');
  putHtml('<select name="cdr_default">');
  putHtml('<option value="standard">Standard cdr-csv</option>');
  $sel = (getPREFdef($global_prefs, 'cdrlog_default_format') === 'yes') ? ' selected="selected"' : '';
  putHtml('<option value="yes"'.$sel.'>Default cdr-custom</option>');
  $sel = (getPREFdef($global_prefs, 'cdrlog_default_format') === 'special') ? ' selected="selected"' : '';
  putHtml('<option value="special"'.$sel.'>Special cdr-custom</option>');
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">CDR Log Path:</td><td colspan="4">');
  if (($value = getPREFdef($global_prefs, 'cdrlog_log_file_cmdstr')) === '') {
    $value = '/var/log/asterisk/cdr-csv/Master.csv';
  }
  putHtml('<input type="text" size="48" maxlength="96" value="'.$value.'" name="cdr_logfile" /></td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'cdrlog_databases_show') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="cdr_databases" name="cdr_databases"'.$sel.' /></td><td colspan="5">Show multiple *.csv CDR Databases in CDR Log Path</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'cdrlog_extra_show') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="cdr_extra" name="cdr_extra"'.$sel.' /></td><td colspan="5">Display channel, dstchannel and disposition CDR values</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'cdrlog_last_show') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="cdr_last" name="cdr_last"'.$sel.' /></td><td colspan="5">Display');
  putHtml('<select name="cdr_last_cmd">');
  putHtml('<option value="disposition">disposition</option>');
  $sel = (getPREFdef($global_prefs, 'cdrlog_last_cmd') === 'channel') ? ' selected="selected"' : '';
  putHtml('<option value="channel"'.$sel.'>channel</option>');
  $sel = (getPREFdef($global_prefs, 'cdrlog_last_cmd') === 'dstchannel') ? ' selected="selected"' : '';
  putHtml('<option value="dstchannel"'.$sel.'>dstchannel</option>');
  $sel = (getPREFdef($global_prefs, 'cdrlog_last_cmd') === 'lastapp') ? ' selected="selected"' : '';
  putHtml('<option value="lastapp"'.$sel.'>lastapp</option>');
  $sel = (getPREFdef($global_prefs, 'cdrlog_last_cmd') === 'lastdata') ? ' selected="selected"' : '';
  putHtml('<option value="lastdata"'.$sel.'>lastdata</option>');
  $sel = (getPREFdef($global_prefs, 'cdrlog_last_cmd') === 'amaflags') ? ' selected="selected"' : '';
  putHtml('<option value="amaflags"'.$sel.'>amaflags</option>');
  $sel = (getPREFdef($global_prefs, 'cdrlog_last_cmd') === 'accountcode') ? ' selected="selected"' : '';
  putHtml('<option value="accountcode"'.$sel.'>accountcode</option>');
  $sel = (getPREFdef($global_prefs, 'cdrlog_last_cmd') === 'uniqueid') ? ' selected="selected"' : '';
  putHtml('<option value="uniqueid"'.$sel.'>uniqueid</option>');
  $sel = (getPREFdef($global_prefs, 'cdrlog_last_cmd') === 'userfield') ? ' selected="selected"' : '';
  putHtml('<option value="userfield"'.$sel.'>userfield</option>');
  putHtml('</select>');
  putHtml('CDR value</td></tr>');

  putHtml('<tr class="dtrow0"><td colspan="6">&nbsp;</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Speed Dial Tab Options:</strong>');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">Prefix:</td><td>');
  if (($value = getPREFdef($global_prefs, 'sysdial_ext_prefix_cmdstr')) === '') {
    $value = '11';
  }
  putHtml('<input type="text" size="8" maxlength="8" value="'.$value.'" name="ext_prefix" /></td>');
  putHtml('<td style="text-align: right;">Digits:</td><td colspan="2">');
  if (($value = getPREFdef($global_prefs, 'sysdial_ext_digits_cmdstr')) === '') {
    $value = '50';
  }
  putHtml('<select name="ext_digits">');
  for ($i = 10; $i <= 100; $i += 10) {
    if (($j = $i - 1) < 10) {
      $j = '0'.$j;
    }
    $sel = ($i == $value) ? ' selected="selected"' : '';
    putHtml('<option value="'.$i.'"'.$sel.'>00 to '.$j.'</option>');
  }
  $i = 1000;
  $j = 999;
  $sel = ($i == $value) ? ' selected="selected"' : '';
  putHtml('<option value="'.$i.'"'.$sel.'>00 to '.$j.'</option>');
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow0"><td colspan="6">&nbsp;</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Caller*ID, Blacklist &amp; Whitelist Tab Options:</strong>');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">Number Format:</td><td colspan="4">');
  if (($value = getPREFdef($global_prefs, 'number_format_cmdstr')) === '') {
    $value = '^[2-9][0-9][0-9][2-9][0-9][0-9][0-9][0-9][0-9][0-9]$';
  }
  putHtml('<input type="text" size="48" maxlength="128" value="'.$value.'" name="num_format" /></td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">Error String:</td><td colspan="4">');
  if (($value = getPREFdef($global_prefs, 'number_error_cmdstr')) === '') {
    $value = 'Number must be 10 digits in the format NXXNXXXXXX';
  }
  putHtml('<input type="text" size="48" maxlength="96" value="'.$value.'" name="num_error" /></td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="3">CID Name Max Length:</td><td colspan="3">');
  if (($value = getPREFdef($global_prefs, 'cidname_maxlen_cmdstr')) === '') {
    $value = '15';
  }
  putHtml('<input type="text" size="6" maxlength="2" value="'.$value.'" name="cidname_maxlen" /></td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">Blacklist Menu:</td><td colspan="4">');
  if (($value = getPREFdef($global_prefs, 'blacklist_action_menu_cmdstr')) === '') {
    $value = 'No Answer~Zapateller~Voicemail';
  }
  putHtml('<input type="text" size="48" maxlength="128" value="'.$value.'" name="blacklist_menu" /></td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">Whitelist Menu:</td><td colspan="4">');
  if (($value = getPREFdef($global_prefs, 'whitelist_action_menu_cmdstr')) === '') {
    $value = 'Voicemail~Priority~Standard~Follow-Me~IVR';
  }
  putHtml('<input type="text" size="48" maxlength="128" value="'.$value.'" name="whitelist_menu" /></td></tr>');

  putHtml('<tr class="dtrow0"><td colspan="6">&nbsp;</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Actionlist Tab Options:</strong>');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">Key Format:</td><td colspan="4">');
  if (($value = getPREFdef($global_prefs, 'actionlist_format_cmdstr')) === '') {
    $value = '^[A-Za-z0-9-]{2,20}$';
  }
  putHtml('<input type="text" size="48" maxlength="128" value="'.$value.'" name="actionlist_key_format" /></td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">Error String:</td><td colspan="4">');
  if (($value = getPREFdef($global_prefs, 'actionlist_error_cmdstr')) === '') {
    $value = 'Key must be alphanumeric, 2-20 characters';
  }
  putHtml('<input type="text" size="48" maxlength="96" value="'.$value.'" name="actionlist_key_error" /></td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">Action Menu:</td><td colspan="4">');
  $value = getPREFdef($global_prefs, 'actionlist_action_menu_cmdstr');
  putHtml('<input type="text" size="48" maxlength="128" value="'.$value.'" name="actionlist_menu" /></td></tr>');

  putHtml('<tr class="dtrow0"><td colspan="6">&nbsp;</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>SQL-Data Tab Options:</strong>');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'sqldata_create_schema') !== 'no') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="sqldata_create_schema" name="sqldata_create_schema"'.$sel.' /></td><td colspan="5">Create SIP &amp; Phone standard SQL schema</td></tr>');

  putHtml('<tr class="dtrow0"><td colspan="6">&nbsp;</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>PhoneProv Tab Options:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="3">Ext / CID Name(s) Displayed:</td><td colspan="3">');
  if (($value = getPREFdef($global_prefs, 'phoneprov_extensions_displayed')) === '') {
    $value = '2';
  }
  putHtml('<select name="phoneprov_maxnum">');
  for ($i = 1; $i <= 6; $i++) {
    $sel = ($i == $value) ? ' selected="selected"' : '';
    putHtml('<option value="'.$i.'"'.$sel.'>&nbsp;'.$i.'&nbsp;</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow0"><td colspan="6">&nbsp;</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Users Tab Options:</strong>');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'users_voicemail_hide_pass') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="users_hide_pass" name="users_hide_pass"'.$sel.' /></td><td colspan="5">Hide Passwords for Voicemail Users Mailboxes</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'users_voicemail_delete_vmdata') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="users_delete_vmdata" name="users_delete_vmdata"'.$sel.' /></td><td colspan="5">Remove User Voicemail Data when User is Deleted</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="3">Voicemail Users Context:</td><td colspan="3">');
  if (($value = getPREFdef($global_prefs, 'users_voicemail_context_cmdstr')) === '') {
    $value = 'default';
  }
  putHtml('<input type="text" size="28" maxlength="64" value="'.$value.'" name="voicemail_context" /></td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="3">Reload Voicemail Command:</td><td colspan="3">');
  if (($value = getPREFdef($global_prefs, 'users_voicemail_reload_cmdstr')) === '') {
    $value = 'module reload app_voicemail.so';
  }
  putHtml('<input type="text" size="28" maxlength="64" value="'.$value.'" name="voicemail_reload" /></td></tr>');

  putHtml('<tr class="dtrow0"><td colspan="6">&nbsp;</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Edit Tab Options:</strong>');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'disable_codemirror_editor') !== 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="codemirror_editor" name="codemirror_editor"'.$sel.' /></td><td colspan="2">CodeMirror Editor</td>');
  putHtml('<td style="text-align: right;">Theme:</td><td colspan="2">');
  $value = getPREFdef($global_prefs, 'edit_text_codemirror_theme');
  putHtml('<select name="codemirror_theme">');
  putHtml('<option value="">default</option>');
  $cm_theme_dir = getSYSlocation('/common/codemirror/theme');
  if (is_dir($cm_theme_dir)) {
    foreach (glob($cm_theme_dir.'/*.css') as $globfile) {
      $cm_theme = basename($globfile, '.css');
      $sel = ($value === $cm_theme) ? ' selected="selected"' : '';
      putHtml('<option value="'.$cm_theme.'"'.$sel.'>'.$cm_theme.'</option>');
    }
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'edit_keep_bak_files') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="bak_files" name="bak_files"'.$sel.' /></td><td colspan="5">Save original file as backup with a &quot;.bak&quot; suffix</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">Columns:</td><td>');
  if (($value = getPREFdef($global_prefs, 'edit_text_cols_cmdstr')) === '') {
    $value = '95';
  }
  putHtml('<input type="text" size="6" maxlength="3" value="'.$value.'" name="text_cols" /></td>');
  putHtml('<td style="text-align: right;">Rows:</td><td colspan="2">');
  if (($value = getPREFdef($global_prefs, 'edit_text_rows_cmdstr')) === '') {
    $value = '30';
  }
  putHtml('<input type="text" size="6" maxlength="2" value="'.$value.'" name="text_rows" /></td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">Shortcuts:<br /><i>(Path~Label)</i></td><td colspan="4">');
  echo '<textarea name="edittext_shortcut" rows="4" cols="40" wrap="off" class="edititemText">';
  if (($value = getPREFdef($global_prefs, 'edit_text_shortcut_cmdstr')) !== '') {
    foreach (explode(' ', $value) as $shortcut) {
      if ($shortcut !== '') {
        echo htmlspecialchars($shortcut), chr(13);
      }
    }
  }
  putHtml('</textarea>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow0"><td colspan="6">&nbsp;</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>System &amp; Staff Tab Options:</strong>');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'system_backup_hostname_domain') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="backup_hostname_domain" name="backup_hostname_domain"'.$sel.' /></td><td colspan="5">Backup filename uses both Hostname and Domain</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'system_backup_compress_gzip') !== 'no') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="backup_gzip" name="backup_gzip"'.$sel.' /></td><td colspan="5">Backup tar archives compressed with gzip [.gz]</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'system_backup_asturw') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="backup_asturw" name="backup_asturw"'.$sel.' /></td><td colspan="5">Backup unionfs partition as /mnt/kd/asturw.tar[.gz]</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'system_backup_temp_disk') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="backup_temp" name="backup_temp"'.$sel.' /></td><td colspan="5">Backup temporary file uses /mnt/kd/ instead of /tmp/</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="3">Backup Exclude Suffixes:</td><td colspan="3">');
  $value = getPREFdef($global_prefs, 'system_backup_exclude_suffix_cmdstr');
  putHtml('<input type="text" size="28" maxlength="128" value="'.$value.'" name="backup_exclude_suffix" /></td></tr>');

  if (system_image_arch() === 'genx86_64-vm') {
    putHtml('<tr class="dtrow1"><td style="text-align: right;">');
    $sel = (getPREFdef($global_prefs, 'system_reboot_vm_classic_full') !== 'no') ? ' checked="checked"' : '';
    putHtml('<input type="checkbox" value="reboot_vm_classic_full" name="reboot_vm_classic_full"'.$sel.' /></td><td colspan="5">Disable faster "kernel-reboot" System Reboot</td></tr>');
  } else {
    putHtml('<tr class="dtrow1"><td style="text-align: right;">');
    $sel = (getPREFdef($global_prefs, 'system_reboot_classic_full') === 'yes') ? ' checked="checked"' : '';
    putHtml('<input type="checkbox" value="reboot_classic_full" name="reboot_classic_full"'.$sel.' /></td><td colspan="5">Disable faster "kernel-reboot" System Reboot</td></tr>');
  }

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="3">System Reboot Timer:</td><td colspan="3">');
  if (($value = getPREFdef($global_prefs, 'system_reboot_timer_adjust')) === '') {
    $value = '0';
  }
  putHtml('<select name="reboot_timer">');
  $reboot_timer_label = array( '40', '30', '20', '10', '0',
                              '-10', '-20', '-30', '-40', '-50', '-60', '-70', '-80', '-90', '-100', '-110');
  foreach ($reboot_timer_label as $adjust) {
    $sel = ($value === $adjust) ? ' selected="selected"' : '';
    if ((int)$adjust == 0) {
      putHtml('<option value="'.$adjust.'"'.$sel.'>default reboot time</option>');
    } elseif ((int)$adjust < 0) {
      putHtml('<option value="'.$adjust.'"'.$sel.'>decrease by '.(-((int)$adjust)).' secs</option>');
    } else {
      putHtml('<option value="'.$adjust.'"'.$sel.'>increase by '.$adjust.' secs</option>');
    }
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="3">Reload Asterisk Command:</td><td colspan="3">');
  if (($value = getPREFdef($global_prefs, 'system_asterisk_reload_cmdstr')) === '') {
    $value = 'module reload';
  }
  putHtml('<input type="text" size="28" maxlength="64" value="'.$value.'" name="asterisk_reload" /></td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">Repository URL:</td><td colspan="4">');
  if (($value = getPREFdef($global_prefs, 'system_firmware_repository_url')) === '') {
    $value = asteriskURLrepo();
  }
  putHtml('<input type="text" size="48" maxlength="128" value="'.$value.'" name="repository_url" /></td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">Sounds Pkg URL:</td><td colspan="4">');
  if (($value = getPREFdef($global_prefs, 'system_asterisk_sounds_url')) === '') {
    $value = 'https://downloads.asterisk.org/pub/telephony/sounds';
  }
  putHtml('<input type="text" size="48" maxlength="128" value="'.$value.'" name="sounds_url" /></td></tr>');

  putHtml('<tr class="dtrow0"><td colspan="6">&nbsp;</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Distinguished Name:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="3">Country Name:</td><td colspan="3">');
  $value = getPREFdef($global_prefs, 'dn_country_name_cmdstr');
  putHtml('<input type="text" size="4" maxlength="2" value="'.$value.'" name="dn_country_name" /></td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="3">State/Province Name:</td><td colspan="3">');
  $value = getPREFdef($global_prefs, 'dn_state_name_cmdstr');
  putHtml('<input type="text" size="28" maxlength="64" value="'.$value.'" name="dn_state_name" /></td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="3">Locality Name:</td><td colspan="3">');
  $value = getPREFdef($global_prefs, 'dn_locality_name_cmdstr');
  putHtml('<input type="text" size="28" maxlength="64" value="'.$value.'" name="dn_locality_name" /></td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="3">Organization Name:</td><td colspan="3">');
  $value = getPREFdef($global_prefs, 'dn_org_name_cmdstr');
  putHtml('<input type="text" size="28" maxlength="64" value="'.$value.'" name="dn_org_name" /></td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="3">Organizational Unit Name:</td><td colspan="3">');
  $value = getPREFdef($global_prefs, 'dn_org_unit_cmdstr');
  putHtml('<input type="text" size="28" maxlength="64" value="'.$value.'" name="dn_org_unit" /></td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="3">Common Name:</td><td colspan="3">');
  $value = getPREFdef($global_prefs, 'dn_common_name_cmdstr');
  putHtml('<input type="text" size="28" maxlength="64" value="'.$value.'" name="dn_common_name" /></td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="3">Email Address:</td><td colspan="3">');
  $value = getPREFdef($global_prefs, 'dn_email_address_cmdstr');
  putHtml('<input type="text" size="28" maxlength="64" value="'.$value.'" name="dn_email_address" /></td></tr>');

  putHtml('<tr class="dtrow0"><td colspan="6">&nbsp;</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>General Options:</strong>');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">Title Name:</td><td colspan="4">');
  if (($value = getPREFdef($global_prefs, 'title_name_cmdstr')) === '') {
    $value = 'AstLinux Management';
  }
  putHtml('<input type="text" size="48" maxlength="64" value="'.$value.'" name="title_name" /></td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">External URL Link:</td><td colspan="4">');
  $value = getPREFdef($global_prefs, 'external_url_link_cmdstr');
  putHtml('<input type="text" size="48" maxlength="128" value="'.$value.'" name="external_url_link" /></td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">External URL Name:</td><td colspan="4">');
  $value = getPREFdef($global_prefs, 'external_url_name_cmdstr');
  putHtml('<input type="text" size="48" maxlength="64" value="'.$value.'" name="external_url_name" /></td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">External Wiki Link:</td><td colspan="4">');
  $value = getPREFdef($global_prefs, 'external_wiki_link_cmdstr');
  putHtml('<input type="text" size="48" maxlength="128" value="'.$value.'" name="external_wiki_link" /></td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">External CLI Link:</td><td colspan="4">');
  $value = getPREFdef($global_prefs, 'external_cli_link_cmdstr');
  putHtml('<input type="text" size="48" maxlength="128" value="'.$value.'" name="external_cli_link" /></td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">External FOP2 Link:</td><td colspan="4">');
  $sel = (getPREFdef($global_prefs, 'external_fop2_https') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="external_fop2_https" name="external_fop2_https"'.$sel.' />&nbsp;Use HTTPS</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'tab_directory_show') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="tab_directory" name="tab_directory"'.$sel.' /></td><td colspan="5">Show Directory Tab</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'tab_voicemail_show') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="tab_voicemail" name="tab_voicemail"'.$sel.' /></td><td colspan="5">Show Voicemail Tab</td></tr>');
  putHtml('<tr class="dtrow1"><td>&nbsp;</td><td colspan="5">');
  $sel = (getPREFdef($global_prefs, 'tab_voicemail_disable_staff') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="voicemail_disable_staff" name="voicemail_disable_staff"'.$sel.' />&nbsp;Disable Voicemail Tab for &quot;staff&quot; user</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'tab_monitor_show') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="tab_monitor" name="tab_monitor"'.$sel.' /></td><td colspan="5">Show Monitor Tab</td></tr>');
  putHtml('<tr class="dtrow1"><td>&nbsp;</td><td colspan="5">');
  $sel = (getPREFdef($global_prefs, 'tab_monitor_disable_staff') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="monitor_disable_staff" name="monitor_disable_staff"'.$sel.' />&nbsp;Disable Monitor Tab for &quot;staff&quot; user</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'tab_followme_show') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="tab_followme" name="tab_followme"'.$sel.' /></td><td colspan="5">Show Follow-Me Tab'.includeTOPICinfo('followme-dialplan').'</td></tr>');
  putHtml('<tr class="dtrow1"><td>&nbsp;</td><td colspan="5">');
  $sel = (getPREFdef($global_prefs, 'tab_followme_disable_staff') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="followme_disable_staff" name="followme_disable_staff"'.$sel.' />&nbsp;Disable Follow-Me Tab for &quot;staff&quot; user</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'tab_meetme_show') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="tab_meetme" name="tab_meetme"'.$sel.' /></td><td colspan="5">Show MeetMe Tab</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'tab_confbridge_show') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="tab_confbridge" name="tab_confbridge"'.$sel.' /></td><td colspan="5">Show ConfBridge Tab</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'tab_cdrlog_show') !== 'no') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="tab_cdrlog" name="tab_cdrlog"'.$sel.' /></td><td colspan="5">Show CDR Log Tab</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'tab_sysdial_show') !== 'no') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="tab_sysdial" name="tab_sysdial"'.$sel.' /></td><td colspan="5">Show Speed Dial Tab'.includeTOPICinfo('sysdial-dialplan').'</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'tab_cidname_show') !== 'no') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="tab_cidname" name="tab_cidname"'.$sel.' /></td><td colspan="5">Show Caller*ID Tab'.includeTOPICinfo('cidname-dialplan').'</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'tab_blacklist_show') !== 'no') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="tab_blacklist" name="tab_blacklist"'.$sel.' /></td><td colspan="5">Show Blacklist Tab'.includeTOPICinfo('blacklist-dialplan').'</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'tab_whitelist_show') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="tab_whitelist" name="tab_whitelist"'.$sel.' /></td><td colspan="5">Show Whitelist Tab'.includeTOPICinfo('whitelist-dialplan').'</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'tab_actionlist_show') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="tab_actionlist" name="tab_actionlist"'.$sel.' /></td><td colspan="5">Show Actionlist Tab'.includeTOPICinfo('actionlist-dialplan').'</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'tab_sqldata_show') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="tab_sqldata" name="tab_sqldata"'.$sel.' /></td><td colspan="5">Show SQL-Data Tab'.includeTOPICinfo('sqldata-dialplan').'</td></tr>');
  putHtml('<tr class="dtrow1"><td>&nbsp;</td><td colspan="5">');
  $sel = (getPREFdef($global_prefs, 'tab_sqldata_disable_staff') !== 'no') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="sqldata_disable_staff" name="sqldata_disable_staff"'.$sel.' />&nbsp;Disable SQL-Data Tab for &quot;staff&quot; user</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'tab_ldapab_show') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="tab_ldapab" name="tab_ldapab"'.$sel.' /></td><td colspan="5">Show LDAP-AB Tab</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'tab_phoneprov_show') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="tab_phoneprov" name="tab_phoneprov"'.$sel.' /></td><td colspan="5">Show PhoneProv Tab</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'tab_users_show') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="tab_users" name="tab_users"'.$sel.' /></td><td colspan="5">Show Users Tab</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'tab_vnstat_show') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="tab_vnstat" name="tab_vnstat"'.$sel.' /></td><td colspan="5">Show vnStat Tab</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'tab_netstat_show') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="tab_netstat" name="tab_netstat"'.$sel.' /></td><td colspan="5">Show NetStat Tab</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'tab_monit_show') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="tab_monit" name="tab_monit"'.$sel.' /></td><td colspan="5">Show Monit Tab</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'tab_network_show') !== 'no') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="tab_network" name="tab_network"'.$sel.' /></td><td colspan="5">Show Network Tab</td></tr>');
  putHtml('<tr class="dtrow1"><td>&nbsp;</td><td colspan="5">');
  $sel = (getPREFdef($global_prefs, 'tab_dnshosts_disable_staff') !== 'no') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="dnshosts_disable_staff" name="dnshosts_disable_staff"'.$sel.' />&nbsp;Disable DNS Hosts Tab for &quot;staff&quot; user</td></tr>');
  putHtml('<tr class="dtrow1"><td>&nbsp;</td><td colspan="5">');
  $sel = (getPREFdef($global_prefs, 'tab_xmpp_disable_staff') !== 'no') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="xmpp_disable_staff" name="xmpp_disable_staff"'.$sel.' />&nbsp;Disable XMPP Users Tab for &quot;staff&quot; user</td></tr>');


  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'tab_edit_show') !== 'no') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="tab_edit" name="tab_edit"'.$sel.' /></td><td colspan="5">Show Edit Tab</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'tab_cli_show') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="tab_cli" name="tab_cli"'.$sel.' /></td><td colspan="5">Show CLI Tab</td></tr>');
  putHtml('<tr class="dtrow1"><td>&nbsp;</td><td colspan="5">');
  $sel = (getPREFdef($global_prefs, 'tab_cli_disable_staff') !== 'no') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="cli_disable_staff" name="cli_disable_staff"'.$sel.' />&nbsp;Disable CLI Tab for &quot;staff&quot; user</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'tab_fossil_show') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="tab_fossil" name="tab_fossil"'.$sel.' /></td><td colspan="5">Show Fossil Tab</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'tab_staff_disable_staff') !== 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="tab_staff" name="tab_staff"'.$sel.' /></td><td colspan="5">Show Staff Tab for &quot;staff&quot; user</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getPREFdef($global_prefs, 'tab_wiki_show') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="tab_wiki" name="tab_wiki"'.$sel.' /></td><td colspan="5">Show Wiki Tab</td></tr>');

  putHtml('</table>');
  putHtml('</form>');
  putHtml('</center></td></tr></table>');
  putHtml('</center>');
} // End of HTTP GET
require_once '../common/footer.php';

?>
