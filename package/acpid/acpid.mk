#############################################################
#
# acpid
#
#############################################################

ACPID_VERSION = 2.0.23
ACPID_SOURCE = acpid-$(ACPID_VERSION).tar.xz
ACPID_SITE = http://downloads.sourceforge.net/project/acpid2

define ACPID_SET_EVENTS
	mkdir -p $(TARGET_DIR)/etc/acpi/events
	/bin/echo -e "event=button[ /]power\naction=/sbin/poweroff" > $(TARGET_DIR)/etc/acpi/events/powerbtn
	$(INSTALL) -D -m 755 package/acpid/acpid.init $(TARGET_DIR)/etc/init.d/acpid
endef

ACPID_POST_INSTALL_TARGET_HOOKS += ACPID_SET_EVENTS

$(eval $(call AUTOTARGETS,package,acpid))
