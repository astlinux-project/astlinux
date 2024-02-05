#############################################################
#
# jpeg (libjpeg)
#
#############################################################

JPEG_VERSION = 9f
JPEG_SITE = https://www.ijg.org/files
JPEG_SOURCE = jpegsrc.v$(JPEG_VERSION).tar.gz
JPEG_INSTALL_STAGING = YES

define JPEG_REMOVE_USELESS_TOOLS
	rm -f $(addprefix $(TARGET_DIR)/usr/bin/,cjpeg djpeg jpegtrans rdjpgcom wrjpgcom)
endef

JPEG_POST_INSTALL_TARGET_HOOKS += JPEG_REMOVE_USELESS_TOOLS

$(eval $(call AUTOTARGETS,package,jpeg))
