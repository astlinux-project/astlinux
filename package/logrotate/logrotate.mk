#############################################################
#
# logrotate
#
#############################################################

LOGROTATE_VERSION = 3.20.1
LOGROTATE_SOURCE = logrotate-$(LOGROTATE_VERSION).tar.gz
LOGROTATE_SITE = https://github.com/logrotate/logrotate/releases/download/$(LOGROTATE_VERSION)

LOGROTATE_DEPENDENCIES = popt host-pkg-config

LOGROTATE_CONF_OPT = --without-selinux

ifeq ($(BR2_PACKAGE_ACL),y)
LOGROTATE_DEPENDENCIES += acl
LOGROTATE_CONF_OPT += --with-acl
else
LOGROTATE_CONF_OPT += --without-acl
endif

define LOGROTATE_INSTALL_TARGET_CONF
	$(INSTALL) -m 0644 package/logrotate/logrotate.conf $(TARGET_DIR)/etc/logrotate.conf
	if [ ! -d $(TARGET_DIR)/etc/logrotate.d ]; then \
		$(INSTALL) -d -m 0755 $(TARGET_DIR)/etc/logrotate.d; \
	fi
endef
LOGROTATE_POST_INSTALL_TARGET_HOOKS += LOGROTATE_INSTALL_TARGET_CONF

define LOGROTATE_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/logrotate
	rm -f $(TARGET_DIR)/etc/logrotate.conf
endef

$(eval $(call AUTOTARGETS,package,logrotate))
