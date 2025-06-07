#############################################################
#
# libmad
#
#############################################################

LIBMAD_VERSION = 0.15.1b
LIBMAD_SITE = https://downloads.sourceforge.net/project/mad/libmad/$(LIBMAD_VERSION)
LIBMAD_INSTALL_STAGING = YES

# Force autoreconf to be able to use a more recent libtool script, that
# is able to properly behave in the face of a missing C++ compiler.
LIBMAD_AUTORECONF = YES

define LIBMAD_INSTALL_STAGING_PC
	$(INSTALL) -D package/multimedia/libmad/mad.pc \
		$(STAGING_DIR)/usr/lib/pkgconfig/mad.pc
endef

LIBMAD_POST_INSTALL_STAGING_HOOKS += LIBMAD_INSTALL_STAGING_PC

LIBMAD_CONF_OPT = \
		--disable-debugging \
		--enable-speed

$(eval $(call AUTOTARGETS,package/multimedia,libmad))
