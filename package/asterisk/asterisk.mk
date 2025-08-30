#############################################################
#
# asterisk
#
##############################################################

ifeq ($(BR2_PACKAGE_ASTERISK_v18),y)
ASTERISK_VERSION := 18.26.4
ASTERISK_LABEL :=
else
 ifeq ($(BR2_PACKAGE_ASTERISK_v20),y)
ASTERISK_VERSION := 20.15.2
ASTERISK_LABEL :=
 else
ASTERISK_VERSION := 16.30.0
ASTERISK_LABEL := se
 endif
endif
ASTERISK_SOURCE := asterisk-$(ASTERISK_VERSION).tar.gz
ASTERISK_SITE := https://downloads.asterisk.org/pub/telephony/asterisk/releases
ASTERISK_DIR := $(BUILD_DIR)/asterisk-$(ASTERISK_VERSION)
ASTERISK_BINARY := main/asterisk
ASTERISK_TARGET_BINARY := usr/sbin/asterisk
ASTERISK_EXTRAS :=
ASTERISK_CONFIGURE_ENV :=
ASTERISK_CONFIGURE_ARGS :=
ASTERISK_MODULE_DIR := usr/lib/asterisk/modules

# $(call ndots start,end,dotted-string)
dot:=.
empty:=
space:=$(empty) $(empty)
ndots = $(subst $(space),$(dot),$(wordlist $(1),$(2),$(subst $(dot),$(space),$3)))
##
ASTERISK_VERSION_SINGLE := $(call ndots,1,1,$(ASTERISK_VERSION))
ASTERISK_VERSION_TUPLE := $(call ndots,1,2,$(ASTERISK_VERSION))

ASTERISK_GLOBAL_MAKEOPTS := $(BASE_DIR)/../project/astlinux/asterisk.makeopts-$(ASTERISK_VERSION_SINGLE)

ASTERISK_CONFIGURE_ENV += \
			USE_GETIFADDRS=yes

ASTERISK_LIBS:=

ifeq ($(strip $(BR2_PACKAGE_OPENSSL)),y)
ASTERISK_LIBS += -lssl
endif

ifeq ($(strip $(BR2_PACKAGE_ZLIB)),y)
ASTERISK_LIBS += -lz
endif

ASTERISK_CONFIGURE_ARGS+= \
			--without-sdl

ASTERISK_CONFIGURE_ARGS+= \
			--without-cap

ifeq ($(strip $(BR2_PACKAGE_LIBEDIT)),y)
ASTERISK_EXTRAS+=libedit
ASTERISK_CONFIGURE_ARGS+= \
			--with-libedit="$(STAGING_DIR)/usr"
else
ASTERISK_CONFIGURE_ARGS+= \
			--without-libedit
endif

ifeq ($(strip $(BR2_PACKAGE_LIBXML2)),y)
ASTERISK_EXTRAS+=libxml2
ASTERISK_CONFIGURE_ARGS+= \
			--with-libxml2
ASTERISK_CONFIGURE_ENV+= \
			CONFIG_LIBXML2="$(STAGING_DIR)/usr/bin/xml2-config"
else
ASTERISK_CONFIGURE_ARGS+= \
			--disable-xmldoc
endif

ASTERISK_CONFIGURE_ARGS+= \
			--without-ldap \
			--without-lua

ifeq ($(strip $(BR2_PACKAGE_IKSEMEL)),y)
ASTERISK_EXTRAS+=iksemel
ASTERISK_CONFIGURE_ARGS+= \
			--with-iksemel="$(STAGING_DIR)/usr"
endif

ifeq ($(strip $(BR2_PACKAGE_LIBPRI)),y)
ASTERISK_EXTRAS+=libpri
ASTERISK_CONFIGURE_ARGS+= \
			--with-pri="$(STAGING_DIR)/usr"
endif

ifeq ($(strip $(BR2_PACKAGE_LIBSRTP)),y)
ASTERISK_EXTRAS+=libsrtp
ASTERISK_CONFIGURE_ARGS+= \
			--with-srtp="$(STAGING_DIR)/usr"
endif

ifeq ($(strip $(BR2_PACKAGE_UW_IMAP)),y)
ASTERISK_EXTRAS+=uw-imap
ASTERISK_CONFIGURE_ARGS+= \
			--with-imap="$(BUILD_DIR)/uw-imap-2007f"
endif

ifeq ($(strip $(BR2_PACKAGE_NETSNMP)),y)
ASTERISK_EXTRAS+=netsnmp
ASTERISK_CONFIGURE_ARGS+= \
			--with-netsnmp
