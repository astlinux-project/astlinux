#############################################################
#
# smartmontools
#
#############################################################

SMARTMONTOOLS_VERSION = 7.4
SMARTMONTOOLS_SITE = https://downloads.sourceforge.net/project/smartmontools/smartmontools/$(SMARTMONTOOLS_VERSION)

define SMARTMONTOOLS_LATEST_DRIVEDB
	# Upstream Drive DB:
	# curl -o package/smartmontools/drivedb.h 'https://sourceforge.net/p/smartmontools/code/HEAD/tree/trunk/smartmontools/drivedb.h?format=raw'
	# Install local snapshot
	cp package/smartmontools/drivedb.h $(@D)/drivedb.h
endef
SMARTMONTOOLS_POST_EXTRACT_HOOKS += SMARTMONTOOLS_LATEST_DRIVEDB

SMARTMONTOOLS_CONF_OPT = \
	--without-gnupg \
	--without-libcap-ng \
	--without-nvme-devicescan

define SMARTMONTOOLS_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/smartctl $(TARGET_DIR)/usr/sbin/smartctl
	$(INSTALL) -m 0755 -D package/smartmontools/smart-status.sh $(TARGET_DIR)/usr/sbin/smart-status
	ln -sf /tmp/etc/smart_drivedb.h $(TARGET_DIR)/etc/smart_drivedb.h
endef

define SMARTMONTOOLS_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/smartctl
	rm -f $(TARGET_DIR)/usr/sbin/smart-status
	rm -f $(TARGET_DIR)/etc/smart_drivedb.h
endef

$(eval $(call AUTOTARGETS,package,smartmontools))
