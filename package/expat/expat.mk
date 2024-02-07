#############################################################
#
# expat
#
#############################################################

EXPAT_VERSION = 2.6.0
EXPAT_SITE = https://github.com/libexpat/libexpat/releases/download/R_$(subst .,_,$(EXPAT_VERSION))
EXPAT_SOURCE = expat-$(EXPAT_VERSION).tar.bz2
EXPAT_INSTALL_STAGING = YES

EXPAT_DEPENDENCIES = host-pkg-config

HOST_EXPAT_DEPENDENCIES = host-pkg-config

EXPAT_CONF_OPT = \
	--without-docbook \
	--without-xmlwf

HOST_EXPAT_CONF_OPT = \
	--without-docbook

$(eval $(call AUTOTARGETS,package,expat))
$(eval $(call AUTOTARGETS,package,expat,host))
