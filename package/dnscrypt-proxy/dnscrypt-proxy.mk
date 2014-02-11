#############################################################
#
# dnscrypt-proxy
#
#############################################################
DNSCRYPT_PROXY_VERSION = 1.3.3
DNSCRYPT_PROXY_SOURCE = dnscrypt-proxy-$(DNSCRYPT_PROXY_VERSION).tar.gz
DNSCRYPT_PROXY_SITE = http://download.dnscrypt.org/dnscrypt-proxy

DNSCRYPT_PROXY_DEPENDENCIES += libsodium

# libltdl (libtool)
ifeq ($(BR2_PACKAGE_LIBTOOL),y)
DNSCRYPT_PROXY_DEPENDENCIES += libtool
endif

define DNSCRYPT_PROXY_INSTALL_TARGET_CMDS
	$(INSTALL) -D -m 0755 $(@D)/src/proxy/dnscrypt-proxy $(TARGET_DIR)/usr/sbin/
	$(INSTALL) -D -m 0755 package/dnscrypt-proxy/dnscrypt-proxy.init $(TARGET_DIR)/etc/init.d/dnscrypt
	ln -sf ../../init.d/dnscrypt $(TARGET_DIR)/etc/runlevels/default/S18dnscrypt
	ln -sf ../../init.d/dnscrypt $(TARGET_DIR)/etc/runlevels/default/K15dnscrypt
endef

define DNSCRYPT_PROXY_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/dnscrypt-proxy
	rm -f $(TARGET_DIR)/etc/init.d/dnscrypt
	rm -f $(TARGET_DIR)/etc/runlevels/default/S18dnscrypt
	rm -f $(TARGET_DIR)/etc/runlevels/default/K15dnscrypt
endef

$(eval $(call AUTOTARGETS,package,dnscrypt-proxy))
