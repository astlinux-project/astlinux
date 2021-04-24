#############################################################
#
# openvpn
#
#############################################################

OPENVPN_VERSION = 2.4.11
OPENVPN_SITE = https://swupdate.openvpn.net/community/releases
OPENVPN_DEPENDENCIES = host-pkg-config openssl

OPENVPN_CONF_OPT = \
	--disable-debug \
	--disable-plugins \
	--disable-lz4 \
	--enable-iproute2 \
	--with-crypto-library=openssl

OPENVPN_CONF_ENV = \
	IFCONFIG=/sbin/ifconfig \
	NETSTAT=/bin/netstat \
	ROUTE=/sbin/route

ifeq ($(BR2_PACKAGE_IPROUTE2),y)
OPENVPN_CONF_ENV += IPROUTE=/sbin/ip
else
OPENVPN_CONF_ENV += IPROUTE=/bin/ip
endif

ifeq ($(BR2_PACKAGE_OPENVPN_LZO),y)
	OPENVPN_DEPENDENCIES += lzo
else
	OPENVPN_CONF_OPT += --disable-lzo
endif

define OPENVPN_INSTALL_TARGET_CMDS
	$(INSTALL) -m 755 $(@D)/src/openvpn/openvpn $(TARGET_DIR)/usr/sbin/openvpn
	$(INSTALL) -m 755 -D package/openvpn/openvpn.init $(TARGET_DIR)/etc/init.d/openvpn
	$(INSTALL) -m 644 -D package/openvpn/openvpn.logrotate $(TARGET_DIR)/etc/logrotate.d/openvpn
	$(INSTALL) -m 755 -D package/openvpn/openvpnclient.init $(TARGET_DIR)/etc/init.d/openvpnclient
	$(INSTALL) -m 755 -D package/openvpn/tls-verify.sh $(TARGET_DIR)/usr/sbin/openvpn-tls-verify
	$(INSTALL) -m 755 -D package/openvpn/user-pass-verify.sh $(TARGET_DIR)/usr/sbin/openvpn-user-pass-verify
	ln -sf /tmp/etc/openvpn.conf $(TARGET_DIR)/etc/openvpn.conf
	ln -sf /tmp/etc/openvpnclient.conf $(TARGET_DIR)/etc/openvpnclient.conf
	ln -sf /tmp/etc/openvpn $(TARGET_DIR)/etc/openvpn
endef

define OPENVPN_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/openvpn
	rm -f $(TARGET_DIR)/etc/init.d/openvpn
	rm -f $(TARGET_DIR)/etc/init.d/openvpnclient
endef

$(eval $(call AUTOTARGETS,package,openvpn))
