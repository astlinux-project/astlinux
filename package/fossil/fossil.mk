################################################################################
#
# fossil
#
################################################################################

FOSSIL_VERSION = 1.33
FOSSIL_SOURCE = fossil-src-$(FOSSIL_VERSION).tar.gz
FOSSIL_SITE = http://www.fossil-scm.org/download
FOSSIL_DEPENDENCIES = zlib openssl

define FOSSIL_CONFIGURE_CMDS
	# this is *NOT* GNU autoconf stuff
        (cd $(@D); \
		$(TARGET_CONFIGURE_OPTS) \
                ./configure \
		--prefix=/usr \
		--host=$(GNU_TARGET_NAME) \
		--build=$(GNU_HOST_NAME) \
		--with-openssl="$(STAGING_DIR)/usr" \
		--with-zlib="$(STAGING_DIR)/usr" \
        )
endef

FOSSIL_MAKE_ENV = \
	TCC="$(TARGET_CC)"

FOSSIL_UNINSTALL_STAGING_OPT = --version

define FOSSIL_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/fossil $(TARGET_DIR)/usr/bin/fossil
	$(INSTALL) -m 0755 -D package/fossil/scripts/fossil-open $(TARGET_DIR)/usr/bin/
	$(INSTALL) -m 0755 -D package/fossil/scripts/fossil-close $(TARGET_DIR)/usr/bin/
	$(INSTALL) -m 0755 -D package/fossil/scripts/fossil-update $(TARGET_DIR)/usr/bin/
	$(INSTALL) -m 0755 -D package/fossil/scripts/fossil-revert $(TARGET_DIR)/usr/bin/
	$(INSTALL) -m 0755 -D package/fossil/fossil.init $(TARGET_DIR)/etc/init.d/fossil
	ln -sf ../../init.d/fossil $(TARGET_DIR)/etc/runlevels/default/S75fossil
	ln -sf ../../init.d/fossil $(TARGET_DIR)/etc/runlevels/default/K15fossil
endef

define FOSSIL_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/fossil
	rm -f $(TARGET_DIR)/usr/bin/fossil-open
	rm -f $(TARGET_DIR)/usr/bin/fossil-close
	rm -f $(TARGET_DIR)/usr/bin/fossil-update
	rm -f $(TARGET_DIR)/usr/bin/fossil-revert
	rm -f $(TARGET_DIR)/etc/init.d/fossil
	rm -f $(TARGET_DIR)/etc/runlevels/default/S75fossil
	rm -f $(TARGET_DIR)/etc/runlevels/default/K15fossil
endef

$(eval $(call AUTOTARGETS,package,fossil))
