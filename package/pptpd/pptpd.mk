#############################################################
#
# pptpd
#
#############################################################

PPTPD_VERSION = 1.3.4
PPTPD_SITE = http://$(SOURCEFORGE_MIRROR).dl.sourceforge.net/sourceforge/poptop/

PPTPD_CONF_OPT += \
	--sysconfdir=/etc

define PPTPD_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/bcrelay $(TARGET_DIR)/usr/sbin/bcrelay
	$(INSTALL) -m 0755 -D $(@D)/pptpctrl $(TARGET_DIR)/usr/sbin/pptpctrl
	$(INSTALL) -m 0755 -D $(@D)/pptpd $(TARGET_DIR)/usr/sbin/pptpd
	$(INSTALL) -m 0755 -D package/pptpd/pptpd.init $(TARGET_DIR)/etc/init.d/pptpd
	ln -sf /tmp/etc/pptpd.conf $(TARGET_DIR)/etc/pptpd.conf
endef

define PPTPD_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/bcrelay
	rm -f $(TARGET_DIR)/usr/sbin/pptpctrl
	rm -f $(TARGET_DIR)/usr/sbin/pptpd
	rm -f $(TARGET_DIR)/etc/init.d/pptpd
endef

$(eval $(call AUTOTARGETS,package,pptpd))
