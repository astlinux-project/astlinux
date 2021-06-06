#############################################################
#
# openldap
#
#############################################################

OPENLDAP_VERSION = 2.4.59
OPENLDAP_SOURCE = openldap-$(OPENLDAP_VERSION).tgz
OPENLDAP_SITE = http://www.openldap.org/software/download/OpenLDAP/openldap-release
OPENLDAP_INSTALL_STAGING = YES
OPENLDAP_DEPENDENCIES += openssl

OPENLDAP_UNINSTALL_STAGING_OPT = --version

OPENLDAP_CONF_OPT = \
	--enable-shared \
	--disable-static \
	--disable-debug \
	--enable-syslog \
	--enable-ipv6 \
	--enable-crypt \
	--with-tls \
	--with-yielding_select=yes \
	--without-fetch \
	--without-cyrus-sasl \
	--enable-slapd \
	--enable-mdb \
	--enable-null \
	--disable-local \
	--disable-bdb \
	--disable-hdb \
	--disable-monitor \
	--disable-relay

ifeq ($(BR2_PACKAGE_OPENLDAP_SERVER),y)
define OPENLDAP_INSTALL_TARGET_SERVER
	cp -a $(STAGING_DIR)/etc/openldap/schema $(TARGET_DIR)/etc/openldap/
	$(INSTALL) -D -m 0755 $(STAGING_DIR)/usr/libexec/slapd $(TARGET_DIR)/usr/sbin/
	$(INSTALL) -D -m 0755 $(STAGING_DIR)/usr/bin/ldap* $(TARGET_DIR)/usr/bin/
	$(INSTALL) -m 0755 -D package/openldap/slapd.init $(TARGET_DIR)/etc/init.d/slapd
	$(INSTALL) -m 0444 -D package/openldap/schema/*.schema $(TARGET_DIR)/etc/openldap/schema/
	$(INSTALL) -m 0755 -D package/openldap/scripts/ldap* $(TARGET_DIR)/usr/bin/
	$(INSTALL) -m 0755 -D package/openldap/vcard-export $(TARGET_DIR)/usr/sbin/
	ln -sf /tmp/etc/openldap/slapd.conf $(TARGET_DIR)/etc/openldap/slapd.conf
	ln -sf ../../init.d/slapd $(TARGET_DIR)/etc/runlevels/default/S45slapd
	ln -sf ../../init.d/slapd $(TARGET_DIR)/etc/runlevels/default/K16slapd
endef
endif

define OPENLDAP_INSTALL_TARGET_CMDS
	cp -a $(STAGING_DIR)/usr/lib/libldap*.so* $(TARGET_DIR)/usr/lib/
	cp -a $(STAGING_DIR)/usr/lib/liblber*.so* $(TARGET_DIR)/usr/lib/
	chmod +x $(TARGET_DIR)/usr/lib/libldap*.so* \
		 $(TARGET_DIR)/usr/lib/liblber*.so*
	$(INSTALL) -D -m 0755 $(STAGING_DIR)/usr/bin/ldapsearch $(TARGET_DIR)/usr/bin/ldapsearch
	$(INSTALL) -D -m 0755 $(STAGING_DIR)/usr/bin/ldapwhoami $(TARGET_DIR)/usr/bin/ldapwhoami
	$(INSTALL) -m 0755 -D package/openldap/ldap.init $(TARGET_DIR)/etc/init.d/ldap
	mkdir -p $(TARGET_DIR)/etc/openldap
	ln -sf /tmp/etc/openldap/ldap.conf $(TARGET_DIR)/etc/openldap/ldap.conf
	ln -sf ../../init.d/ldap $(TARGET_DIR)/etc/runlevels/default/S01ldap
	$(OPENLDAP_INSTALL_TARGET_SERVER)
endef

define OPENLDAP_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/lib/libldap*
	rm -f $(TARGET_DIR)/usr/lib/liblber*
	rm -f $(TARGET_DIR)/usr/bin/ldap*
	rm -f $(TARGET_DIR)/etc/init.d/ldap
	rm -f $(TARGET_DIR)/etc/init.d/slapd
	rm -rf $(TARGET_DIR)/etc/openldap
	rm -f $(TARGET_DIR)/etc/runlevels/default/S01ldap
	rm -f $(TARGET_DIR)/etc/runlevels/default/S45slapd
	rm -f $(TARGET_DIR)/etc/runlevels/default/K16slapd
	rm -f $(TARGET_DIR)/usr/sbin/slapd
	rm -f $(TARGET_DIR)/usr/sbin/vcard-export
endef

$(eval $(call AUTOTARGETS,package,openldap))
