################################################################################
#
# libedit
#
################################################################################

LIBEDIT_VERSION = 20210419-3.1
LIBEDIT_SITE = https://thrysoee.dk/editline
LIBEDIT_INSTALL_STAGING = YES

LIBEDIT_DEPENDENCIES = ncurses

define LIBEDIT_INSTALL_TARGET_CMDS
	cp -a $(STAGING_DIR)/usr/lib/libedit.so* $(TARGET_DIR)/usr/lib/
endef

define LIBEDIT_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/lib/libedit.so*
endef

$(eval $(call AUTOTARGETS,package,libedit))
