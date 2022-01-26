#############################################################
#
# clix
#
#############################################################
CLIX_VERSION = 2013-03-30
CLIX_SOURCE = clix-$(CLIX_VERSION).tar.gz
CLIX_SITE = https://astlinux-project.org/files

define CLIX_INSTALL_TARGET_CMDS
	install -D -m 755 $(@D)/clix.bin $(TARGET_DIR)/usr/bin/clix
	install -D -m 755 package/clix/sendxmpp $(TARGET_DIR)/usr/bin/sendxmpp
endef

define CLIX_UNINSTALL_TARGET_CMDS
	rm -rf $(TARGET_DIR)/usr/bin/clix
	rm -rf $(TARGET_DIR)/usr/bin/sendxmpp
endef

$(eval $(call GENTARGETS,package,clix))