ASTERISK_CONFIGURE_ENV+= \
			CONFIG_NETSNMP="$(STAGING_DIR)/usr/bin/net-snmp-config"
else
ASTERISK_CONFIGURE_ARGS+= \
			--without-netsnmp
endif

ifeq ($(strip $(BR2_PACKAGE_MYSQL_CLIENT)),y)
ASTERISK_EXTRAS+=mysql_client
ASTERISK_CONFIGURE_ARGS+= \
			--with-mysqlclient
ASTERISK_CONFIGURE_ENV+= \
			CONFIG_MYSQLCLIENT="$(STAGING_DIR)/usr/bin/mysql_config"
endif

ifeq ($(strip $(BR2_PACKAGE_UNIXODBC)),y)
ASTERISK_EXTRAS+=unixodbc
ASTERISK_CONFIGURE_ARGS+= \
			--with-unixodbc="$(STAGING_DIR)/usr"
endif

ifeq ($(strip $(BR2_PACKAGE_ALSA_LIB)),y)
ASTERISK_EXTRAS+=alsa-lib
ASTERISK_CONFIGURE_ARGS+= \
			--with-asound="$(STAGING_DIR)/usr"
endif

ifeq ($(strip $(BR2_PACKAGE_SPANDSP)),y)
ASTERISK_EXTRAS+=spandsp
endif

ifeq ($(strip $(BR2_PACKAGE_DAHDI_LINUX)),y)
ASTERISK_EXTRAS+=dahdi-tools
ASTERISK_CONFIGURE_ARGS+= \
			--with-dahdi="$(STAGING_DIR)/usr" \
			--with-tonezone="$(STAGING_DIR)/usr"
else
ASTERISK_CONFIGURE_ARGS+= \
			--without-dahdi \
			--without-tonezone
endif

ifeq ($(strip $(BR2_PACKAGE_SQLITE)),y)
ASTERISK_EXTRAS+=sqlite
ASTERISK_CONFIGURE_ARGS+= \
                        --with-sqlite3="$(STAGING_DIR)/usr"
endif

ifeq ($(strip $(BR2_PACKAGE_CURL)),y)
ASTERISK_EXTRAS+=libcurl
ASTERISK_CONFIGURE_ARGS+= \
			--with-libcurl="$(STAGING_DIR)"
ASTERISK_CONFIGURE_ENV+= \
			LIBCURL="-lcurl -lz -lssl" \
			_libcurl_config="$(STAGING_DIR)/usr/bin/curl-config"
endif

ifeq ($(strip $(BR2_PACKAGE_NEON)),y)
 ifeq ($(strip $(BR2_PACKAGE_LIBICAL)),y)
ASTERISK_EXTRAS+=neon libical
ASTERISK_CONFIGURE_ARGS+= \
			--with-neon \
			--with-neon29 \
			--with-ical
ASTERISK_CONFIGURE_ENV+= \
			CONFIG_NEON="$(STAGING_DIR)/usr/bin/neon-config" \
			CONFIG_NEON29="$(STAGING_DIR)/usr/bin/neon-config"
 endif
endif

ifeq ($(strip $(BR2_PACKAGE_UNBOUND)),y)
ASTERISK_EXTRAS+=unbound
ASTERISK_CONFIGURE_ARGS+= \
                        --with-unbound="$(STAGING_DIR)/usr"
endif

ifeq ($(strip $(BR2_PACKAGE_PJSIP)),y)
ASTERISK_EXTRAS+=pjsip
ASTERISK_CONFIGURE_ARGS+= \
                        --without-pjproject-bundled \
                        --with-pjproject="$(STAGING_DIR)/usr"
else
 ifeq ($(strip $(BR2_PACKAGE_PJSIP_AST20)),y)
ASTERISK_EXTRAS+=pjsip-ast20
ASTERISK_CONFIGURE_ARGS+= \
                        --without-pjproject-bundled \
                        --with-pjproject="$(STAGING_DIR)/usr"
 else
ASTERISK_CONFIGURE_ARGS+= \
                        --without-pjproject-bundled \
                        --without-pjproject
 endif
endif

ifeq ($(strip $(BR2_PACKAGE_JANSSON)),y)
ASTERISK_EXTRAS+=jansson
ASTERISK_CONFIGURE_ARGS+= \
                        --with-jansson="$(STAGING_DIR)/usr"
endif

ifeq ($(strip $(BR2_PACKAGE_LIBURIPARSER)),y)
ASTERISK_EXTRAS+=liburiparser
ASTERISK_CONFIGURE_ARGS+= \
                        --with-uriparser="$(STAGING_DIR)/usr"
