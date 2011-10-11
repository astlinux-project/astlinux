#############################################################
#
# sox
#
#############################################################

SOX_VERSION = 12.17.9
SOX_SOURCE:=sox-$(SOX_VERSION).tar.gz
SOX_SITE = http://$(BR2_SOURCEFORGE_MIRROR).dl.sourceforge.net/sourceforge/sox

ifeq ($(BR2_PACKAGE_LIBMAD),y)
SOX_DEPENDENCIES = libmad
endif

define SOX_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/src/sox $(TARGET_DIR)/usr/bin/sox
	$(INSTALL) -m 0755 -D $(@D)/src/soxmix $(TARGET_DIR)/usr/bin/soxmix
endef

define SOX_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/sox
	rm -f $(TARGET_DIR)/usr/bin/soxmix
endef

$(eval $(call AUTOTARGETS,package,sox))
