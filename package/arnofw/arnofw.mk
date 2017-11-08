#############################################################
#
# arnofw
#
#############################################################

ARNOFW_VERSION = 2.0.2
ARNOFW_SOURCE = arno-iptables-firewall_$(ARNOFW_VERSION).tar.gz
ARNOFW_SITE = https://github.com/arno-iptables-firewall/aif/releases/download/$(ARNOFW_VERSION)

ARNOFW_CONFIG_DIR = etc/arno-iptables-firewall
ARNOFW_SCRIPT_DIR = usr/share/arno-iptables-firewall

define ARNOFW_INSTALL_TARGET_CMDS
	ln -sf /tmp/$(ARNOFW_CONFIG_DIR) $(TARGET_DIR)/$(ARNOFW_CONFIG_DIR)
	## Install main script
	$(INSTALL) -D -m 0755 $(@D)/bin/arno-iptables-firewall $(TARGET_DIR)/usr/sbin/arno-iptables-firewall
	$(SED) '1 s:^#!/bin/sh:#!/bin/ash:' $(TARGET_DIR)/usr/sbin/arno-iptables-firewall
	## Install firewall.conf and supporting files
	mkdir -p $(TARGET_DIR)/stat/$(ARNOFW_CONFIG_DIR)
	$(INSTALL) -m 0444 package/arnofw/arnofw.serial $(TARGET_DIR)/stat/$(ARNOFW_CONFIG_DIR)/serial
	$(INSTALL) -m 0644 $(@D)/etc/arno-iptables-firewall/firewall.conf $(@D)/etc/arno-iptables-firewall/custom-rules \
		$(TARGET_DIR)/stat/$(ARNOFW_CONFIG_DIR)
	## Edit the default firewall.conf
	$(SED) 's:^PLUGIN_BIN_PATH=.*$$:PLUGIN_BIN_PATH="/$(ARNOFW_SCRIPT_DIR)/plugins":' \
	    -e 's:^ENV_FILE=.*$$:ENV_FILE="/$(ARNOFW_SCRIPT_DIR)/environment":' \
	    -e 's:^LOCAL_CONFIG_FILE=.*$$:LOCAL_CONFIG_FILE="/$(ARNOFW_SCRIPT_DIR)/astlinux.shim":' \
	    -e 's:^NAT_LOCAL_REDIRECT=.*$$:NAT_LOCAL_REDIRECT=1:' \
	    -e 's:^IGMP_LOG=.*$$:IGMP_LOG=0:' \
	    -e 's:^RESERVED_NET_LOG=.*$$:RESERVED_NET_LOG=0:' \
		$(TARGET_DIR)/stat/$(ARNOFW_CONFIG_DIR)/firewall.conf
	## Install plugin scripts and configs
	mkdir -p $(TARGET_DIR)/stat/$(ARNOFW_CONFIG_DIR)/plugins
	$(INSTALL) -m 0644 $(@D)/etc/arno-iptables-firewall/plugins/*.conf $(TARGET_DIR)/stat/$(ARNOFW_CONFIG_DIR)/plugins
	cp -a $(@D)/share/arno-iptables-firewall $(TARGET_DIR)/$(ARNOFW_SCRIPT_DIR)
	$(INSTALL) -m 0444 package/arnofw/arnofw.wrapper $(TARGET_DIR)/$(ARNOFW_SCRIPT_DIR)/astlinux.shim
	$(INSTALL) -D -m 0755 package/arnofw/reload-spamhaus-drop $(TARGET_DIR)/usr/sbin/reload-spamhaus-drop
	$(INSTALL) -D -m 0755 package/arnofw/reload-blocklist-netset $(TARGET_DIR)/usr/sbin/reload-blocklist-netset
	## Remove plugin CHANGELOG's
	rm -f $(TARGET_DIR)/$(ARNOFW_SCRIPT_DIR)/plugins/*.CHANGELOG
	##
	## Remove plugins that we don't use
	##
	rm -f $(TARGET_DIR)/$(ARNOFW_SCRIPT_DIR)/plugins/??linux-upnp-igd.plugin
	rm -f $(TARGET_DIR)/stat/$(ARNOFW_CONFIG_DIR)/plugins/linux-upnp-igd.conf
	rm -f $(TARGET_DIR)/$(ARNOFW_SCRIPT_DIR)/plugins/??traffic-accounting.plugin
	rm -f $(TARGET_DIR)/$(ARNOFW_SCRIPT_DIR)/plugins/traffic-accounting-*
	rm -f $(TARGET_DIR)/stat/$(ARNOFW_CONFIG_DIR)/plugins/traffic-accounting.conf
	rm -f $(TARGET_DIR)/$(ARNOFW_SCRIPT_DIR)/plugins/??rpc.plugin
	rm -f $(TARGET_DIR)/stat/$(ARNOFW_CONFIG_DIR)/plugins/rpc.conf
	##
	## Overwrite the config files with our custom versions
	##
	$(INSTALL) -m 0644 package/arnofw/ipsec-vpn-astlinux.conf $(TARGET_DIR)/stat/$(ARNOFW_CONFIG_DIR)/plugins/ipsec-vpn.conf
	$(INSTALL) -m 0644 package/arnofw/sip-voip-astlinux.conf $(TARGET_DIR)/stat/$(ARNOFW_CONFIG_DIR)/plugins/sip-voip.conf
	$(INSTALL) -m 0644 package/arnofw/ipv6-over-ipv4-astlinux.conf $(TARGET_DIR)/stat/$(ARNOFW_CONFIG_DIR)/plugins/ipv6-over-ipv4.conf
	##
	## Install local version of Adaptive Ban plugin
	##
	$(INSTALL) -m 0644 package/arnofw/adaptive-ban/95adaptive-ban.plugin.sh $(TARGET_DIR)/$(ARNOFW_SCRIPT_DIR)/plugins/95adaptive-ban.plugin
	$(INSTALL) -m 0755 package/arnofw/adaptive-ban/adaptive-ban-helper.sh $(TARGET_DIR)/$(ARNOFW_SCRIPT_DIR)/plugins/adaptive-ban-helper
	$(INSTALL) -m 0644 package/arnofw/adaptive-ban/adaptive-ban.conf $(TARGET_DIR)/stat/$(ARNOFW_CONFIG_DIR)/plugins/adaptive-ban.conf
	##
	## Install local version of DynDNS Host Open plugin
	##
	$(INSTALL) -m 0644 package/arnofw/dyndns-host-open/50dyndns-host-open.plugin.sh $(TARGET_DIR)/$(ARNOFW_SCRIPT_DIR)/plugins/50dyndns-host-open.plugin
	$(INSTALL) -m 0755 package/arnofw/dyndns-host-open/dyndns-host-open-helper.sh $(TARGET_DIR)/$(ARNOFW_SCRIPT_DIR)/plugins/dyndns-host-open-helper
	$(INSTALL) -m 0644 package/arnofw/dyndns-host-open/dyndns-host-open.conf $(TARGET_DIR)/stat/$(ARNOFW_CONFIG_DIR)/plugins/dyndns-host-open.conf
	##
	## Install local version of DynDNS IPv6 Forward plugin
	##
	$(INSTALL) -m 0644 package/arnofw/dyndns-ipv6-forward/50dyndns-ipv6-forward.plugin.sh $(TARGET_DIR)/$(ARNOFW_SCRIPT_DIR)/plugins/50dyndns-ipv6-forward.plugin
	$(INSTALL) -m 0755 package/arnofw/dyndns-ipv6-forward/dyndns-ipv6-forward-helper.sh $(TARGET_DIR)/$(ARNOFW_SCRIPT_DIR)/plugins/dyndns-ipv6-forward-helper
	$(INSTALL) -m 0644 package/arnofw/dyndns-ipv6-forward/dyndns-ipv6-forward.conf $(TARGET_DIR)/stat/$(ARNOFW_CONFIG_DIR)/plugins/dyndns-ipv6-forward.conf
	##
	## Install local version of DynDNS IPv6 Open plugin
	##
	$(INSTALL) -m 0644 package/arnofw/dyndns-ipv6-open/50dyndns-ipv6-open.plugin.sh $(TARGET_DIR)/$(ARNOFW_SCRIPT_DIR)/plugins/50dyndns-ipv6-open.plugin
	$(INSTALL) -m 0755 package/arnofw/dyndns-ipv6-open/dyndns-ipv6-open-helper.sh $(TARGET_DIR)/$(ARNOFW_SCRIPT_DIR)/plugins/dyndns-ipv6-open-helper
	$(INSTALL) -m 0644 package/arnofw/dyndns-ipv6-open/dyndns-ipv6-open.conf $(TARGET_DIR)/stat/$(ARNOFW_CONFIG_DIR)/plugins/dyndns-ipv6-open.conf
	##
	## Install local version of Traffic Shaper plugin
	##
	$(INSTALL) -m 0644 package/arnofw/traffic-shaper/60traffic-shaper.plugin.sh $(TARGET_DIR)/$(ARNOFW_SCRIPT_DIR)/plugins/60traffic-shaper.plugin
	$(INSTALL) -m 0644 package/arnofw/traffic-shaper/traffic-shaper-astlinux.conf $(TARGET_DIR)/stat/$(ARNOFW_CONFIG_DIR)/plugins/traffic-shaper.conf
	##
	## Install local version of OpenVPN Server plugin
	##
	$(INSTALL) -m 0644 package/arnofw/openvpn-server/50openvpn-server.plugin.sh $(TARGET_DIR)/$(ARNOFW_SCRIPT_DIR)/plugins/50openvpn-server.plugin
	$(INSTALL) -m 0644 package/arnofw/openvpn-server/openvpn-server-astlinux.conf $(TARGET_DIR)/stat/$(ARNOFW_CONFIG_DIR)/plugins/openvpn-server.conf
	##
	## Install local version of Time Schedule Host Block plugin
	##
	$(INSTALL) -m 0644 package/arnofw/time-schedule-host-block/30time-schedule-host-block.plugin.sh $(TARGET_DIR)/$(ARNOFW_SCRIPT_DIR)/plugins/30time-schedule-host-block.plugin
	$(INSTALL) -m 0644 package/arnofw/time-schedule-host-block/time-schedule-host-block.conf $(TARGET_DIR)/stat/$(ARNOFW_CONFIG_DIR)/plugins/time-schedule-host-block.conf
	##
	## Install local version of SIP User-Agent plugin
	##
	$(INSTALL) -m 0644 package/arnofw/sip-user-agent/30sip-user-agent.plugin.sh $(TARGET_DIR)/$(ARNOFW_SCRIPT_DIR)/plugins/30sip-user-agent.plugin
	$(INSTALL) -m 0644 package/arnofw/sip-user-agent/sip-user-agent.conf $(TARGET_DIR)/stat/$(ARNOFW_CONFIG_DIR)/plugins/sip-user-agent.conf
	##
	## Install local version of PPTP VPN plugin
	##
	$(INSTALL) -m 0644 package/arnofw/pptp-vpn/50pptp-vpn.plugin.sh $(TARGET_DIR)/$(ARNOFW_SCRIPT_DIR)/plugins/50pptp-vpn.plugin
	$(INSTALL) -m 0644 package/arnofw/pptp-vpn/pptp-vpn-astlinux.conf $(TARGET_DIR)/stat/$(ARNOFW_CONFIG_DIR)/plugins/pptp-vpn.conf
	##
	## Install local version of WireGuard VPN plugin
	##
	$(INSTALL) -m 0644 package/arnofw/wireguard-vpn/50wireguard-vpn.plugin.sh $(TARGET_DIR)/$(ARNOFW_SCRIPT_DIR)/plugins/50wireguard-vpn.plugin
	$(INSTALL) -m 0644 package/arnofw/wireguard-vpn/wireguard-vpn-astlinux.conf $(TARGET_DIR)/stat/$(ARNOFW_CONFIG_DIR)/plugins/wireguard-vpn.conf
	##
	## Install local version of miniupnpd plugin
	##
	$(INSTALL) -m 0644 package/arnofw/miniupnpd/50miniupnpd.plugin.sh $(TARGET_DIR)/$(ARNOFW_SCRIPT_DIR)/plugins/50miniupnpd.plugin
	$(INSTALL) -m 0644 package/arnofw/miniupnpd/miniupnpd-astlinux.conf $(TARGET_DIR)/stat/$(ARNOFW_CONFIG_DIR)/plugins/miniupnpd.conf
	##
	## Install local version of net-prefix-translation plugin
	##
	$(INSTALL) -m 0644 package/arnofw/net-prefix-translation/20net-prefix-translation.plugin.sh $(TARGET_DIR)/$(ARNOFW_SCRIPT_DIR)/plugins/20net-prefix-translation.plugin
	$(INSTALL) -m 0644 package/arnofw/net-prefix-translation/net-prefix-translation.conf $(TARGET_DIR)/stat/$(ARNOFW_CONFIG_DIR)/plugins/net-prefix-translation.conf
endef

define ARNOFW_CLEAN_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/arno-iptables-firewall
	rm -f $(TARGET_DIR)/$(ARNOFW_CONFIG_DIR)
	rm -rf $(TARGET_DIR)/$(ARNOFW_SCRIPT_DIR)
	rm -rf $(TARGET_DIR)/stat/$(ARNOFW_CONFIG_DIR)
	rm -f $(TARGET_DIR)/usr/sbin/reload-spamhaus-drop
	rm -f $(TARGET_DIR)/usr/sbin/reload-blocklist-netset
endef

$(eval $(call GENTARGETS,package,arnofw))
