#############################################################
#
# spandsp
#
#############################################################
SPANDSP_VERSION = 0.0.6
SPANDSP_SITE = http://www.soft-switch.org/downloads/spandsp
SPANDSP_SOURCE:=spandsp-$(SPANDSP_VERSION).tar.gz
SPANDSP_AUTORECONF = YES
SPANDSP_INSTALL_STAGING = YES
SPANDSP_CONF_ENV = \
	ac_cv_file__usr_X11R6_lib=no \
	ac_cv_file__usr_X11R6_lib64=no

SPANDSP_DEPENDENCIES = tiff

ifeq ($(strip $(BR2_PACKAGE_LIBXML2)),y)
	SPANDSP_DEPENDENCIES += libxml2
endif

ifeq ($(strip $(BR2_PACKAGE_JPEG)),y)
	SPANDSP_DEPENDENCIES += jpeg
endif

define SPANDSP_CONFIGURE_FIXUP
	$(SED) 's:-Wunused-but-set-variable ::' $(@D)/configure.ac
endef

SPANDSP_POST_EXTRACT_HOOKS += SPANDSP_CONFIGURE_FIXUP

$(eval $(call AUTOTARGETS,package,spandsp))
