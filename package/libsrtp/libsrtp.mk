#############################################################
#
# libsrtp
#
#############################################################

LIBSRTP_VERSION = 2.7.0
LIBSRTP_SITE = https://github.com/cisco/libsrtp/archive/v$(LIBSRTP_VERSION)
LIBSRTP_SOURCE = libsrtp-$(LIBSRTP_VERSION).tar.gz
LIBSRTP_INSTALL_STAGING = YES
LIBSRTP_DEPENDENCIES = host-pkg-config openssl

LIBSRTP_CONF_OPT = \
	--prefix=/usr \
	--disable-nss \
	--enable-openssl

LIBSRTP_MAKE_OPT = shared_library

define LIBSRTP_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/lib/libsrtp2.so*
endef

$(eval $(call AUTOTARGETS,package,libsrtp))
