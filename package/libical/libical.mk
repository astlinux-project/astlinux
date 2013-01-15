#############################################################
#
# libical
#
#############################################################

#LIBICAL_VERSION = 0.48
#LIBICAL_SITE = http://downloads.sourceforge.net/project/freeassociation/libical/libical-$(LIBICAL_VERSION)
LIBICAL_VERSION = r1139
LIBICAL_SITE = http://files.astlinux.org
LIBICAL_INSTALL_STAGING = YES
LIBICAL_AUTORECONF = YES

LIBICAL_CONF_OPT = \
		--disable-static

$(eval $(call AUTOTARGETS,package,libical))
