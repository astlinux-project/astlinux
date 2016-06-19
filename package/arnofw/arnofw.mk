#############################################################
#
# Arno's IPtables Firewall Script
#
#############################################################
ARNOFW_VER := 2.0.1f
ARNOFW_ROOT := arno-iptables-firewall
ARNOFW_SOURCE := $(ARNOFW_ROOT)_$(ARNOFW_VER).tar.gz
ARNOFW_SITE := http://rocky.eld.leidenuniv.nl/arno-iptables-firewall
#ARNOFW_SITE := http://files.astlinux-project.org
ARNOFW_DIR := $(BUILD_DIR)/$(ARNOFW_ROOT)_$(ARNOFW_VER)
ARNOFW_CAT := zcat
ARNOFW_TARGET_BINARY := /usr/sbin/arno-iptables-firewall
ARNOFW_CONFIG_DIR := /etc/arno-iptables-firewall
ARNOFW_SCRIPT_DIR := /usr/share/arno-iptables-firewall
ARNOFW_PLUGIN_CONFIG_DIR := $(ARNOFW_CONFIG_DIR)/plugins
ARNOFW_PLUGIN_SCRIPT_DIR := $(ARNOFW_SCRIPT_DIR)/plugins
ARNOFW_CONFIG_SHIM := $(ARNOFW_SCRIPT_DIR)/astlinux.shim
ARNOFW_CONFIG_SERIAL := $(ARNOFW_CONFIG_DIR)/serial

$(DL_DIR)/$(ARNOFW_SOURCE):
	$(WGET) -P $(DL_DIR) $(ARNOFW_SITE)/$(ARNOFW_SOURCE)

$(ARNOFW_DIR)/.unpacked: $(DL_DIR)/$(ARNOFW_SOURCE)
	$(ARNOFW_CAT) $(DL_DIR)/$(ARNOFW_SOURCE) \
		| tar -C $(BUILD_DIR) $(TAR_OPTIONS) -
	touch $(ARNOFW_DIR)/.unpacked

$(ARNOFW_DIR)/.patched: $(ARNOFW_DIR)/.unpacked
	toolchain/patch-kernel.sh $(ARNOFW_DIR) package/arnofw/ arnofw-\*.patch
	touch $(ARNOFW_DIR)/.patched

