#############################################################
#
# build GNU readline
#
#############################################################

READLINE_VERSION = 7.0
READLINE_SOURCE = readline-$(READLINE_VERSION).tar.gz
READLINE_SITE = $(BR2_GNU_MIRROR)/readline
READLINE_INSTALL_STAGING = YES

READLINE_DEPENDENCIES = ncurses

READLINE_CONF_ENV = \
	bash_cv_func_sigsetjmp=yes \
	bash_cv_wcwidth_broken=no

define READLINE_INSTALL_TARGET_CMDS
	cp -a $(STAGING_DIR)/usr/lib/libhistory.so* $(TARGET_DIR)/usr/lib/
	cp -a $(STAGING_DIR)/usr/lib/libreadline.so* $(TARGET_DIR)/usr/lib/
	chmod +w $(addprefix $(TARGET_DIR)/usr/lib/,libhistory.so.* libreadline.so.*)
endef

define READLINE_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/lib/libhistory.so*
	rm -f $(TARGET_DIR)/usr/lib/libreadline.so*
endef

$(eval $(call AUTOTARGETS,package,readline))