endif

ifeq ($(strip $(BR2_PACKAGE_LIBXSLT)),y)
ASTERISK_EXTRAS+=libxslt
ASTERISK_CONFIGURE_ARGS+= \
                        --with-libxslt="$(STAGING_DIR)/usr"
else
ASTERISK_CONFIGURE_ARGS+= \
                        --without-libxslt
endif

$(DL_DIR)/$(ASTERISK_SOURCE):
	$(WGET) -P $(DL_DIR) $(ASTERISK_SITE)/$(ASTERISK_SOURCE)

$(ASTERISK_DIR)/.source: $(DL_DIR)/$(ASTERISK_SOURCE)
	zcat $(DL_DIR)/$(ASTERISK_SOURCE) | tar -C $(BUILD_DIR) $(TAR_OPTIONS) -
	touch $@

$(ASTERISK_DIR)/.patched: $(ASTERISK_DIR)/.source
	toolchain/patch-kernel.sh $(ASTERISK_DIR) package/asterisk/patches/ asterisk-$(ASTERISK_VERSION_SINGLE)$(ASTERISK_LABEL)-\*.patch
	toolchain/patch-kernel.sh $(ASTERISK_DIR) package/asterisk/patches/ asterisk-$(ASTERISK_VERSION_TUPLE)-\*.patch
	touch $@

$(ASTERISK_DIR)/.configured: $(ASTERISK_DIR)/.patched | host-automake host-pkg-config host-ncurses host-bison host-flex host-libxml2 \
			ncurses zlib openssl libtool util-linux $(ASTERISK_EXTRAS)
	(cd $(ASTERISK_DIR); rm -rf config.cache configure; \
		$(HOST_CONFIGURE_OPTS) \
		./bootstrap.sh; \
		$(TARGET_CONFIGURE_OPTS) \
		./configure \
		--target=$(GNU_TARGET_NAME) \
		--host=$(GNU_TARGET_NAME) \
		--build=$(GNU_HOST_NAME) \
		--prefix=/usr \
		--exec-prefix=/usr \
		--datadir=/usr/share \
		--sysconfdir=/etc \
		$(ASTERISK_CONFIGURE_ARGS) \
		$(ASTERISK_CONFIGURE_ENV) \
		CFLAGS='$(TARGET_CFLAGS)' \
		CPPFLAGS='$(TARGET_CFLAGS)' \
		LIBS='$(ASTERISK_LIBS)' \
	)
	(cd $(ASTERISK_DIR)/menuselect; \
		$(HOST_CONFIGURE_OPTS) \
		./configure \
	)
	$(HOST_MAKE_ENV) LD_RUN_PATH="$(HOST_DIR)/usr/lib" \
	$(MAKE) -C $(ASTERISK_DIR)/menuselect menuselect
ifeq ($(strip $(BR2_PACKAGE_ASTERISK_MENUSELECT)),y)
	$(HOST_MAKE_ENV) LD_RUN_PATH="$(HOST_DIR)/usr/lib" \
	$(MAKE) -C $(ASTERISK_DIR) \
		GLOBAL_MAKEOPTS=$(ASTERISK_GLOBAL_MAKEOPTS) \
		USER_MAKEOPTS= \
		menuselect
else
	$(HOST_MAKE_ENV) LD_RUN_PATH="$(HOST_DIR)/usr/lib" \
	$(MAKE) -C $(ASTERISK_DIR) \
		GLOBAL_MAKEOPTS=$(ASTERISK_GLOBAL_MAKEOPTS) \
		USER_MAKEOPTS= \
		menuselect.makeopts
 ifeq ($(strip $(BR2_PACKAGE_MYSQL_CLIENT)),y)
	(cd $(ASTERISK_DIR); \
		menuselect/menuselect --enable app_mysql --enable cdr_mysql --enable res_config_mysql menuselect.makeopts; \
	)
 endif
 ifeq ($(strip $(BR2_PACKAGE_UW_IMAP)),y)
	(cd $(ASTERISK_DIR); \
		menuselect/menuselect --enable IMAP_STORAGE menuselect.makeopts; \
	)
 endif
 ifeq ($(ASTERISK_VERSION_SINGLE),20)
	## Asterisk 20.x version
	(cd $(ASTERISK_DIR); \
		menuselect/menuselect --enable chan_sip menuselect.makeopts; \
	)
 else
	## Asterisk 16.x and 18.x versions
	(cd $(ASTERISK_DIR); \
		menuselect/menuselect --enable res_pktccops --disable app_dahdiras menuselect.makeopts; \
	)
 endif
	## All Asterisk versions
	(cd $(ASTERISK_DIR); \
		menuselect/menuselect --enable app_meetme --enable app_page --enable app_macro menuselect.makeopts; \
		menuselect/menuselect --disable res_stir_shaken menuselect.makeopts; \
		menuselect/menuselect --disable CORE-SOUNDS-EN-GSM --disable MOH-OPSOUND-WAV menuselect.makeopts; \
		menuselect/menuselect --disable BUILD_NATIVE menuselect.makeopts; \
	)
 ifneq ($(strip $(BR2_PACKAGE_DAHDI_LINUX)),y)
	## Disable DAHDI related modules
	(cd $(ASTERISK_DIR); \
		menuselect/menuselect --disable chan_dahdi --disable codec_dahdi --disable app_meetme menuselect.makeopts; \
		menuselect/menuselect --disable res_timing_dahdi --disable app_flash menuselect.makeopts; \
	)
 endif
