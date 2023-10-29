#############################################################
#
# ne
#
############################################################

NE_VERSION = 3.3.3
NE_SOURCE = ne-$(NE_VERSION).tar.gz
NE_SITE = http://ne.di.unimi.it

NE_DEPENDENCIES = ncurses

NE_SYNTAX_TYPES = asterisk conf ini perl sh

define NE_BUILD_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) CC="$(TARGET_CC)" \
	NE_GLOBAL_DIR="/usr/share/ne" \
	GCCFLAGS="$(TARGET_CFLAGS) -std=c99 -Wall -Wno-parentheses" \
	-C $(@D)/src
endef

define NE_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/src/ne $(TARGET_DIR)/usr/bin/ne
	$(INSTALL) -d -m 0755 $(TARGET_DIR)/usr/share/ne/syntax
	## Install select syntax files
	for i in $(NE_SYNTAX_TYPES); do \
	  $(INSTALL) -m 0444 -D $(@D)/syntax/$$i.jsf $(TARGET_DIR)/usr/share/ne/syntax/ ; \
	done
	## Install supporting files
	$(INSTALL) -m 0444 -D package/ne/share/dot-keys $(TARGET_DIR)/usr/share/ne/.keys
	$(INSTALL) -m 0444 -D package/ne/share/extensions $(TARGET_DIR)/usr/share/ne/extensions
endef

define NE_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/ne
	rm -rf $(TARGET_DIR)/usr/share/ne
endef

$(eval $(call GENTARGETS,package,ne))
