#############################################################
#
# acpid
#
#############################################################
ACPID_VERSION = 2.0.14
ACPID_SOURCE = acpid_$(ACPID_VERSION).orig.tar.gz
ACPID_SITE = $(BR2_DEBIAN_MIRROR)/debian/pool/main/a/acpid

define ACPID_BUILD_CMDS
	$(MAKE) CC="$(TARGET_CC)" -C $(@D)
endef

define ACPID_INSTALL_TARGET_CMDS
	install -D -m 755 $(@D)/acpid $(TARGET_DIR)/usr/sbin/acpid
	install -D -m 755 $(@D)/acpi_listen $(TARGET_DIR)/usr/bin/acpi_listen
	mkdir -p $(TARGET_DIR)/etc/acpi/events
	/bin/echo -e "event=button[ /]power\naction=/sbin/poweroff" > $(TARGET_DIR)/etc/acpi/events/powerbtn
	install -D -m 755 package/acpid/acpid.init $(TARGET_DIR)/etc/init.d/acpid
endef

define ACPID_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/acpid
	rm -f $(TARGET_DIR)/usr/bin/acpi_listen
endef

define ACPID_CLEAN_CMDS
	-$(MAKE) -C $(@D) clean
endef

$(eval $(call GENTARGETS,package,acpid))
