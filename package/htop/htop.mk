#############################################################
#
# htop
#
#############################################################

HTOP_VERSION = 1.0.3
HTOP_SOURCE = htop-$(HTOP_VERSION).tar.gz
HTOP_SITE = http://hisham.hm/htop/releases/$(HTOP_VERSION)
HTOP_DEPENDENCIES = ncurses
HTOP_AUTORECONF = YES

HTOP_CONF_OPT = \
	--disable-unicode

HTOP_CONF_ENV = \
	ac_cv_file__proc_stat=yes \
	ac_cv_file__proc_meminfo=yes

define HTOP_INSTALL_TARGET_CMDS
	$(INSTALL) -D -m 0755 $(@D)/htop $(TARGET_DIR)/usr/bin/htop
endef

define HTOP_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/htop
endef

$(eval $(call AUTOTARGETS,package,htop))
