#############################################################
#
# libretls
#
#############################################################

LIBRETLS_VERSION = 3.4.1
LIBRETLS_SITE = https://causal.agency/libretls
LIBRETLS_INSTALL_STAGING = YES

LIBRETLS_DEPENDENCIES = host-pkg-config openssl

LIBRETLS_CONF_OPT = \
	--with-openssl=$(STAGING_DIR)/usr \

$(eval $(call AUTOTARGETS,package,libretls))
