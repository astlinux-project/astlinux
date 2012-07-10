#############################################################
#
# libfb
#
#############################################################
LIBFB_VERSION:=2.0.2
LIBFB_SITE:=http://support.red-fone.com/downloads/fonulator
LIBFB_SOURCE:=libfb-$(LIBFB_VERSION).tar.gz
LIBFB_INSTALL_STAGING = YES
LIBFB_INSTALL_TARGET = YES
LIBFB_CONF_OPT = \
	--prefix=/usr \
	--disable-debug
	
LIBFB_DEPENDENCIES = libnet

define LIBFB_INSTALL_TARGET_CMDS
	cp -a $(STAGING_DIR)/usr/lib/libfb.so* $(TARGET_DIR)/usr/lib/
endef

define LIBFB_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/lib/libfb.so*
endef

$(eval $(call AUTOTARGETS,package,libfb))
