################################################################################
#
# joe
#
################################################################################

JOE_VERSION = 3.7
JOE_SITE = http://downloads.sourceforge.net/project/joe-editor/JOE%20sources/joe-$(JOE_VERSION)
JOE_SOURCE = joe-$(JOE_VERSION).tar.gz

ifeq ($(BR2_PACKAGE_NCURSES),y)
JOE_DEPENDENCIES += ncurses
JOE_CONF_OPT += --enable-curses
else
JOE_CONF_OPT += --disable-curses
endif

ifneq ($(BR2_PACKAGE_JOE_FULL),y)
define JOE_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/joe $(TARGET_DIR)/usr/bin/joe
endef

define JOE_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/joe
endef
endif

$(eval $(call AUTOTARGETS,package,joe))
