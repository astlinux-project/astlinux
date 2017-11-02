#############################################################
#
# expat
#
#############################################################

EXPAT_VERSION = 2.2.5
EXPAT_SITE = http://downloads.sourceforge.net/project/expat/expat/$(EXPAT_VERSION)
EXPAT_SOURCE = expat-$(EXPAT_VERSION).tar.bz2
EXPAT_INSTALL_STAGING = YES

EXPAT_DEPENDENCIES = host-pkg-config

HOST_EXPAT_DEPENDENCIES = host-pkg-config

EXPAT_CONF_OPT = \
	--without-xmlwf

$(eval $(call AUTOTARGETS,package,expat))
$(eval $(call AUTOTARGETS,package,expat,host))
