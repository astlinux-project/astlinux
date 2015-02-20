#############################################################
#
# netsnmp
#
#############################################################

NETSNMP_VERSION = 5.7.3
NETSNMP_SITE = http://downloads.sourceforge.net/project/net-snmp/net-snmp/$(NETSNMP_VERSION)
NETSNMP_SOURCE = net-snmp-$(NETSNMP_VERSION).tar.gz
NETSNMP_INSTALL_STAGING = YES
NETSNMP_CONF_ENV = ac_cv_NETSNMP_CAN_USE_SYSCTL=no
NETSNMP_CONF_OPT = \
	--with-persistent-directory=/var/lib/snmp \
	--disable-static \
	--with-defaults \
	--enable-mini-agent \
	--without-rpm \
	--with-logfile=none \
	--without-kmem-usage \
	--enable-as-needed \
	--disable-debugging \
	--without-perl-modules \
	--disable-embedded-perl \
	--disable-perl-cc-checks \
	--disable-scripts \
	--with-default-snmp-version="1" \
	--enable-silent-libtool \
	--enable-mfd-rewrites \
	--with-sys-contact="root@localhost" \
	--with-sys-location="Unknown" \
	--with-mib-modules="host ucd-snmp/dlmod agentx" \
	--with-out-mib-modules="disman/event disman/schedule utilities" \
	--disable-manuals
NETSNMP_MAKE = $(MAKE1)
NETSNMP_BLOAT_MIBS = BRIDGE DISMAN-EVENT DISMAN-SCHEDULE DISMAN-SCRIPT EtherLike RFC-1215 RFC1155-SMI RFC1213 SCTP SMUX

ifeq ($(BR2_ENDIAN),"BIG")
	NETSNMP_CONF_OPT += --with-endianness=big
else
	NETSNMP_CONF_OPT += --with-endianness=little
endif

# OpenSSL
ifeq ($(BR2_PACKAGE_OPENSSL),y)
	NETSNMP_DEPENDENCIES += openssl
	NETSNMP_CONF_OPT += \
		--with-openssl=$(STAGING_DIR)/usr/include/openssl
else
	NETSNMP_CONF_OPT += --without-openssl
endif

# Remove IPv6 MIBs if there's no IPv6
ifneq ($(BR2_INET_IPV6),y)
define NETSNMP_REMOVE_MIBS_IPV6
	rm -f $(TARGET_DIR)/usr/share/snmp/mibs/IPV6*
endef
else
	NETSNMP_CONF_OPT += --enable-ipv6
endif

define NETSNMP_INSTALL_TARGET_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) -C $(@D) \
		DESTDIR=$(TARGET_DIR) install
	$(INSTALL) -D -m 0755 package/netsnmp/netsnmp.init \
		$(TARGET_DIR)/etc/init.d/snmpd
	ln -snf /tmp/etc/snmp $(TARGET_DIR)/etc/snmp
	for mib in $(NETSNMP_BLOAT_MIBS); do \
		rm -f $(TARGET_DIR)/usr/share/snmp/mibs/$$mib-MIB.txt; \
	done
	$(NETSNMP_REMOVE_MIBS_IPV6)
endef

define NETSNMP_UNINSTALL_TARGET_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) -C $(@D) \
		DESTDIR=$(TARGET_DIR) uninstall
	rm -f $(TARGET_DIR)/etc/init.d/snmpd
	rm -f $(TARGET_DIR)/usr/lib/libnetsnmp*
endef

define NETSNMP_STAGING_NETSNMP_CONFIG_FIXUP
	$(SED) "s,^prefix=.*,prefix=\'$(STAGING_DIR)/usr\',g" \
		-e "s,^exec_prefix=.*,exec_prefix=\'$(STAGING_DIR)/usr\',g" \
		-e "s,^includedir=.*,includedir=\'$(STAGING_DIR)/usr/include\',g" \
		-e "s,^libdir=.*,libdir=\'$(STAGING_DIR)/usr/lib\',g" \
		$(STAGING_DIR)/usr/bin/net-snmp-config
endef
NETSNMP_POST_INSTALL_STAGING_HOOKS += NETSNMP_STAGING_NETSNMP_CONFIG_FIXUP

define NETSNMP_TARGET_REMOVE_NETSNMP_CONFIG
        rm -f $(TARGET_DIR)/usr/bin/net-snmp-config
endef
NETSNMP_POST_INSTALL_TARGET_HOOKS += NETSNMP_TARGET_REMOVE_NETSNMP_CONFIG

$(eval $(call AUTOTARGETS,package,netsnmp))
