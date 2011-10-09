#############################################################
#
# inadyn
#
#############################################################

INADYN_VERSION = 1.96.2
INADYN_SOURCE:=inadyn-$(INADYN_VERSION).tar.gz
INADYN_SITE = http://files.astlinux.org

INADYN_UNINSTALL_STAGING_OPT = --version

define INADYN_CONFIGURE_CMDS
	@echo "No configure"
endef

define INADYN_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/bin/linux/inadyn $(TARGET_DIR)/usr/sbin/inadyn
	$(INSTALL) -m 0755 -D package/inadyn/inadyn.init $(TARGET_DIR)/etc/init.d/dynamicdns
	ln -sf /tmp/etc/inadyn.conf $(TARGET_DIR)/etc/inadyn.conf
endef

define INADYN_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/inadyn
	rm -f $(TARGET_DIR)/etc/init.d/dynamicdns
endef

$(eval $(call AUTOTARGETS,package,inadyn))
