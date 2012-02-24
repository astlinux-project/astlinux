#############################################################
#
# PCRE
#
#############################################################

PCRE_VERSION = 8.21
PCRE_SITE = http://$(BR2_SOURCEFORGE_MIRROR).dl.sourceforge.net/project/pcre/pcre/$(PCRE_VERSION)
PCRE_INSTALL_STAGING = YES

ifneq ($(BR2_INSTALL_LIBSTDCPP),y)
# pcre will use the host g++ if a cross version isn't available
PCRE_CONF_OPT = --disable-cpp
endif

define PCRE_STAGING_PCRE_CONFIG_FIXUP
	$(SED) 's,^prefix=.*,prefix=$(STAGING_DIR)/usr,' \
		-e 's,^exec_prefix=.*,exec_prefix=$(STAGING_DIR)/usr,' \
		$(STAGING_DIR)/usr/bin/pcre-config
endef

PCRE_POST_INSTALL_STAGING_HOOKS += PCRE_STAGING_PCRE_CONFIG_FIXUP

define PCRE_TARGET_REMOVE_PCRE_CONFIG
	rm -f $(TARGET_DIR)/usr/bin/pcre-config
endef

ifneq ($(BR2_HAVE_DEVFILES),y)
PCRE_POST_INSTALL_TARGET_HOOKS += PCRE_TARGET_REMOVE_PCRE_CONFIG
endif

$(eval $(call AUTOTARGETS,package,pcre))
