#############################################################
#
# openvpn
#
#############################################################

OPENVPN_VERSION = 2.2.2
OPENVPN_SITE = http://swupdate.openvpn.net/community/releases
OPENVPN_CONF_OPT = --disable-plugins

ifeq ($(BR2_PACKAGE_OPENVPN_LZO),y)
	OPENVPN_DEPENDENCIES += lzo
else
	OPENVPN_CONF_OPT += --disable-lzo
endif

ifeq ($(BR2_PACKAGE_OPENVPN_OPENSSL),y)
	OPENVPN_DEPENDENCIES += openssl
else
	OPENVPN_CONF_OPT += --disable-crypto --disable-ssl
endif

define OPENVPN_INSTALL_TARGET_CMDS
	$(INSTALL) -m 755 $(@D)/openvpn $(TARGET_DIR)/usr/sbin/openvpn
	$(INSTALL) -m 755 -D package/openvpn/openvpn.init $(TARGET_DIR)/etc/init.d/openvpn
	$(INSTALL) -m 755 -D package/openvpn/openvpnclient.init $(TARGET_DIR)/etc/init.d/openvpnclient
	$(INSTALL) -m 755 -D package/openvpn/tls-verify.sh $(TARGET_DIR)/usr/sbin/openvpn-tls-verify
	mkdir -p $(TARGET_DIR)/stat/etc/openvpn
	ln -sf /tmp/etc/openvpn.conf $(TARGET_DIR)/etc/openvpn.conf
	ln -sf /tmp/etc/openvpnclient.conf $(TARGET_DIR)/etc/openvpnclient.conf
	ln -sf /tmp/etc/openvpn $(TARGET_DIR)/etc/openvpn
	tar -C $(TARGET_DIR)/stat/etc/openvpn -xzf package/openvpn/easy-rsa.tar.gz
endef

define OPENVPN_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/openvpn
	rm -f $(TARGET_DIR)/etc/init.d/openvpn
	rm -f $(TARGET_DIR)/etc/init.d/openvpnclient
endef

$(eval $(call AUTOTARGETS,package,openvpn))
