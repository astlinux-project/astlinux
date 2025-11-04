#############################################################
#
# PCRE
#
#############################################################

PCRE_VERSION = 8.45
PCRE_SITE = https://downloads.sourceforge.net/project/pcre/pcre/$(PCRE_VERSION)
PCRE_SOURCE = pcre-$(PCRE_VERSION).tar.bz2
PCRE_INSTALL_STAGING = YES

ifneq ($(BR2_INSTALL_LIBSTDCPP),y)
# pcre will use the host g++ if a cross version isn't available
PCRE_CONF_OPT = --disable-cpp
endif

PCRE_CONF_OPT += --enable-pcre8
PCRE_CONF_OPT += --disable-pcre16
PCRE_CONF_OPT += --disable-pcre32
PCRE_CONF_OPT += --disable-utf
PCRE_CONF_OPT += --disable-unicode-properties

define PCRE_STAGING_PCRE_CONFIG_FIXUP
	$(SED) 's,^prefix=.*,prefix=$(STAGING_DIR)/usr,' \
		-e 's,^exec_prefix=.*,exec_prefix=$(STAGING_DIR)/usr,' \
		$(STAGING_DIR)/usr/bin/pcre-config
endef
PCRE_POST_INSTALL_STAGING_HOOKS += PCRE_STAGING_PCRE_CONFIG_FIXUP

define PCRE_TARGET_REMOVE_PCRE_CONFIG
	rm -f $(TARGET_DIR)/usr/bin/pcre-config
	rm -f $(TARGET_DIR)/usr/bin/pcretest
endef
PCRE_POST_INSTALL_TARGET_HOOKS += PCRE_TARGET_REMOVE_PCRE_CONFIG

$(eval $(call AUTOTARGETS,package,pcre))
