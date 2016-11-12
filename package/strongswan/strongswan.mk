################################################################################
#
# strongswan
#
################################################################################

STRONGSWAN_VERSION = 5.5.1
STRONGSWAN_SOURCE = strongswan-$(STRONGSWAN_VERSION).tar.bz2
STRONGSWAN_SITE = https://download.strongswan.org
STRONGSWAN_DEPENDENCIES = openssl host-pkg-config
STRONGSWAN_CONF_OPT += \
	--without-lib-prefix \
	--enable-led=no \
	--enable-pkcs11=no \
	--enable-kernel-netlink=yes \
	--enable-socket-default=yes \
	--enable-openssl=yes \
	--enable-gcrypt=no \
	--enable-gmp=no \
	--enable-af-alg=$(if $(BR2_PACKAGE_STRONGSWAN_AF_ALG),yes,no) \
	--enable-curl=no \
	--enable-charon=$(if $(BR2_PACKAGE_STRONGSWAN_CHARON),yes,no) \
	--enable-tnccs-11=no \
	--enable-tnccs-20=no \
	--enable-tnccs-dynamic=no \
	--enable-xauth-generic=yes \
	--enable-xauth-eap=yes \
	--enable-unity=no \
	--enable-stroke=yes \
	--enable-sqlite=$(if $(BR2_PACKAGE_STRONGSWAN_SQLITE),yes,no) \
	--enable-sql=$(if $(BR2_PACKAGE_STRONGSWAN_SQLITE),yes,no) \
	--enable-attr-sql=$(if $(BR2_PACKAGE_STRONGSWAN_SQLITE),yes,no) \
	--enable-pki=no \
	--enable-scepclient=no \
	--enable-scripts=no \
	--enable-vici=$(if $(BR2_PACKAGE_STRONGSWAN_VICI),yes,no) \
	--enable-swanctl=$(if $(BR2_PACKAGE_STRONGSWAN_VICI),yes,no) \
	--enable-cmd=yes

ifeq ($(BR2_PACKAGE_STRONGSWAN_EAP),y)
STRONGSWAN_CONF_OPT += \
	--enable-eap-identity \
	--enable-eap-md5 \
	--enable-eap-mschapv2 \
	--enable-eap-tls \
	--enable-eap-ttls \
	--enable-eap-peap
endif

ifeq ($(BR2_PACKAGE_STRONGSWAN_SQLITE),y)
STRONGSWAN_DEPENDENCIES += \
	$(if $(BR2_PACKAGE_SQLITE),sqlite)
endif

define STRONGSWAN_POST_INSTALL
	$(INSTALL) -m 0755 -D package/strongswan/ipsec.init $(TARGET_DIR)/etc/init.d/ipsec
	ln -sf ../../init.d/ipsec $(TARGET_DIR)/etc/runlevels/default/S31ipsec
	ln -sf ../../init.d/ipsec $(TARGET_DIR)/etc/runlevels/default/K20ipsec
endef

STRONGSWAN_POST_INSTALL_TARGET_HOOKS = STRONGSWAN_POST_INSTALL

STRONGSWAN_UNINSTALL_STAGING_OPT = --version

define STRONGSWAN_UNINSTALL_TARGET_CMDS
	rm -rf $(TARGET_DIR)/usr/lib/ipsec
	rm -rf $(TARGET_DIR)/usr/libexec/ipsec
	rm -rf $(TARGET_DIR)/etc/strongswan.*
	rm -rf $(TARGET_DIR)/etc/ipsec.*
	rm -rf $(TARGET_DIR)/etc/swanctl
	rm -rf $(TARGET_DIR)/usr/share/strongswan
	rm -f $(TARGET_DIR)/etc/init.d/ipsec
	rm -f $(TARGET_DIR)/etc/runlevels/default/S31ipsec
	rm -f $(TARGET_DIR)/etc/runlevels/default/K20ipsec
endef

$(eval $(call AUTOTARGETS,package,strongswan))
