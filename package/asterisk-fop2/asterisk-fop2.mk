#############################################################
#
# asterisk-fop2
#
#############################################################
ASTERISK_FOP2_VERSION = 2.27
ASTERISK_FOP2_SOURCE = fop2-$(ASTERISK_FOP2_VERSION)-debian-i386.tgz
ASTERISK_FOP2_SITE = http://download.fop2.com
# Note: be sure to edit "project/astlinux/target_skeleton/usr/sbin/upgrade-package" on version change

define ASTERISK_FOP2_INSTALL_TARGET_CMDS
	$(INSTALL) -D -m 0755 package/asterisk-fop2/fop2.init $(TARGET_DIR)/etc/init.d/fop2
	$(INSTALL) -D -m 0644 package/asterisk-fop2/config/fop2.cfg $(TARGET_DIR)/stat/etc/fop2/fop2.cfg
	$(INSTALL) -D -m 0644 package/asterisk-fop2/config/buttons.cfg $(TARGET_DIR)/stat/etc/fop2/buttons.cfg
	ln -snf /tmp/etc/fop2 $(TARGET_DIR)/etc/fop2
	ln -sf ../../init.d/fop2 $(TARGET_DIR)/etc/runlevels/default/S98fop2
	ln -sf ../../init.d/fop2 $(TARGET_DIR)/etc/runlevels/default/K00fop2
endef

define ASTERISK_FOP2_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/etc/init.d/fop2
	rm -rf $(TARGET_DIR)/stat/etc/fop2
	rm -f $(TARGET_DIR)/etc/fop2
	rm -f $(TARGET_DIR)/etc/runlevels/default/S98fop2
	rm -f $(TARGET_DIR)/etc/runlevels/default/K00fop2
endef

$(eval $(call GENTARGETS,package,asterisk-fop2))
