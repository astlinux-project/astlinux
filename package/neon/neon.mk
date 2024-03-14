#############################################################
#
# neon
#
#############################################################

NEON_VERSION = 0.33.0
NEON_SITE = https://notroj.github.io/neon
NEON_INSTALL_STAGING = YES
NEON_CONF_OPT = --without-gssapi

NEON_DEPENDENCIES = host-pkg-config

ifeq ($(BR2_PACKAGE_NEON_ZLIB),y)
NEON_CONF_OPT += --with-zlib=yes
NEON_DEPENDENCIES += zlib
else
NEON_CONF_OPT += --without-zlib
endif

ifeq ($(BR2_PACKAGE_NEON_SSL),y)
NEON_CONF_OPT += --with-ssl
NEON_DEPENDENCIES += openssl
else
NEON_CONF_OPT += --without-ssl
endif

ifeq ($(BR2_PACKAGE_NEON_EXPAT),y)
NEON_CONF_OPT += --with-expat=$(STAGING_DIR)/usr/lib/libexpat.la
NEON_DEPENDENCIES += expat
else
NEON_CONF_OPT += --with-expat=no
endif

ifeq ($(BR2_PACKAGE_NEON_LIBXML2),y)
NEON_CONF_OPT += --with-libxml2=yes
NEON_CONF_ENV += ac_cv_prog_XML2_CONFIG=$(STAGING_DIR)/usr/bin/xml2-config
NEON_DEPENDENCIES += libxml2
else
NEON_CONF_OPT += --with-libxml2=no
endif

ifeq ($(BR2_PACKAGE_NEON_NOXML),y)
# webdav needs xml support
NEON_CONF_OPT += --disable-webdav
endif

ifneq ($(BR2_HAVE_DEVFILES),y)
define NEON_REMOVE_CONFIG_SCRIPTS
	rm -f $(TARGET_DIR)/usr/bin/neon-config
endef
NEON_POST_INSTALL_TARGET_HOOKS += NEON_REMOVE_CONFIG_SCRIPTS
endif

define NEON_STAGING_NEON_CONFIG_FIXUP
	$(SED) "s,^prefix=.*,prefix=\'$(STAGING_DIR)/usr\',g" \
		-e "s,^exec_prefix=.*,exec_prefix=\'$(STAGING_DIR)/usr\',g" \
		$(STAGING_DIR)/usr/bin/neon-config
endef
NEON_POST_INSTALL_STAGING_HOOKS += NEON_STAGING_NEON_CONFIG_FIXUP

$(eval $(call AUTOTARGETS,package,neon))
