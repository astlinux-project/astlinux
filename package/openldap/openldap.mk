#############################################################
#
# openldap
#
#############################################################

OPENLDAP_VERSION = 2.4.35
OPENLDAP_SOURCE = openldap-$(OPENLDAP_VERSION).tgz
OPENLDAP_SITE = ftp://ftp.openldap.org/pub/OpenLDAP/openldap-release
OPENLDAP_INSTALL_STAGING = YES
OPENLDAP_DEPENDENCIES += openssl

OPENLDAP_UNINSTALL_STAGING_OPT = --version

OPENLDAP_CONF_OPT = \
	--enable-shared \
	--disable-static \
	--disable-debug \
	--enable-syslog \
	--enable-ipv6 \
	--with-tls \
	--with-yielding_select="yes" \
	--without-fetch \
	--without-cyrus-sasl \
	--disable-slapd \
	--disable-local \
	--disable-bdb \
	--disable-hdb \
	--disable-monitor \
	--disable-relay

define OPENLDAP_INSTALL_TARGET_CMDS
	cp -a $(STAGING_DIR)/usr/lib/libldap*.so* $(TARGET_DIR)/usr/lib/
	cp -a $(STAGING_DIR)/usr/lib/liblber*.so* $(TARGET_DIR)/usr/lib/
	$(INSTALL) -D -m 0755 $(STAGING_DIR)/usr/bin/ldapsearch $(TARGET_DIR)/usr/bin/ldapsearch
endef

define OPENLDAP_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/lib/libldap*
	rm -f $(TARGET_DIR)/usr/lib/liblber*
	rm -f $(TARGET_DIR)/usr/bin/ldapsearch
endef

$(eval $(call AUTOTARGETS,package,openldap))