#
# the second pattern in the 2nd SED command comments out variables that
# will be handled by the wrapper instead.  this is just to avoid confusion.
#
$(TARGET_DIR)$(ARNOFW_TARGET_BINARY): $(ARNOFW_DIR)/.patched
	ln -sf /tmp$(ARNOFW_CONFIG_DIR) $(TARGET_DIR)$(ARNOFW_CONFIG_DIR)
	$(INSTALL) -D -m 0755 $(ARNOFW_DIR)/bin/arno-iptables-firewall \
		$(TARGET_DIR)$(ARNOFW_TARGET_BINARY)
	$(SED) '1 s:^#!/bin/sh:#!/bin/ash:' \
		$(TARGET_DIR)$(ARNOFW_TARGET_BINARY)
	mkdir -p $(TARGET_DIR)/stat$(ARNOFW_CONFIG_DIR)
	$(INSTALL) -m 0444 package/arnofw/arnofw.serial \
		$(TARGET_DIR)/stat$(ARNOFW_CONFIG_SERIAL)
	$(INSTALL) -m 0644 $(ARNOFW_DIR)/etc/arno-iptables-firewall/firewall.conf \
		$(ARNOFW_DIR)/etc/arno-iptables-firewall/custom-rules \
		$(TARGET_DIR)/stat$(ARNOFW_CONFIG_DIR)
	$(SED) 's:^PLUGIN_BIN_PATH="[^"]*":PLUGIN_BIN_PATH="$(ARNOFW_SCRIPT_DIR)/plugins":' \
	    -e 's:^ENV_FILE="[^"]*":ENV_FILE="$(ARNOFW_SCRIPT_DIR)/environment":' \
	    -e 's:^ENV_FILE=[^"]*$$:ENV_FILE="$(ARNOFW_SCRIPT_DIR)/environment":' \
	    -e 's:^LOCAL_CONFIG_FILE="":LOCAL_CONFIG_FILE="$(ARNOFW_CONFIG_SHIM)":' \
	    -e 's:^(INT_IF|EXT_IF|MODEM_IF|INTERNAL_NET|NAT|NAT_INTERNAL_NET|EXT_IF_DHCP_IP)=:#&:' \
	    -e 's:^NAT_LOCAL_REDIRECT=0$$:NAT_LOCAL_REDIRECT=1:' \
	    -e 's:^IGMP_LOG=1$$:IGMP_LOG=0:' \
	    -e 's:^RESERVED_NET_LOG=1$$:RESERVED_NET_LOG=0:' \
		$(TARGET_DIR)/stat$(ARNOFW_CONFIG_DIR)/firewall.conf
	mkdir -p $(TARGET_DIR)/stat$(ARNOFW_PLUGIN_CONFIG_DIR)
	$(INSTALL) -m 0644 $(ARNOFW_DIR)/etc/arno-iptables-firewall/plugins/*.conf \
		$(TARGET_DIR)/stat$(ARNOFW_PLUGIN_CONFIG_DIR)
	cp -a $(ARNOFW_DIR)/share/arno-iptables-firewall \
		$(TARGET_DIR)$(ARNOFW_SCRIPT_DIR)
	$(INSTALL) -m 0444 package/arnofw/arnofw.wrapper \
		$(TARGET_DIR)$(ARNOFW_CONFIG_SHIM)
	$(INSTALL) -D -m 0755 package/arnofw/reload-spamhaus-drop \
		$(TARGET_DIR)/usr/sbin/reload-spamhaus-drop
	@rm -f $(TARGET_DIR)$(ARNOFW_PLUGIN_SCRIPT_DIR)/*.CHANGELOG
	@echo
	@echo "Remove plugins that don't apply."
	@echo
	rm -f $(TARGET_DIR)$(ARNOFW_PLUGIN_SCRIPT_DIR)/??linux-upnp-igd.plugin \
	       $(TARGET_DIR)/stat$(ARNOFW_PLUGIN_CONFIG_DIR)/linux-upnp-igd.conf
	rm -f $(TARGET_DIR)$(ARNOFW_PLUGIN_SCRIPT_DIR)/??traffic-accounting.plugin \
	       $(TARGET_DIR)$(ARNOFW_PLUGIN_SCRIPT_DIR)/traffic-accounting-* \
	       $(TARGET_DIR)/stat$(ARNOFW_PLUGIN_CONFIG_DIR)/traffic-accounting.conf
	rm -f $(TARGET_DIR)$(ARNOFW_PLUGIN_SCRIPT_DIR)/??rpc.plugin \
	       $(TARGET_DIR)/stat$(ARNOFW_PLUGIN_CONFIG_DIR)/rpc.conf
	@echo
	@echo "Clobber the config files from the tarball with our shim-friendly versions."
	@echo
	$(INSTALL) -m 0644 package/arnofw/ipsec-vpn-astlinux.conf \
		$(TARGET_DIR)/stat$(ARNOFW_PLUGIN_CONFIG_DIR)/ipsec-vpn.conf
	$(INSTALL) -m 0644 package/arnofw/sip-voip-astlinux.conf \
		$(TARGET_DIR)/stat$(ARNOFW_PLUGIN_CONFIG_DIR)/sip-voip.conf
	$(INSTALL) -m 0644 package/arnofw/ipv6-over-ipv4-astlinux.conf \
		$(TARGET_DIR)/stat$(ARNOFW_PLUGIN_CONFIG_DIR)/ipv6-over-ipv4.conf
	@echo
	@echo "Install local version of Adaptive Ban plugin."
	@echo
	$(INSTALL) -m 0644 package/arnofw/adaptive-ban/95adaptive-ban.plugin.sh \
		$(TARGET_DIR)$(ARNOFW_PLUGIN_SCRIPT_DIR)/95adaptive-ban.plugin
	$(INSTALL) -m 0755 package/arnofw/adaptive-ban/adaptive-ban-helper.sh \
		$(TARGET_DIR)$(ARNOFW_PLUGIN_SCRIPT_DIR)/adaptive-ban-helper
	$(INSTALL) -m 0644 package/arnofw/adaptive-ban/adaptive-ban.conf \
		$(TARGET_DIR)/stat$(ARNOFW_PLUGIN_CONFIG_DIR)/adaptive-ban.conf
	@echo
	@echo "Install local version of DynDNS Host Open plugin."
	@echo
	$(INSTALL) -m 0644 package/arnofw/dyndns-host-open/50dyndns-host-open.plugin.sh \
		$(TARGET_DIR)$(ARNOFW_PLUGIN_SCRIPT_DIR)/50dyndns-host-open.plugin
	$(INSTALL) -m 0755 package/arnofw/dyndns-host-open/dyndns-host-open-helper.sh \
		$(TARGET_DIR)$(ARNOFW_PLUGIN_SCRIPT_DIR)/dyndns-host-open-helper
	$(INSTALL) -m 0644 package/arnofw/dyndns-host-open/dyndns-host-open.conf \
		$(TARGET_DIR)/stat$(ARNOFW_PLUGIN_CONFIG_DIR)/dyndns-host-open.conf
	@echo
	@echo "Install local version of Traffic Shaper plugin."
	@echo
	$(INSTALL) -m 0644 package/arnofw/traffic-shaper/60traffic-shaper.plugin.sh \
		$(TARGET_DIR)$(ARNOFW_PLUGIN_SCRIPT_DIR)/60traffic-shaper.plugin
	$(INSTALL) -m 0644 package/arnofw/traffic-shaper/traffic-shaper-astlinux.conf \
		$(TARGET_DIR)/stat$(ARNOFW_PLUGIN_CONFIG_DIR)/traffic-shaper.conf
	@echo
	@echo "Install local version of OpenVPN Server plugin."
	@echo
	$(INSTALL) -m 0644 package/arnofw/openvpn-server/50openvpn-server.plugin.sh \
		$(TARGET_DIR)$(ARNOFW_PLUGIN_SCRIPT_DIR)/50openvpn-server.plugin
	$(INSTALL) -m 0644 package/arnofw/openvpn-server/openvpn-server-astlinux.conf \
		$(TARGET_DIR)/stat$(ARNOFW_PLUGIN_CONFIG_DIR)/openvpn-server.conf
	@echo
	@echo "Install local version of Time Schedule Host Block plugin."
	@echo
	$(INSTALL) -m 0644 package/arnofw/time-schedule-host-block/30time-schedule-host-block.plugin.sh \
		$(TARGET_DIR)$(ARNOFW_PLUGIN_SCRIPT_DIR)/30time-schedule-host-block.plugin
	$(INSTALL) -m 0644 package/arnofw/time-schedule-host-block/time-schedule-host-block.conf \
		$(TARGET_DIR)/stat$(ARNOFW_PLUGIN_CONFIG_DIR)/time-schedule-host-block.conf
	@echo
	@echo "Install local version of SIP User-Agent plugin."
	@echo
	$(INSTALL) -m 0644 package/arnofw/sip-user-agent/30sip-user-agent.plugin.sh \
		$(TARGET_DIR)$(ARNOFW_PLUGIN_SCRIPT_DIR)/30sip-user-agent.plugin
	$(INSTALL) -m 0644 package/arnofw/sip-user-agent/sip-user-agent.conf \
		$(TARGET_DIR)/stat$(ARNOFW_PLUGIN_CONFIG_DIR)/sip-user-agent.conf
	@echo
	@echo "Install local version of PPTP VPN plugin."
	@echo
	$(INSTALL) -m 0644 package/arnofw/pptp-vpn/50pptp-vpn.plugin.sh \
		$(TARGET_DIR)$(ARNOFW_PLUGIN_SCRIPT_DIR)/50pptp-vpn.plugin
	$(INSTALL) -m 0644 package/arnofw/pptp-vpn/pptp-vpn-astlinux.conf \
		$(TARGET_DIR)/stat$(ARNOFW_PLUGIN_CONFIG_DIR)/pptp-vpn.conf
	@echo
	@echo "Install local version of miniupnpd plugin."
	@echo
	$(INSTALL) -m 0644 package/arnofw/miniupnpd/50miniupnpd.plugin.sh \
		$(TARGET_DIR)$(ARNOFW_PLUGIN_SCRIPT_DIR)/50miniupnpd.plugin
	$(INSTALL) -m 0644 package/arnofw/miniupnpd/miniupnpd-astlinux.conf \
		$(TARGET_DIR)/stat$(ARNOFW_PLUGIN_CONFIG_DIR)/miniupnpd.conf

arnofw: $(TARGET_DIR)$(ARNOFW_TARGET_BINARY)

arnofw-clean:
	rm -f $(TARGET_DIR)$(ARNOFW_TARGET_BINARY)
	rm -rf $(TARGET_DIR)$(ARNOFW_CONFIG_DIR) \
		$(TARGET_DIR)/$(ARNOFW_SCRIPT_DIR) \
		$(TARGET_DIR)/stat$(ARNOFW_CONFIG_DIR)
	rm -f $(TARGET_DIR)/usr/sbin/reload-spamhaus-drop

arnofw-dirclean:
	rm -rf $(ARNOFW_DIR)

arnofw-source: $(ARNOFW_DIR)/.patched

#############################################################
#
# Toplevel Makefile options
#
#############################################################
ifeq ($(strip $(BR2_PACKAGE_ARNOFW)),y)
TARGETS+=arnofw
endif
