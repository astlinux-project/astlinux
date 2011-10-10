#############################################################
#
# linux-atm
#
#############################################################
LINUX_ATM_VERSION = 2.5.2
LINUX_ATM_SITE = http://$(BR2_SOURCEFORGE_MIRROR).dl.sourceforge.net/project/linux-atm/linux-atm/$(LINUX_ATM_VERSION)
LINUX_ATM_SOURCE = linux-atm-$(LINUX_ATM_VERSION).tar.gz
LINUX_ATM_INSTALL_STAGING = YES
LINUX_ATM_INSTALL_TARGET = YES
LINUX_ATM_CONF_OPT = \
	--program-transform-name=''

define LINUX_ATM_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/br2684ctl
	rm -f $(TARGET_DIR)/usr/sbin/atmsigd
endef

$(eval $(call AUTOTARGETS,package,linux-atm))
