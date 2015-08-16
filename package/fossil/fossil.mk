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
	$(TARGET_MAKE_ENV) \
	TCC="$(TARGET_CC)"

FOSSIL_UNINSTALL_STAGING_OPT = --version

define FOSSIL_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/fossil $(TARGET_DIR)/usr/bin/fossil
endef

define FOSSIL_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/fossil
endef

$(eval $(call AUTOTARGETS,package,fossil))
