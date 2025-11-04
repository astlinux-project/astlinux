#############################################################
#
# PCRE2
#
#############################################################

PCRE2_VERSION = 10.47
PCRE2_SITE = https://github.com/PCRE2Project/pcre2/releases/download/pcre2-$(PCRE2_VERSION)
PCRE2_SOURCE = pcre2-$(PCRE2_VERSION).tar.bz2
PCRE2_INSTALL_STAGING = YES

PCRE2_CONF_OPT = \
	--enable-pcre2-8 \
	--disable-pcre2-16 \
	--disable-pcre2-32 \
	--disable-unicode \
	--disable-jit

define PCRE2_STAGING_PCRE2_CONFIG_FIXUP
	$(SED) 's,^prefix=.*,prefix=$(STAGING_DIR)/usr,' \
		-e 's,^exec_prefix=.*,exec_prefix=$(STAGING_DIR)/usr,' \
		$(STAGING_DIR)/usr/bin/pcre2-config
endef
PCRE2_POST_INSTALL_STAGING_HOOKS += PCRE2_STAGING_PCRE2_CONFIG_FIXUP

define PCRE2_TARGET_REMOVE_PCRE2_CONFIG
	rm -f $(TARGET_DIR)/usr/bin/pcre2-config
	rm -f $(TARGET_DIR)/usr/bin/pcre2test
endef
PCRE2_POST_INSTALL_TARGET_HOOKS += PCRE2_TARGET_REMOVE_PCRE2_CONFIG

$(eval $(call AUTOTARGETS,package,pcre2))
