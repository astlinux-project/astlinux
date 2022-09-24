#############################################################
#
# zabbix
#
#############################################################

ZABBIX_VERSION = 4.0.44
ZABBIX_SOURCE = zabbix-$(ZABBIX_VERSION).tar.gz
ZABBIX_SITE = https://cdn.zabbix.com/zabbix/sources/stable/4.0

ZABBIX_CONF_OPT = \
	--disable-static \
	--enable-agent \
	--enable-ipv6

ifeq ($(BR2_PACKAGE_ZABBIX_PROXY),y)
ZABBIX_DEPENDENCIES += sqlite fping
ZABBIX_CONF_OPT += \
	--enable-proxy \
	--with-sqlite3="$(STAGING_DIR)/usr"

 ifeq ($(BR2_PACKAGE_CURL),y)
ZABBIX_DEPENDENCIES += libcurl
ZABBIX_CONF_OPT += \
	--with-libcurl="$(STAGING_DIR)/usr/bin/curl-config"
 endif

 ifeq ($(BR2_PACKAGE_NETSNMP),y)
ZABBIX_DEPENDENCIES += netsnmp
ZABBIX_CONF_OPT += \
	--with-net-snmp="$(STAGING_DIR)/usr/bin/net-snmp-config"
 endif
endif

ifeq ($(BR2_PACKAGE_OPENSSL),y)
ZABBIX_DEPENDENCIES += openssl
ZABBIX_CONF_OPT += --with-openssl=$(STAGING_DIR)/usr
else
ZABBIX_CONF_OPT += --without-openssl
endif

ifeq ($(BR2_PACKAGE_PCRE),y)
ZABBIX_DEPENDENCIES += pcre
ZABBIX_CONF_OPT += --with-libpcre=$(STAGING_DIR)/usr
else
ZABBIX_CONF_OPT += --without-libpcre
endif

define ZABBIX_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/src/zabbix_agent/zabbix_agentd $(TARGET_DIR)/usr/bin/zabbix_agentd
	if [ -f $(@D)/src/zabbix_proxy/zabbix_proxy ]; then \
	  $(INSTALL) -m 0755 -D $(@D)/src/zabbix_proxy/zabbix_proxy $(TARGET_DIR)/usr/bin/zabbix_proxy ; \
          ln -sf /tmp/etc/zabbix_proxy.conf $(TARGET_DIR)/etc/zabbix_proxy.conf ; \
	fi
	$(INSTALL) -m 0755 -D package/zabbix/zabbix.init $(TARGET_DIR)/etc/init.d/zabbix
	ln -sf /tmp/etc/zabbix_agentd.conf $(TARGET_DIR)/etc/zabbix_agentd.conf
	ln -sf ../../init.d/zabbix $(TARGET_DIR)/etc/runlevels/default/S98zabbix
	ln -sf ../../init.d/zabbix $(TARGET_DIR)/etc/runlevels/default/K01zabbix
endef

define ZABBIX_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/zabbix_agentd
	rm -f $(TARGET_DIR)/usr/bin/zabbix_proxy
	rm -f $(TARGET_DIR)/etc/init.d/zabbix
	rm -f $(TARGET_DIR)/etc/zabbix_agentd.conf
	rm -f $(TARGET_DIR)/etc/zabbix_proxy.conf
	rm -f $(TARGET_DIR)/etc/runlevels/default/S98zabbix
	rm -f $(TARGET_DIR)/etc/runlevels/default/K01zabbix
endef

$(eval $(call AUTOTARGETS,package,zabbix))
