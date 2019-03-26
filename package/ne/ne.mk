#############################################################
#
# ne
#
############################################################

NE_VERSION = 3.1.2
NE_SOURCE = ne-$(NE_VERSION).tar.gz
NE_SITE = http://ne.di.unimi.it

NE_DEPENDENCIES = ncurses

define NE_BUILD_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) CC="$(TARGET_CC)" \
	NE_GLOBAL_DIR="/usr/share/ne" \
	GCCFLAGS="$(TARGET_CFLAGS) -std=c99 -Wall -Wno-parentheses" \
	-C $(@D)/src
endef

define NE_INSTALL_TARGET_CMDS
	$(INSTALL) -d -m 0755 $(TARGET_DIR)/usr/share/ne
	$(INSTALL) -d -m 0755 $(TARGET_DIR)/usr/share/ne/syntax
	$(INSTALL) -m 0444 -D package/ne/share/dot-keys $(TARGET_DIR)/usr/share/ne/.keys
	$(INSTALL) -m 0444 -D package/ne/share/extensions $(TARGET_DIR)/usr/share/ne/extensions
	$(INSTALL) -m 0444 -D package/ne/share/syntax/asterisk.jsf $(TARGET_DIR)/usr/share/ne/syntax/
	$(INSTALL) -m 0444 -D $(@D)/syntax/conf.jsf $(TARGET_DIR)/usr/share/ne/syntax/
	$(INSTALL) -m 0444 -D $(@D)/syntax/sh.jsf $(TARGET_DIR)/usr/share/ne/syntax/
	$(INSTALL) -m 0444 -D $(@D)/syntax/perl.jsf $(TARGET_DIR)/usr/share/ne/syntax/
	$(INSTALL) -m 0755 -D $(@D)/src/ne $(TARGET_DIR)/usr/bin/ne
endef

define NE_UNINSTALL_TARGET_CMDS
	rm -rf $(TARGET_DIR)/usr/share/ne
	rm -f $(TARGET_DIR)/usr/bin/ne
endef

$(eval $(call GENTARGETS,package,ne))
