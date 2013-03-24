#############################################################
#
# clix
#
#############################################################
CLIX_VERSION = 2013-03-24
CLIX_SOURCE = clix-$(CLIX_VERSION).tar.gz
CLIX_SITE = http://files.astlinux.org

define CLIX_INSTALL_TARGET_CMDS
	install -D -m 755 $(@D)/clix.bin $(TARGET_DIR)/usr/bin/clix
endef

define CLIX_UNINSTALL_TARGET_CMDS
	rm -rf $(TARGET_DIR)/usr/bin/clix
endef

$(eval $(call GENTARGETS,package,clix))
