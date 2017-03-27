################################################################################
#
# netcalc
#
################################################################################

NETCALC_VERSION = 2.1-694614e
NETCALC_SOURCE = netcalc-$(NETCALC_VERSION).tar.gz
NETCALC_SITE = http://files.astlinux-project.org
#NETCALC_SITE = https://github.com/troglobit/netcalc/releases/download/v$(NETCALC_VERSION)
NETCALC_AUTORECONF = YES

## Default install adds ipcalc symlink, confused with Busybox's
##
define NETCALC_INSTALL_TARGET_CMDS
	$(INSTALL) -D -m 755 $(@D)/netcalc $(TARGET_DIR)/usr/bin/
endef

define NETCALC_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/netcalc
endef

$(eval $(call AUTOTARGETS,package,netcalc))
