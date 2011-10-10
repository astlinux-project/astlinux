#############################################################
#
# libsrtp
#
#############################################################
LIBSRTP_VERSION:=1.4.4
LIBSRTP_SITE:=http://$(BR2_SOURCEFORGE_MIRROR).dl.sourceforge.net/sourceforge/srtp
LIBSRTP_SOURCE:=srtp-$(LIBSRTP_VERSION).tgz
LIBSRTP_INSTALL_STAGING = YES
LIBSRTP_INSTALL_TARGET = YES
LIBSRTP_CONF_OPT = \
	--prefix=/usr \
	--disable-debug

LIBSRTP_MAKE_OPT = CC='$(TARGET_CC)' LD='$(TARGET_LD)' -C $(@D) libsrtp.so

define LIBSRTP_INSTALL_TARGET_CMDS
	cp -a $(STAGING_DIR)/usr/lib/libsrtp.so* $(TARGET_DIR)/usr/lib/
endef

define LIBSRTP_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/lib/libsrtp.so*
endef

$(eval $(call AUTOTARGETS,package,libsrtp))
