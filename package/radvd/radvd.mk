#############################################################
#
# radvd
#
#############################################################
RADVD_VERSION = 2.8
RADVD_SOURCE = radvd-$(RADVD_VERSION).tar.gz
RADVD_SITE = http://www.litech.org/radvd/dist/
RADVD_DEPENDENCIES = host-bison flex host-flex host-pkg-config
# For radvd-drop-check.patch
RADVD_AUTORECONF = YES

define RADVD_INSTALL_INITSCRIPT
	$(INSTALL) -D -m 0755 package/radvd/radvd.init $(TARGET_DIR)/etc/init.d/radvd
	ln -sf /tmp/etc/radvd.conf $(TARGET_DIR)/etc/radvd.conf
	ln -sf ../../init.d/radvd $(TARGET_DIR)/etc/runlevels/default/S92radvd
	ln -sf ../../init.d/radvd $(TARGET_DIR)/etc/runlevels/default/K05radvd
endef

RADVD_POST_INSTALL_TARGET_HOOKS += RADVD_INSTALL_INITSCRIPT

define RADVD_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/etc/init.d/radvd
	rm -f $(TARGET_DIR)/etc/radvd.conf
	rm -f $(TARGET_DIR)/etc/runlevels/default/S92radvd
	rm -f $(TARGET_DIR)/etc/runlevels/default/K05radvd
endef

$(eval $(call AUTOTARGETS,package,radvd))
