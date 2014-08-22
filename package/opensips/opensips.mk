#############################################################
#
# opensips
#
##############################################################

OPENSIPS_VERSION = 1.11.2
OPENSIPS_SOURCE = opensips-$(OPENSIPS_VERSION)_src.tar.gz
OPENSIPS_SITE = http://www.opensips.org/pub/opensips/$(OPENSIPS_VERSION)/src
OPENSIPS_DEPENDENCIES = openssl

OPENSIPS_INCLUDE_MODULES =

ifeq ($(strip $(BR2_PACKAGE_UNIXODBC)),y)
OPENSIPS_DEPENDENCIES += unixodbc
OPENSIPS_INCLUDE_MODULES += db_unixodbc
endif

OPENSIPS_EXCLUDE_MODULES = siptrace sipcapture cachedb_couchbase cachedb_memcached cachedb_cassandra cachedb_redis \
	cachedb_mongodb db_berkeley db_oracle db_perlvdb db_unixodbc event_rabbitmq identity jabber json ldap lua mi_xmlrpc osp \
	perl perlvdb python h350 snmpstats sngtc aaa_radius carrierroute db_http db_mysql db_postgres mmgeoip httpd \
	b2b_logic cpl-c xmpp rls xcap_client presence presence_xml presence_mwi presence_dialoginfo \
	pua pua_bla pua_mi pua_usrloc pua_xmpp pua_dialoginfo regex dialplan mi_http pi_http cachedb_sql \
	mi_json presence_callinfo presence_xcapdiff

define OPENSIPS_BUILD_CMDS
	$(TARGET_CONFIGURE_OPTS) \
	LOCALBASE="$(STAGING_DIR)/usr" \
	SYSBASE="$(STAGING_DIR)/usr" \
	CROSS_COMPILE=true \
	TLS=1 \
	CFLAGS='$(TARGET_CFLAGS)' \
	LDFLAGS='$(TARGET_LDFLAGS)' \
	$(MAKE) -C $(@D) \
		prefix="" \
		bin-prefix=/usr/ \
		cfg-prefix=/ \
		ARCH="i386" \
		OS="linux" \
		include_modules="$(OPENSIPS_INCLUDE_MODULES)" \
		exclude_modules="$(OPENSIPS_EXCLUDE_MODULES)" \
		all
endef

define OPENSIPS_INSTALL_TARGET_CMDS
	$(INSTALL) -s -D -m 0755 $(@D)/opensips $(TARGET_DIR)/usr/sbin/opensips
	# Install modules
	(for i in `find $(@D)/modules -name \*.so`; \
	do $(INSTALL) -s -D -m 0755 "$$i" $(TARGET_DIR)/usr/lib/opensips/modules/`basename "$$i"`; done)
	# Install opensipsctl
	$(INSTALL) -D -m 0640 $(@D)/scripts/opensipsctl.base $(TARGET_DIR)/usr/lib/opensips/opensipsctl/opensipsctl.base
	$(SED) 's:/usr/local/etc/opensips:/etc/opensips:g' \
		$(TARGET_DIR)/usr/lib/opensips/opensipsctl/opensipsctl.base
	$(INSTALL) -D -m 0640 $(@D)/scripts/opensipsctl.ctlbase $(TARGET_DIR)/usr/lib/opensips/opensipsctl/opensipsctl.ctlbase
	$(INSTALL) -D -m 0640 $(@D)/scripts/opensipsctl.fifo $(TARGET_DIR)/usr/lib/opensips/opensipsctl/opensipsctl.fifo
	$(INSTALL) -D -m 0755 $(@D)/scripts/opensipsctl $(TARGET_DIR)/usr/sbin/opensipsctl
	$(SED) 's:/bin/sh:/bin/bash:' \
	    -e 's:/usr/local/etc/opensips:/etc/opensips:g' \
	    -e 's:/usr/local/lib/opensips:/usr/lib/opensips:g' \
		$(TARGET_DIR)/usr/sbin/opensipsctl
	$(INSTALL) -D -m 0640 $(@D)/scripts/opensipsctlrc $(TARGET_DIR)/stat/etc/opensips/opensipsctlrc
	$(INSTALL) -D -m 0640 package/opensips/opensips.cfg $(TARGET_DIR)/stat/etc/opensips/opensips.cfg
	$(INSTALL) -D -m 0755 package/opensips/opensips.init $(TARGET_DIR)/etc/init.d/opensips
	ln -snf /tmp/etc/opensips $(TARGET_DIR)/etc/opensips
	ln -sf ../../init.d/opensips $(TARGET_DIR)/etc/runlevels/default/S58opensips
	ln -sf ../../init.d/opensips $(TARGET_DIR)/etc/runlevels/default/K02opensips
endef

define OPENSIPS_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/opensips
	rm -rf $(TARGET_DIR)/usr/lib/opensips
	rm -f $(TARGET_DIR)/usr/sbin/opensipsctl.base
	rm -f $(TARGET_DIR)/usr/sbin/opensipsctl
	rm -rf $(TARGET_DIR)/stat/etc/opensips
	rm -f $(TARGET_DIR)/etc/init.d/opensips
	rm -f $(TARGET_DIR)/etc/opensips
	rm -f $(TARGET_DIR)/etc/runlevels/default/S58opensips
	rm -f $(TARGET_DIR)/etc/runlevels/default/K02opensips
endef

$(eval $(call GENTARGETS,package,opensips))
