#############################################################
#
# libpng
#
#############################################################

LIBPNG_VERSION = 1.6.44
LIBPNG_SERIES = 16
LIBPNG_SOURCE = libpng-$(LIBPNG_VERSION).tar.xz
LIBPNG_SITE = http://downloads.sourceforge.net/project/libpng/libpng$(LIBPNG_SERIES)/$(LIBPNG_VERSION)
LIBPNG_INSTALL_STAGING = YES
LIBPNG_DEPENDENCIES = host-pkg-config zlib

HOST_LIBPNG_DEPENDENCIES = host-pkg-config host-zlib

LIBPNG_CONF_OPT = \
	--disable-tests \
	--disable-tools \
	--disable-hardware-optimizations

define LIBPNG_STAGING_LIBPNG_SERIES_CONFIG_FIXUP
	$(SED) "s,^prefix=.*,prefix=\'$(STAGING_DIR)/usr\',g" \
		-e "s,^exec_prefix=.*,exec_prefix=\'$(STAGING_DIR)/usr\',g" \
		-e "s,^includedir=.*,includedir=\'$(STAGING_DIR)/usr/include/libpng$(LIBPNG_SERIES)\',g" \
		-e "s,^libdir=.*,libdir=\'$(STAGING_DIR)/usr/lib\',g" \
		$(STAGING_DIR)/usr/bin/libpng$(LIBPNG_SERIES)-config
endef
LIBPNG_POST_INSTALL_STAGING_HOOKS += LIBPNG_STAGING_LIBPNG_SERIES_CONFIG_FIXUP

define LIBPNG_REMOVE_CONFIG_SCRIPTS
	$(RM) $(TARGET_DIR)/usr/bin/libpng$(LIBPNG_SERIES)-config \
		 $(TARGET_DIR)/usr/bin/libpng-config
endef
LIBPNG_POST_INSTALL_TARGET_HOOKS += LIBPNG_REMOVE_CONFIG_SCRIPTS

$(eval $(call AUTOTARGETS,package,libpng))
$(eval $(call AUTOTARGETS,package,libpng,host))
