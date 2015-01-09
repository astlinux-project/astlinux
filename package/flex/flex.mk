#############################################################
#
# flex
#
#############################################################
FLEX_VERSION = 2.5.37
FLEX_SOURCE = flex-$(FLEX_VERSION).tar.gz
FLEX_SITE = http://download.sourceforge.net/project/flex
FLEX_INSTALL_STAGING=YES
FLEX_DEPENDENCIES = host-m4
HOST_FLEX_DEPENDENCIES = host-m4

FLEX_CONF_ENV = ac_cv_path_M4=$(HOST_DIR)/usr/bin/m4
HOST_FLEX_CONF_ENV = ac_cv_path_M4=$(HOST_DIR)/usr/bin/m4

define FLEX_DISABLE_PROGRAM
	$(SED) 's/^bin_PROGRAMS.*//' $(@D)/Makefile.in
endef
FLEX_POST_PATCH_HOOKS += FLEX_DISABLE_PROGRAM

# flex++ symlink is broken when flex binary is not installed
define FLEX_REMOVE_BROKEN_SYMLINK
	rm -f $(TARGET_DIR)/usr/bin/flex++
endef
FLEX_POST_INSTALL_TARGET_HOOKS += FLEX_REMOVE_BROKEN_SYMLINK

$(eval $(call AUTOTARGETS,package,flex))
$(eval $(call AUTOTARGETS,package,flex,host))
