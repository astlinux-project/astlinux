################################################################################
#
# libressl
#
################################################################################

LIBRESSL_VERSION = 3.2.2
LIBRESSL_SITE = https://ftp.openbsd.org/pub/OpenBSD/LibreSSL
LIBRESSL_INSTALL_STAGING = YES

ifeq ($(BR2_PACKAGE_LIBRESSL_BIN),)
define LIBRESSL_REMOVE_BIN
	$(RM) -f $(TARGET_DIR)/usr/bin/openssl
endef
LIBRESSL_POST_INSTALL_TARGET_HOOKS += LIBRESSL_REMOVE_BIN
endif

$(eval $(call AUTOTARGETS,package,libressl))
