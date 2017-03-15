#############################################################
#
# bash
#
#############################################################

BASH_VERSION = 4.1
BASH_SITE = $(BR2_GNU_MIRROR)/bash
BASH_DEPENDENCIES = ncurses host-bison
BASH_CONF_ENV += \
	bash_cv_job_control_missing=present \
	bash_cv_sys_named_pipes=present     \
	bash_cv_func_sigsetjmp=present      \
	bash_cv_printf_a_format=yes

# Make sure we build after busybox so that /bin/sh links to bash
ifeq ($(BR2_PACKAGE_BUSYBOX),y)
BASH_DEPENDENCIES += busybox
endif

define BASH_LOADABLE_BUILTINS
	$(TARGET_MAKE_ENV) $(MAKE) -C $(@D)/examples/loadables sleep
endef
BASH_POST_BUILD_HOOKS = BASH_LOADABLE_BUILTINS

ifeq ($(BR2_PACKAGE_BASH_DEFAULT_SHELL),y)
define BASH_DEFAULT_SHELL
	if [ -e $(TARGET_DIR)/bin/sh ]; then \
		mv -f $(TARGET_DIR)/bin/sh $(TARGET_DIR)/bin/sh.prebash; \
	fi
	ln -sf bash $(TARGET_DIR)/bin/sh
endef
endif

define BASH_RESTRICTED_SHELL
	ln -sf bash $(TARGET_DIR)/bin/rbash
	# Define /usr/rbin with symlinks
	rm -rf $(TARGET_DIR)/usr/rbin
	$(INSTALL) -d -m 0755 $(TARGET_DIR)/usr/rbin
	(for i in `cat package/bash/rbash/cmd_symlinks.txt`; \
	do ln -s "../../$$i" $(TARGET_DIR)/usr/rbin/`basename "$$i"`; done)
	$(INSTALL) -D -m 0755 package/bash/rbash/grep.sh $(TARGET_DIR)/usr/rbin/grep
endef

# Save the old sh file/link if there is one and symlink bash->sh
define BASH_INSTALL_TARGET_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) -C $(@D) \
		DESTDIR=$(TARGET_DIR) exec_prefix=/ install
	rm -f $(TARGET_DIR)/bin/bashbug
	$(INSTALL) -D -m 0755 $(@D)/examples/loadables/sleep $(TARGET_DIR)/usr/lib/bash/sleep
	$(BASH_DEFAULT_SHELL)
	$(BASH_RESTRICTED_SHELL)
endef

# Restore the old shell file/link if there was one
define BASH_UNINSTALL_TARGET_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) DESTDIR=$(TARGET_DIR) \
		-C $(BASH_DIR) exec_prefix=/ uninstall
	if [ -e $(TARGET_DIR)/bin/sh.prebash ]; then \
		mv -f $(TARGET_DIR)/bin/sh.prebash $(TARGET_DIR)/bin/sh; \
	fi
	rm -rf $(TARGET_DIR)/usr/lib/bash
endef

$(eval $(call AUTOTARGETS,package,bash))
