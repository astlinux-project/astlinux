#############################################################
#
# libnet
#
#############################################################
LIBNET_VERSION:=1.1.6
LIBNET_SITE:=http://$(BR2_SOURCEFORGE_MIRROR).dl.sourceforge.net/sourceforge/libnet-dev
LIBNET_SOURCE:=libnet-$(LIBNET_VERSION).tar.gz
LIBNET_INSTALL_STAGING = YES
LIBNET_INSTALL_TARGET = YES
LIBNET_CONF_OPT = \
	--prefix=/usr \
	--disable-debug

LIBNET_DEPENDENCIES = libpcap

define LIBNET_INSTALL_TARGET_CMDS
	cp -a $(STAGING_DIR)/usr/lib/libnet.so* $(TARGET_DIR)/usr/lib/
endef

define LIBNET_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/lib/libnet.so*
endef

$(eval $(call AUTOTARGETS,package,libnet))
