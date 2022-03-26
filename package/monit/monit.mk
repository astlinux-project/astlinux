################################################################################
#
# monit
#
################################################################################

MONIT_VERSION = 5.32.0
MONIT_SITE = https://mmonit.com/monit/dist
MONIT_DEPENDENCIES = host-bison host-flex
#
# Touching Makefile.am and configure.ac:
#MONIT_AUTORECONF = YES
define MONIT_BOOTSTRAP_AFTER_PATCH
	(cd $(@D); \
		$(HOST_CONFIGURE_OPTS) \
		./bootstrap \
	)
endef
MONIT_POST_PATCH_HOOKS += MONIT_BOOTSTRAP_AFTER_PATCH

MONIT_CONF_ENV = \
	ac_cv_ipv6=yes \
	libmonit_cv_setjmp_available=yes \
	libmonit_cv_vsnprintf_c99_conformant=yes

MONIT_CONF_OPT += \
	--sysconfdir=/etc/monit \
	--without-pam

ifeq ($(BR2_PACKAGE_OPENSSL),y)
MONIT_CONF_OPT += --with-ssl-dir=$(STAGING_DIR)/usr
MONIT_DEPENDENCIES += openssl
else
MONIT_CONF_OPT += --without-ssl
endif

ifeq ($(BR2_LARGEFILE),y)
MONIT_CONF_OPT += --with-largefiles
else
MONIT_CONF_OPT += --without-largefiles
endif

define MONIT_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/monit $(TARGET_DIR)/usr/sbin/monit
	$(INSTALL) -m 0600 -D package/monit/monit.services $(TARGET_DIR)/stat/etc/monit/monit.d/services.conf
	$(INSTALL) -m 0755 -D package/monit/monit.init $(TARGET_DIR)/etc/init.d/monit
	ln -sf /tmp/etc/monit $(TARGET_DIR)/etc/monit
	ln -sf ../../init.d/monit $(TARGET_DIR)/etc/runlevels/default/S98monit
	ln -sf ../../init.d/monit $(TARGET_DIR)/etc/runlevels/default/K01monit
endef

define MONIT_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/monit
	rm -rf $(TARGET_DIR)/stat/etc/monit
	rm -f $(TARGET_DIR)/etc/init.d/monit
	rm -f $(TARGET_DIR)/etc/monit
	rm -f $(TARGET_DIR)/etc/runlevels/default/S98monit
	rm -f $(TARGET_DIR)/etc/runlevels/default/K01monit
endef

$(eval $(call AUTOTARGETS,package,monit))
