#############################################################
#
# expat
#
#############################################################

EXPAT_VERSION = 2.2.2
EXPAT_SITE = http://downloads.sourceforge.net/project/expat/expat/$(EXPAT_VERSION)
EXPAT_SOURCE = expat-$(EXPAT_VERSION).tar.bz2
EXPAT_INSTALL_STAGING = YES
EXPAT_INSTALL_STAGING_OPT = DESTDIR=$(STAGING_DIR) installlib
EXPAT_INSTALL_TARGET_OPT = DESTDIR=$(TARGET_DIR) installlib

EXPAT_DEPENDENCIES = host-pkg-config

EXPAT_CONF_ENV = CFLAGS='$(TARGET_CFLAGS) -DXML_POOR_ENTROPY'

HOST_EXPAT_DEPENDENCIES = host-pkg-config

HOST_EXPAT_CONF_ENV = CFLAGS='$(HOST_CFLAGS) -DXML_POOR_ENTROPY'

$(eval $(call AUTOTARGETS,package,expat))
$(eval $(call AUTOTARGETS,package,expat,host))
