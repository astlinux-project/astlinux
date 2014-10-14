#############################################################
#
# libsrtp
#
#############################################################
LIBSRTP_VERSION = 1.5.0
LIBSRTP_SITE = http://files.astlinux.org
#LIBSRTP_SITE = https://github.com/cisco/libsrtp/releases
LIBSRTP_SOURCE = libsrtp-$(LIBSRTP_VERSION).tar.gz
LIBSRTP_INSTALL_STAGING = YES
LIBSRTP_DEPENDENCIES = openssl

LIBSRTP_CONF_OPT = \
	--prefix=/usr \
	--enable-openssl \
	--enable-syslog \
	--disable-stdout \
	--disable-debug

LIBSRTP_MAKE_OPT = \
	CC='$(TARGET_CC)' \
	LD='$(TARGET_LD)' \
	CFLAGS='$(TARGET_CFLAGS) -fPIC' \
	-C $(@D) libsrtp.so

define LIBSRTP_INSTALL_TARGET_CMDS
	cp -a $(STAGING_DIR)/usr/lib/libsrtp.so* $(TARGET_DIR)/usr/lib/
endef

define LIBSRTP_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/lib/libsrtp.so*
endef

$(eval $(call AUTOTARGETS,package,libsrtp))
