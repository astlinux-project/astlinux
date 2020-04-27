#############################################################
#
# flashrom
#
#############################################################

FLASHROM_VERSION = 1.2
FLASHROM_SOURCE = flashrom-v$(FLASHROM_VERSION).tar.bz2
FLASHROM_SITE = https://download.flashrom.org/releases

FLASHROM_DEPENDENCIES = pciutils libusb libusb-compat host-pkg-config

define FLASHROM_BUILD_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) $(TARGET_CONFIGURE_OPTS) -C $(@D)
endef

define FLASHROM_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/flashrom $(TARGET_DIR)/usr/sbin/flashrom
endef

$(eval $(call GENTARGETS,package,flashrom))
