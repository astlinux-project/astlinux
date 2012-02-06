#############################################################
#
# spandsp
#
#############################################################
SPANDSP_VERSION = 0.0.6pre20
SPANDSP_SITE = http://www.soft-switch.org/downloads/spandsp
SPANDSP_SOURCE:=spandsp-$(SPANDSP_VERSION).tgz
SPANDSP_INSTALL_STAGING = YES
SPANDSP_INSTALL_TARGET = YES
SPANDSP_CONF_ENV = \
	ac_cv_file__usr_X11R6_lib=no \
	ac_cv_file__usr_X11R6_lib64=no \
	ac_cv_func_realloc_0_nonnull=yes \
	ac_cv_func_malloc_0_nonnull=yes

SPANDSP_DEPENDENCIES = tiff

define SPANDSP_INSTALL_DICTIONARY
	cp package/spandsp/at_interpreter_dictionary.h $(@D)/src/
endef

SPANDSP_POST_EXTRACT_HOOKS += SPANDSP_INSTALL_DICTIONARY

$(eval $(call AUTOTARGETS,package,spandsp))
