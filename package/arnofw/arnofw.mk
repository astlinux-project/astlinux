#############################################################
#
# arnofw
#
#############################################################

# source included in package
ARNOFW_SOURCE =

ARNOFW_CONFIG_DIR = etc/arno-iptables-firewall
ARNOFW_SCRIPT_DIR = usr/share/arno-iptables-firewall
ARNOFW_AIFSRC_DIR = package/arnofw/aif

define ARNOFW_BUILD_CMDS
    # No build needed
endef

define ARNOFW_INSTALL_TARGET_CMDS
	ln -sf /tmp/$(ARNOFW_CONFIG_DIR) $(TARGET_DIR)/$(ARNOFW_CONFIG_DIR)
	## Install main script
	$(INSTALL) -D -m 0755 $(ARNOFW_AIFSRC_DIR)/bin/arno-iptables-firewall $(TARGET_DIR)/usr/sbin/arno-iptables-firewall
	## Install plugin configs
	mkdir -p $(TARGET_DIR)/stat/$(ARNOFW_CONFIG_DIR)/plugins
	$(INSTALL) -m 0644 $(ARNOFW_AIFSRC_DIR)/etc/arno-iptables-firewall/plugins/*.conf $(TARGET_DIR)/stat/$(ARNOFW_CONFIG_DIR)/plugins
	## Install firewall.conf and supporting files
	$(INSTALL) -m 0644 $(ARNOFW_AIFSRC_DIR)/etc/arno-iptables-firewall/firewall.conf $(TARGET_DIR)/stat/$(ARNOFW_CONFIG_DIR)
	$(INSTALL) -m 0644 $(ARNOFW_AIFSRC_DIR)/etc/arno-iptables-firewall/custom-rules $(TARGET_DIR)/stat/$(ARNOFW_CONFIG_DIR)
	$(INSTALL) -m 0444 package/arnofw/arnofw.serial $(TARGET_DIR)/stat/$(ARNOFW_CONFIG_DIR)/serial
	## Install plugin scripts
	mkdir -p $(TARGET_DIR)/$(ARNOFW_SCRIPT_DIR)/plugins
	$(INSTALL) -m 0644 $(ARNOFW_AIFSRC_DIR)/share/arno-iptables-firewall/plugins/*.plugin $(TARGET_DIR)/$(ARNOFW_SCRIPT_DIR)/plugins
	$(INSTALL) -m 0755 $(ARNOFW_AIFSRC_DIR)/share/arno-iptables-firewall/plugins/*-helper $(TARGET_DIR)/$(ARNOFW_SCRIPT_DIR)/plugins
	## Install environment script
	$(INSTALL) -m 0644 $(ARNOFW_AIFSRC_DIR)/share/arno-iptables-firewall/environment $(TARGET_DIR)/$(ARNOFW_SCRIPT_DIR)
	##
	$(INSTALL) -m 0444 package/arnofw/arnofw.wrapper $(TARGET_DIR)/$(ARNOFW_SCRIPT_DIR)/astlinux.shim
	$(INSTALL) -D -m 0755 package/arnofw/reload-spamhaus-drop $(TARGET_DIR)/usr/sbin/reload-spamhaus-drop
	$(INSTALL) -D -m 0755 package/arnofw/reload-blocklist-netset $(TARGET_DIR)/usr/sbin/reload-blocklist-netset
	$(INSTALL) -D -m 0755 package/arnofw/apiban-netset $(TARGET_DIR)/usr/sbin/apiban-netset
endef

define ARNOFW_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/arno-iptables-firewall
	rm -f $(TARGET_DIR)/$(ARNOFW_CONFIG_DIR)
	rm -rf $(TARGET_DIR)/$(ARNOFW_SCRIPT_DIR)
	rm -rf $(TARGET_DIR)/stat/$(ARNOFW_CONFIG_DIR)
	rm -f $(TARGET_DIR)/usr/sbin/reload-spamhaus-drop
	rm -f $(TARGET_DIR)/usr/sbin/reload-blocklist-netset
	rm -f $(TARGET_DIR)/usr/sbin/apiban-netset
endef

$(eval $(call GENTARGETS,package,arnofw))
