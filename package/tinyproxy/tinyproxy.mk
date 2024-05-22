#############################################################
#
# tinyproxy
#
#############################################################

TINYPROXY_VERSION = 1.11.2
TINYPROXY_SITE = https://github.com/tinyproxy/tinyproxy/releases/download/$(TINYPROXY_VERSION)
TINYPROXY_SOURCE = tinyproxy-$(TINYPROXY_VERSION).tar.xz

define TINYPROXY_POST_INSTALL
	$(INSTALL) -D -m 0644 $(TARGET_DIR)/etc/tinyproxy/tinyproxy.conf $(TARGET_DIR)/stat/etc/tinyproxy.conf
	rm -rf $(TARGET_DIR)/etc/tinyproxy
	$(INSTALL) -D -m 0755 package/tinyproxy/tinyproxy.init $(TARGET_DIR)/etc/init.d/tinyproxy
	ln -sf /tmp/etc/tinyproxy.conf $(TARGET_DIR)/etc/tinyproxy.conf
	ln -sf ../../init.d/tinyproxy $(TARGET_DIR)/etc/runlevels/default/S33tinyproxy
	ln -sf ../../init.d/tinyproxy $(TARGET_DIR)/etc/runlevels/default/K25tinyproxy	
endef

TINYPROXY_POST_INSTALL_TARGET_HOOKS = TINYPROXY_POST_INSTALL

define TINYPROXY_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/tinyproxy
	rm -rf $(TARGET_DIR)/usr/share/tinyproxy
	rm -f $(TARGET_DIR)/stat/etc/tinyproxy.conf
	rm -f $(TARGET_DIR)/etc/init.d/tinyproxy
	rm -f $(TARGET_DIR)/etc/tinyproxy.conf
	rm -f $(TARGET_DIR)/etc/runlevels/default/S33tinyproxy
	rm -f $(TARGET_DIR)/etc/runlevels/default/K25tinyproxy	
endef

define TINYPROXY_UNINSTALL_STAGING_CMDS
        @echo "Skip Staging Uninstall..."
endef

$(eval $(call AUTOTARGETS,package,tinyproxy))
