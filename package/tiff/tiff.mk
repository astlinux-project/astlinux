#############################################################
#
# tiff
#
#############################################################

TIFF_VERSION = 4.7.0
TIFF_SITE = https://download.osgeo.org/libtiff
TIFF_SOURCE = tiff-$(TIFF_VERSION).tar.gz
TIFF_INSTALL_STAGING = YES
TIFF_CONF_OPT = \
	--disable-contrib \
	--disable-cxx \
	--disable-lerc \
	--disable-jbig \
	--disable-tests \
	--disable-docs \
	--disable-lzma \
	--disable-webp \
	--disable-zstd \
	--disable-libdeflate

TIFF_DEPENDENCIES = host-pkg-config zlib jpeg

define TIFF_INSTALL_TARGET_CMDS
	cp -a $(@D)/libtiff/.libs/libtiff.so* $(TARGET_DIR)/usr/lib/
	$(INSTALL) -D -m 0755 $(STAGING_DIR)/usr/bin/tiff2pdf $(TARGET_DIR)/usr/bin/tiff2pdf
	$(INSTALL) -D -m 0755 $(STAGING_DIR)/usr/bin/tiffinfo $(TARGET_DIR)/usr/bin/tiffinfo
endef

define TIFF_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/tiff2pdf
	rm -f $(TARGET_DIR)/usr/bin/tiffinfo
endef

$(eval $(call AUTOTARGETS,package,tiff))