endif
	# Don't force a "clean", create .lastclean
	cp -f $(ASTERISK_DIR)/.cleancount $(ASTERISK_DIR)/.lastclean
	touch $@

$(ASTERISK_DIR)/$(ASTERISK_BINARY): $(ASTERISK_DIR)/.configured
	$(TARGET_MAKE_ENV) \
	$(MAKE) -C $(ASTERISK_DIR) \
		GLOBAL_MAKEOPTS=$(ASTERISK_GLOBAL_MAKEOPTS) \
		USER_MAKEOPTS= \
		ASTVARRUNDIR=/var/run/asterisk

$(TARGET_DIR)/$(ASTERISK_TARGET_BINARY): $(ASTERISK_DIR)/$(ASTERISK_BINARY)
	$(TARGET_MAKE_ENV) \
	$(MAKE1) -C $(ASTERISK_DIR) \
		GLOBAL_MAKEOPTS=$(ASTERISK_GLOBAL_MAKEOPTS) \
		USER_MAKEOPTS=menuselect.makeopts \
		ASTVARRUNDIR=/var/run/asterisk \
		SOUNDS_CACHE_DIR=$(DL_DIR) \
		DESTDIR=$(TARGET_DIR) install samples install-headers

	mv $(TARGET_DIR)/usr/include/asterisk.h \
	   $(TARGET_DIR)/usr/include/asterisk \
	   $(STAGING_DIR)/usr/include/
	rm -Rf $(TARGET_DIR)/usr/share/man
	$(INSTALL) -D -m 0755 package/asterisk/asterisk.init $(TARGET_DIR)/etc/init.d/asterisk
	$(INSTALL) -D -m 0644 package/asterisk/asterisk.logrotate $(TARGET_DIR)/etc/logrotate.d/asterisk
	$(INSTALL) -D -m 0755 package/asterisk/upgrade-asterisk-sounds $(TARGET_DIR)/usr/sbin/upgrade-asterisk-sounds
	$(INSTALL) -D -m 0755 package/asterisk/safe_asterisk $(TARGET_DIR)/usr/sbin/safe_asterisk
	$(INSTALL) -D -m 0755 package/asterisk/asterisk-sip-monitor $(TARGET_DIR)/usr/sbin/asterisk-sip-monitor
	$(INSTALL) -D -m 0755 package/asterisk/asterisk-sip-monitor-ctrl $(TARGET_DIR)/usr/sbin/asterisk-sip-monitor-ctrl
	$(INSTALL) -D -m 0755 $(ASTERISK_DIR)/contrib/scripts/ast_tls_cert $(TARGET_DIR)/usr/sbin/ast_tls_cert
	mkdir -p $(TARGET_DIR)/stat/var/lib/asterisk/licenses
	mv $(TARGET_DIR)/var/lib/asterisk/* $(TARGET_DIR)/stat/var/lib/asterisk/
	rmdir $(TARGET_DIR)/var/lib/asterisk
	rm -f $(TARGET_DIR)/stat/var/lib/asterisk/astdb
	ln -sf /var/db/astdb $(TARGET_DIR)/stat/var/lib/asterisk/astdb
	rm -f $(TARGET_DIR)/stat/var/lib/asterisk/astdb.sqlite3
	ln -sf /var/db/astdb.sqlite3 $(TARGET_DIR)/stat/var/lib/asterisk/astdb.sqlite3
	mkdir -p $(TARGET_DIR)/stat/var/spool
	mv $(TARGET_DIR)/var/spool/asterisk $(TARGET_DIR)/stat/var/spool/
	touch -c $(TARGET_DIR)/$(ASTERISK_TARGET_BINARY)
	rm -f $(TARGET_DIR)/etc/asterisk/*.old
	rm -f $(TARGET_DIR)/stat/var/lib/asterisk/mohmp3/*
	rm -f $(TARGET_DIR)/usr/sbin/astversion
# Remove unwanted MOH sound files to save space
	rm -f $(TARGET_DIR)/stat/var/lib/asterisk/moh/macroform-robot_dity.*
	rm -f $(TARGET_DIR)/stat/var/lib/asterisk/moh/macroform-cold_day.*
	mv $(TARGET_DIR)/etc/asterisk $(TARGET_DIR)/stat/etc/
	$(INSTALL) -D -m 0644 package/asterisk/logger.conf $(TARGET_DIR)/stat/etc/asterisk/logger.conf

	ln -sf /tmp/etc/asterisk $(TARGET_DIR)/etc/asterisk
	ln -sf /var/tmp/asterisk/sounds/custom-sounds $(TARGET_DIR)/stat/var/lib/asterisk/sounds/custom-sounds
	ln -sf /var/tmp/asterisk/agi-bin/custom-agi $(TARGET_DIR)/stat/var/lib/asterisk/agi-bin/custom-agi

	if [ -d $(TARGET_DIR)/usr/share/snmp/mibs ]; then \
	  $(INSTALL) -D -m 0644 package/asterisk/mibs/ASTERISK-MIB.txt $(TARGET_DIR)/usr/share/snmp/mibs/ ; \
	  $(INSTALL) -D -m 0644 package/asterisk/mibs/DIGIUM-MIB.txt $(TARGET_DIR)/usr/share/snmp/mibs/ ; \
	fi

asterisk: $(TARGET_DIR)/$(ASTERISK_TARGET_BINARY)

asterisk-patch: $(ASTERISK_DIR)/.patched

asterisk-clean:
	rm -Rf $(STAGING_DIR)/usr/include/asterisk
	rm -Rf $(TARGET_DIR)/stat/etc/asterisk
	rm -Rf $(TARGET_DIR)/etc/asterisk
	rm -Rf $(TARGET_DIR)/usr/lib/asterisk
	rm -Rf $(TARGET_DIR)/stat/var/lib/asterisk
	rm -Rf $(TARGET_DIR)/stat/var/spool/asterisk
	rm -Rf $(TARGET_DIR)/var/lib/asterisk
	rm -Rf $(TARGET_DIR)/var/spool/asterisk
	rm -f $(TARGET_DIR)/etc/init.d/asterisk
	rm -f $(TARGET_DIR)/usr/sbin/upgrade-asterisk-sounds
	rm -f $(TARGET_DIR)/usr/sbin/safe_asterisk
	rm -f $(TARGET_DIR)/usr/sbin/asterisk-sip-monitor
	rm -f $(TARGET_DIR)/usr/sbin/asterisk-sip-monitor-ctrl
	rm -f $(TARGET_DIR)/usr/sbin/ast_tls_cert
	rm -f $(TARGET_DIR)/usr/sbin/stereorize
	rm -f $(TARGET_DIR)/usr/sbin/streamplayer
	rm -rf $(STAGING_DIR)/usr/include/asterisk
	rm -Rf $(TARGET_DIR)/stat/var/lib/asterisk
	rm -Rf $(TARGET_DIR)/stat/var/spool/asterisk
	rm -Rf $(TARGET_DIR)/stat/etc/asterisk
	rm -f $(TARGET_DIR)/usr/share/snmp/mibs/ASTERISK-MIB.txt
	rm -f $(TARGET_DIR)/usr/share/snmp/mibs/DIGIUM-MIB.txt
	-$(MAKE) -C $(ASTERISK_DIR) clean
	rm -rf $(BUILD_DIR)/asterisk-$(ASTERISK_VERSION)

asterisk-sounds-clean:
	rm -rf $(TARGET_DIR)/stat/var/lib/asterisk/sounds

asterisk-moh-clean:
	rm -rf $(TARGET_DIR)/stat/var/lib/asterisk/mohmp3
	rm -rf $(TARGET_DIR)/stat/etc/asterisk/musiconhold.conf

asterisk-dirclean:
	rm -rf $(ASTERISK_DIR)

#############################################################
#
# Toplevel Makefile options
#
#############################################################
ifeq ($(strip $(BR2_PACKAGE_ASTERISK)),y)
TARGETS+=asterisk
endif

