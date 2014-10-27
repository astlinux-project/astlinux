#############################################################
#
# smartmontools
#
#############################################################

SMARTMONTOOLS_VERSION = 6.3
SMARTMONTOOLS_SITE = http://downloads.sourceforge.net/project/smartmontools/smartmontools/$(SMARTMONTOOLS_VERSION)

define SMARTMONTOOLS_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/smartctl $(TARGET_DIR)/usr/sbin/smartctl
	ln -sf /tmp/etc/smart_drivedb.h $(TARGET_DIR)/etc/smart_drivedb.h
endef

define SMARTMONTOOLS_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/smartctl
	rm -f $(TARGET_DIR)/etc/smart_drivedb.h
endef

$(eval $(call AUTOTARGETS,package,smartmontools))
