#############################################################
#
# pkg-config
#
#############################################################

PKG_CONFIG_VERSION = 0.9.12
PKG_CONFIG_SOURCE = pkgconf-$(PKG_CONFIG_VERSION).tar.bz2
PKG_CONFIG_SITE = https://github.com/pkgconf/pkgconf/releases/download/pkgconf-$(PKG_CONFIG_VERSION)

define HOST_PKG_CONFIG_INSTALL_WRAPPER
	$(INSTALL) -m 0755 -D package/pkg-config/pkg-config.in \
		$(HOST_DIR)/usr/bin/pkg-config
	$(SED) 's,@PKG_CONFIG_LIBDIR@,$(STAGING_DIR)/usr/lib/pkgconfig:$(STAGING_DIR)/usr/share/pkgconfig,' \
		-e 's,@STAGING_DIR@,$(STAGING_DIR),' \
		$(HOST_DIR)/usr/bin/pkg-config
endef

define HOST_PKG_CONFIG_STATIC
	$(SED) 's,@STATIC@,--static,' $(HOST_DIR)/usr/bin/pkg-config
endef

define HOST_PKG_CONFIG_SHARED
	$(SED) 's,@STATIC@,,' $(HOST_DIR)/usr/bin/pkg-config
endef

HOST_PKG_CONFIG_POST_INSTALL_HOOKS += HOST_PKG_CONFIG_INSTALL_WRAPPER

ifeq ($(BR2_PREFER_STATIC_LIB),y)
HOST_PKG_CONFIG_POST_INSTALL_HOOKS += HOST_PKG_CONFIG_STATIC
else
HOST_PKG_CONFIG_POST_INSTALL_HOOKS += HOST_PKG_CONFIG_SHARED
endif

$(eval $(call AUTOTARGETS,package,pkg-config,host))

PKG_CONFIG_HOST_BINARY = $(HOST_DIR)/usr/bin/pkg-config
