#############################################################
#
# fbflash
#
#############################################################
FBFLASH_VERSION:=2.0.0
FBFLASH_SITE:=http://support.red-fone.com/fb_flash/
FBFLASH_SOURCE:=fb_flash-$(FBFLASH_VERSION).tar.gz
FBFLASH_CONF_OPT = \
	--with-shared-libfb \
	--without-readline
	
FBFLASH_DEPENDENCIES = libfb

define FBFLASH_INSTALL_TARGET_CMDS
	cp -a $(@D)/fb_flash_util $(TARGET_DIR)/usr/sbin/
	cp -a $(@D)/fb_reflector $(TARGET_DIR)/usr/sbin/
	cp -a $(@D)/fb_udp $(TARGET_DIR)/usr/sbin/
endef

define FBFLASH_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/fb_flash_util
	rm -f $(TARGET_DIR)/usr/sbin/fb_reflector
	rm -f $(TARGET_DIR)/usr/sbin/fb_udp
endef

$(eval $(call AUTOTARGETS,package,fbflash))
