#############################################################
#
# sox
#
#############################################################

SOX_VERSION = 14.4.1
SOX_SOURCE:=sox-$(SOX_VERSION).tar.gz
SOX_SITE = http://downloads.sourceforge.net/sourceforge/sox
SOX_INSTALL_STAGING = YES

SOX_CONF_OPT = \
	--without-libltdl \
	--without-flac \
	--without-ffmpeg \
	--without-ladspa \
	--without-lame \
	--without-id3tag

ifeq ($(BR2_PACKAGE_LIBMAD),y)
	SOX_DEPENDENCIES += libmad
	SOX_CONF_OPT += --with-mad
else
	SOX_CONF_OPT += --without-mad
endif

define SOX_INSTALL_TARGET_CMDS
	$(INSTALL) -D -m 0755 $(STAGING_DIR)/usr/bin/sox $(TARGET_DIR)/usr/bin/sox
	cp -a $(STAGING_DIR)/usr/bin/rec $(TARGET_DIR)/usr/bin/
	cp -a $(STAGING_DIR)/usr/bin/play $(TARGET_DIR)/usr/bin/
	cp -a $(STAGING_DIR)/usr/lib/libsox.so* $(TARGET_DIR)/usr/lib/
endef

define SOX_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/sox
	rm -f $(TARGET_DIR)/usr/bin/rec
	rm -f $(TARGET_DIR)/usr/bin/play
	rm -f $(TARGET_DIR)/usr/lib/libsox.so*
endef

$(eval $(call AUTOTARGETS,package,sox))
