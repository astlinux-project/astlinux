#############################################################
#
# acpid
#
#############################################################

ACPID_VERSION = 2.0.33
ACPID_SOURCE = acpid-$(ACPID_VERSION).tar.xz
ACPID_SITE = https://downloads.sourceforge.net/project/acpid2

define ACPID_SET_EVENTS_FILES
	mkdir -p $(TARGET_DIR)/etc/acpi/events
	/bin/echo -e "event=button[ /]power\naction=/sbin/poweroff" > $(TARGET_DIR)/etc/acpi/events/powerbtn
	$(INSTALL) -D -m 755 package/acpid/acpid.init $(TARGET_DIR)/etc/init.d/acpid
	ln -sf ../../init.d/acpid $(TARGET_DIR)/etc/runlevels/default/S24acpid
	ln -sf ../../init.d/acpid $(TARGET_DIR)/etc/runlevels/default/K01acpid
endef

ACPID_POST_INSTALL_TARGET_HOOKS += ACPID_SET_EVENTS_FILES

define ACPID_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/acpid
	rm -f $(TARGET_DIR)/usr/bin/acpi_listen
	rm -f $(TARGET_DIR)/etc/init.d/acpid
	rm -f $(TARGET_DIR)/etc/runlevels/default/S24acpid
	rm -f $(TARGET_DIR)/etc/runlevels/default/K01acpid
endef

$(eval $(call AUTOTARGETS,package,acpid))
