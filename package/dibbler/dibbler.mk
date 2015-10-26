################################################################################
#
# dibbler
#
################################################################################

DIBBLER_VERSION = 1.0.1
DIBBLER_SOURCE = dibbler-$(DIBBLER_VERSION).tar.gz
DIBBLER_SITE = http://downloads.sourceforge.net/project/dibbler/dibbler/$(DIBBLER_VERSION)

DIBBLER_CONF_OPT = \
	--enable-dst-addr-check \
	--disable-dns-update \
	--disable-auth \
	--disable-link-state

## Only build dibbler-client
DIBBLER_MAKE_OPT = client

define DIBBLER_INSTALL_TARGET_CMDS
	$(INSTALL) -D -m 0755 $(@D)/dibbler-client $(TARGET_DIR)/usr/sbin/
	ln -sf /tmp/etc/dibbler $(TARGET_DIR)/etc/dibbler
endef

define DIBBLER_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/dibbler-client
	rm -f $(TARGET_DIR)/etc/dibbler
endef

$(eval $(call AUTOTARGETS,package,dibbler))
