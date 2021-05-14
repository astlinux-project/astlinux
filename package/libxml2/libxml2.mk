#############################################################
#
# libxml2
#
#############################################################

LIBXML2_VERSION = 2.9.12
LIBXML2_SITE = http://xmlsoft.org/sources
LIBXML2_INSTALL_STAGING = YES

ifneq ($(BR2_LARGEFILE),y)
LIBXML2_CONF_ENV = CC="$(TARGET_CC) $(TARGET_CFLAGS) -DNO_LARGEFILE_SOURCE"
endif

LIBXML2_CONF_OPT = --with-gnu-ld --without-python --without-debug --without-lzma

define LIBXML2_STAGING_LIBXML2_CONFIG_FIXUP
	$(SED) "s,^prefix=.*,prefix=\'$(STAGING_DIR)/usr\',g" $(STAGING_DIR)/usr/bin/xml2-config
	$(SED) "s,^exec_prefix=.*,exec_prefix=\'$(STAGING_DIR)/usr\',g" $(STAGING_DIR)/usr/bin/xml2-config
endef
LIBXML2_POST_INSTALL_STAGING_HOOKS += LIBXML2_STAGING_LIBXML2_CONFIG_FIXUP

HOST_LIBXML2_DEPENDENCIES = host-pkg-config
LIBXML2_DEPENDENCIES = host-pkg-config

HOST_LIBXML2_CONF_OPT = --without-zlib --without-lzma --without-debug --without-python

ifneq ($(BR2_HAVE_DEVFILES),y)
define LIBXML2_REMOVE_CONFIG_SCRIPTS
	rm -f $(TARGET_DIR)/usr/bin/xml2-config
	rm -f $(TARGET_DIR)/usr/lib/xml2Conf.sh
endef
LIBXML2_POST_INSTALL_TARGET_HOOKS += LIBXML2_REMOVE_CONFIG_SCRIPTS
endif

ifeq ($(BR2_PACKAGE_ZLIB),y)
LIBXML2_DEPENDENCIES += zlib
LIBXML2_CONF_OPT += --with-zlib=$(STAGING_DIR)/usr
else
LIBXML2_CONF_OPT += --without-zlib
endif

LIBXML2_DEPENDENCIES += $(if $(BR2_PACKAGE_LIBICONV),libiconv)

ifeq ($(BR2_ENABLE_LOCALE)$(BR2_PACKAGE_LIBICONV),y)
LIBXML2_CONF_OPT += --with-iconv
else
LIBXML2_CONF_OPT += --without-iconv
endif

$(eval $(call AUTOTARGETS,package,libxml2))
$(eval $(call AUTOTARGETS,package,libxml2,host))

# libxml2 for the host
LIBXML2_HOST_BINARY = $(HOST_DIR)/usr/bin/xmllint
