#############################################################
#
# zabbix
#
#############################################################

ZABBIX_VERSION = 2.2.9
ZABBIX_SOURCE = zabbix-$(ZABBIX_VERSION).tar.gz
ZABBIX_SITE = http://downloads.sourceforge.net/sourceforge/zabbix

ZABBIX_CONF_OPT = \
	--enable-agent \
	--enable-ipv6

ifeq ($(strip $(BR2_PACKAGE_ZABBIX_PROXY)),y)
ZABBIX_DEPENDENCIES += sqlite fping
ZABBIX_CONF_OPT += \
	--enable-proxy \
	--with-sqlite3="$(STAGING_DIR)/usr"

 ifeq ($(strip $(BR2_PACKAGE_CURL)),y)
ZABBIX_DEPENDENCIES += libcurl
ZABBIX_CONF_OPT += \
	--with-libcurl="$(STAGING_DIR)/usr/bin/curl-config"
 endif

 ifeq ($(strip $(BR2_PACKAGE_NETSNMP)),y)
ZABBIX_DEPENDENCIES += netsnmp
ZABBIX_CONF_OPT += \
	--with-net-snmp="$(STAGING_DIR)/usr/bin/net-snmp-config"
 endif
endif

define ZABBIX_CONFIGURE_CMDS
        (cd $(@D); \
                $(TARGET_CONFIGURE_ARGS) \
                $(TARGET_CONFIGURE_OPTS) \
                ./configure \
		--target=$(GNU_TARGET_NAME) \
		--host=$(GNU_TARGET_NAME) \
        	--build=$(GNU_HOST_NAME) \
		--prefix=/usr \
		--exec-prefix=/usr \
		--sysconfdir=/etc \
		$(ZABBIX_CONF_OPT) \
        )
endef

define ZABBIX_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/src/zabbix_agent/zabbix_agentd $(TARGET_DIR)/usr/bin/zabbix_agentd
	if [ -f $(@D)/src/zabbix_proxy/zabbix_proxy ]; then \
	  $(INSTALL) -m 0755 -D $(@D)/src/zabbix_proxy/zabbix_proxy $(TARGET_DIR)/usr/bin/zabbix_proxy ; \
          ln -sf /tmp/etc/zabbix_proxy.conf $(TARGET_DIR)/etc/zabbix_proxy.conf ; \
	fi
        $(INSTALL) -m 0755 -D package/zabbix/zabbix.init $(TARGET_DIR)/etc/init.d/zabbix
        ln -sf /tmp/etc/zabbix_agentd.conf $(TARGET_DIR)/etc/zabbix_agentd.conf
endef

define ZABBIX_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/zabbix_agentd
	rm -f $(TARGET_DIR)/usr/bin/zabbix_proxy
        rm -f $(TARGET_DIR)/etc/init.d/zabbix
        rm -f $(TARGET_DIR)/etc/zabbix_agentd.conf
        rm -f $(TARGET_DIR)/etc/zabbix_proxy.conf
endef

$(eval $(call AUTOTARGETS,package,zabbix))
