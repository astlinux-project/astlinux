#############################################################
#
# libretls
#
#############################################################

LIBRETLS_VERSION = 3.3.3
LIBRETLS_SITE = https://causal.agency/libretls
LIBRETLS_INSTALL_STAGING = YES

LIBRETLS_DEPENDENCIES = host-pkg-config openssl

define LIBRETLS_FIXUP_SCRIPTS
	$(INSTALL) -m 0755 -D package/libretls/scripts/wrap-compiler-for-flag-check $(@D)/scripts/wrap-compiler-for-flag-check
endef
LIBRETLS_POST_EXTRACT_HOOKS = LIBRETLS_FIXUP_SCRIPTS

LIBRETLS_CONF_OPT = \
	--with-openssl=$(STAGING_DIR)/usr \

$(eval $(call AUTOTARGETS,package,libretls))
