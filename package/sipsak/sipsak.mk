#############################################################
#
# sipsak
#
#############################################################

SIPSAK_VERSION = 0.9.7
SIPSAK_SOURCE = sipsak-$(SIPSAK_VERSION).tar.gz
SIPSAK_SITE = https://github.com/nils-ohlmeier/sipsak/releases/download/$(SIPSAK_VERSION)
SIPSAK_DEPENDENCIES = openssl

# Generate a modern ./configure
SIPSAK_AUTORECONF = YES

SIPSAK_CONF_OPT += \
	--disable-gnutls \
	--enable-timeout=150

define SIPSAK_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/sipsak $(TARGET_DIR)/usr/bin/sipsak
endef

define SIPSAK_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/sipsak
endef

$(eval $(call AUTOTARGETS,package,sipsak))
