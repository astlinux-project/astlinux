################################################################################
#
# arp-scan
#
################################################################################

ARP_SCAN_VERSION = 1.9.8
ARP_SCAN_SOURCE = arp-scan-$(ARP_SCAN_VERSION).tar.gz
ARP_SCAN_SITE = https://github.com/royhills/arp-scan/archive/$(ARP_SCAN_VERSION)
ARP_SCAN_AUTORECONF = YES

ARP_SCAN_DEPENDENCIES = libpcap

# Only used and needed for 32-bit targets
ARP_SCAN_CONF_ENV = pgac_cv_snprintf_long_long_int_format="%lld"

define ARP_SCAN_INSTALL_TARGET_CMDS
	$(INSTALL) -D -m 0755 $(@D)/arp-scan $(TARGET_DIR)/usr/bin/arp-scan
endef

define ARP_SCAN_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/arp-scan
endef

$(eval $(call AUTOTARGETS,package,arp-scan))
