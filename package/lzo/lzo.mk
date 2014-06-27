#############################################################
#
# lzo
#
#############################################################
LZO_VERSION = 2.07
LZO_SITE = http://www.oberhumer.com/opensource/lzo/download
LZO_INSTALL_STAGING = YES

# Manually patch libtool
LZO_LIBTOOL_PATCH = NO
define LZO_LIBTOOL_PATCH_2.4.2
	@echo "Patching libtool 2.4.2"
	toolchain/patch-kernel.sh $(@D) package buildroot-libtool-v2.4.2.patch
endef
LZO_POST_PATCH_HOOKS += LZO_LIBTOOL_PATCH_2.4.2

$(eval $(call AUTOTARGETS,package,lzo))
$(eval $(call AUTOTARGETS,package,lzo,host))
