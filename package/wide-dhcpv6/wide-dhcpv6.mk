################################################################################
#
# wide-dhcpv6
#
################################################################################

WIDE_DHCPV6_VERSION = 20080615
WIDE_DHCPV6_SOURCE = wide-dhcpv6-$(WIDE_DHCPV6_VERSION).tar.gz
WIDE_DHCPV6_SITE = http://downloads.sourceforge.net/project/wide-dhcpv6/wide-dhcpv6/wide-dhcpv6-$(WIDE_DHCPV6_VERSION)
WIDE_DHCPV6_DEPENDENCIES = host-bison host-flex flex

WIDE_DHCPV6_CONF_OPT = \
	--sysconfdir=/etc/wide-dhcpv6 \
	ac_cv_func_setpgrp_void=yes

## Only build client
WIDE_DHCPV6_MAKE_OPT = dhcp6c

define WIDE_DHCPV6_INSTALL_TARGET_CMDS
	$(INSTALL) -D -m 0755 $(@D)/dhcp6c $(TARGET_DIR)/usr/sbin/
	$(INSTALL) -D -m 0755 package/wide-dhcpv6/dhcp6c.script $(TARGET_DIR)/etc/dhcp6c.script
	ln -sf /tmp/etc/wide-dhcpv6 $(TARGET_DIR)/etc/wide-dhcpv6
endef

WIDE_DHCPV6_UNINSTALL_STAGING_OPT = --version

define WIDE_DHCPV6_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/dhcp6c
	rm -f $(TARGET_DIR)/etc/dhcp6c.script
	rm -f $(TARGET_DIR)/etc/wide-dhcpv6
endef

$(eval $(call AUTOTARGETS,package,wide-dhcpv6))
