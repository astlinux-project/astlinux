#############################################################
#
# asterisk
#
##############################################################
ifeq ($(BR2_PACKAGE_ASTERISK_v1_4),y)
ASTERISK_VERSION := 1.4.43
else
 ifeq ($(BR2_PACKAGE_ASTERISK_v1_6),y)
ASTERISK_VERSION := 1.6.2.21
 else
  ifeq ($(BR2_PACKAGE_ASTERISK_v1_8),y)
ASTERISK_VERSION := 1.8.9.2
  else
ASTERISK_VERSION := 10.0.0
  endif
 endif
endif
ASTERISK_SOURCE := asterisk-$(ASTERISK_VERSION).tar.gz
ASTERISK_SITE := http://downloads.asterisk.org/pub/telephony/asterisk/releases
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
ASTERISK_VERSION_TUPLE := $(call ndots,1,2,$(ASTERISK_VERSION))
ASTERISK_VERSION_TRIPLE := $(call ndots,1,3,$(ASTERISK_VERSION))

ASTERISK_CONFIGURE_ENV += \
			USE_GETIFADDRS=yes

ASTERISK_LIBS:=

ifeq ($(strip $(BR2_PACKAGE_OPENSSL)),y)
ASTERISK_LIBS += -lssl
endif

ifeq ($(strip $(BR2_PACKAGE_ZLIB)),y)
ASTERISK_LIBS += -lz
endif

ifneq ($(ASTERISK_VERSION_TUPLE),1.4)
TARGET_CONFIGURE_OPTS+=ac_cv_pthread_rwlock_timedwrlock=no

ASTERISK_CONFIGURE_ARGS+= \
			--without-sdl
 ifeq ($(strip $(BR2_PACKAGE_LIBXML2)),y)
ASTERISK_EXTRAS+=libxml2
ASTERISK_CONFIGURE_ARGS+= \
			--with-libxml2="$(STAGING_DIR)/usr" 
 else
ASTERISK_CONFIGURE_ARGS+= \
			--disable-xmldoc
 endif
endif

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
 ifneq ($(ASTERISK_VERSION_TUPLE),1.4)
 ifneq ($(ASTERISK_VERSION_TUPLE),1.6)
ASTERISK_EXTRAS+=libsrtp
ASTERISK_CONFIGURE_ARGS+= \
			--with-srtp="$(STAGING_DIR)/usr" 
 endif
 endif
endif

ifeq ($(strip $(BR2_PACKAGE_UW-IMAP)),y)
ASTERISK_EXTRAS+=uw-imap
ASTERISK_CONFIGURE_ARGS+= \
			--with-imap="$(BUILD_DIR)/imap-2007e" 
endif

#ifeq ($(strip $(BR2_PACKAGE_NETSNMP)),y)
#ASTERISK_EXTRAS+=netsnmp
#ASTERISK_CONFIGURE_ARGS+= \
#			--with-netsnmp="$(STAGING_DIR)"
#ASTERISK_CONFIGURE_ENV+= \
#			CONFIG_NETSNMP="$(STAGING_DIR)/usr/bin/net-snmp-config"
#endif

ifeq ($(strip $(BR2_PACKAGE_MYSQL_CLIENT)),y)
ASTERISK_EXTRAS+=mysql_client
ASTERISK_CONFIGURE_ARGS+= \
			--with-mysqlclient="$(STAGING_DIR)/usr"
endif

ifeq ($(strip $(BR2_PACKAGE_UNIXODBC)),y)
ASTERISK_EXTRAS+=unixodbc
ASTERISK_CONFIGURE_ARGS+= \
			--with-odbc="$(STAGING_DIR)/usr"
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
endif

ifneq ($(ASTERISK_VERSION_TUPLE),1.4)
  ifeq ($(strip $(BR2_PACKAGE_SQLITE)),y)
  ASTERISK_EXTRAS+=sqlite
  ASTERISK_CONFIGURE_ARGS+= \
                        --with-sqlite3="$(STAGING_DIR)/usr"
  endif
endif

ifeq ($(strip $(BR2_PACKAGE_CURL)),y)
ASTERISK_EXTRAS+=curl
ASTERISK_CONFIGURE_ARGS+= \
			--with-curl="$(STAGING_DIR)" \
			--with-libcurl="$(STAGING_DIR)"
ASTERISK_CONFIGURE_ENV+= \
			LIBCURL="-lcurl -lz -lssl" \
			_libcurl_config="$(STAGING_DIR)/usr/bin/curl-config"
endif

$(DL_DIR)/$(ASTERISK_SOURCE):
	$(WGET) -P $(DL_DIR) $(ASTERISK_SITE)/$(ASTERISK_SOURCE)

$(ASTERISK_DIR)/.source: $(DL_DIR)/$(ASTERISK_SOURCE)
	zcat $(DL_DIR)/$(ASTERISK_SOURCE) | tar -C $(BUILD_DIR) $(TAR_OPTIONS) -
	touch $@

$(ASTERISK_DIR)/.patched: $(ASTERISK_DIR)/.source
	toolchain/patch-kernel.sh $(ASTERISK_DIR) package/asterisk/ asterisk-$(ASTERISK_VERSION_TUPLE)-\*.patch
	toolchain/patch-kernel.sh $(ASTERISK_DIR) package/asterisk/ asterisk-$(ASTERISK_VERSION_TRIPLE)-\*.patch

ifeq ($(strip $(BR2_PACKAGE_SPANDSP)),y)
 ifeq ($(strip $(BR2_PACKAGE_SPANDSP_APP_FAX)),y)
	toolchain/patch-kernel.sh $(ASTERISK_DIR) package/asterisk/ spandsp.patch
	cp -p package/asterisk/app_fax.c $(ASTERISK_DIR)/apps
 endif
endif

ifeq ($(strip $(BR2_PACKAGE_ASTERISK_ILBC)),y)
	zcat package/asterisk/ilbc-codec.tar.gz | tar -C $(ASTERISK_DIR) $(TAR_OPTIONS) -
	toolchain/patch-kernel.sh $(ASTERISK_DIR) package/asterisk/ ilbc-codec-\*.patch
	$(SED) 's:<defaultenabled>no</defaultenabled>:<defaultenabled>yes</defaultenabled>:' \
		$(ASTERISK_DIR)/codecs/codec_ilbc.c
endif

	cp -p package/asterisk/Makefile.module $(ASTERISK_DIR)/Makefile.module

	touch $@

$(ASTERISK_DIR)/.configured: $(ASTERISK_DIR)/.patched | libelf ncurses zlib \
				openssl libtool $(ASTERISK_EXTRAS)
	(cd $(ASTERISK_DIR); rm -rf config.cache configure; \
		./bootstrap.sh; \
		$(TARGET_CONFIGURE_OPTS) \
		PATH=$(STAGING_DIR)/bin:$$PATH \
		./configure \
		--target=$(GNU_TARGET_NAME) \
		--host=$(GNU_TARGET_NAME) \
		--build=$(GNU_HOST_NAME) \
		--prefix=/usr \
		--exec-prefix=/usr \
		--datadir=/usr/share \
		--sysconfdir=/etc \
		--without-pwlib \
		--with-ltdl=$(STAGING_DIR)/usr \
		$(ASTERISK_CONFIGURE_ARGS) \
		$(ASTERISK_CONFIGURE_ENV) \
		CFLAGS='$(TARGET_CFLAGS)' \
		CPPFLAGS='$(TARGET_CFLAGS)' \
		LIBS='$(ASTERISK_LIBS)' \
	)
	PATH=$(STAGING_DIR)/bin:$$PATH \
	$(MAKE) -C $(ASTERISK_DIR)/menuselect menuselect
ifeq ($(strip $(BR2_PACKAGE_ASTERISK_MENUSELECT)),y)
	PATH=$(STAGING_DIR)/bin:$$PATH \
	$(MAKE) -C $(ASTERISK_DIR) \
		GLOBAL_MAKEOPTS=$(BASE_DIR)/../project/astlinux/asterisk.makeopts \
		USER_MAKEOPTS= \
		menuselect
else
	PATH=$(STAGING_DIR)/bin:$$PATH \
	$(MAKE) -C $(ASTERISK_DIR) \
		GLOBAL_MAKEOPTS=$(BASE_DIR)/../project/astlinux/asterisk.makeopts \
		USER_MAKEOPTS= \
		menuselect.makeopts
 ifeq ($(strip $(BR2_PACKAGE_MYSQL_CLIENT)),y)
	(cd $(ASTERISK_DIR); \
		menuselect/menuselect --enable app_mysql --enable cdr_mysql --enable res_config_mysql menuselect.makeopts; \
	)
 endif
endif
	touch $@

$(ASTERISK_DIR)/$(ASTERISK_BINARY): $(ASTERISK_DIR)/.configured
	#cp $(STAGING_DIR)/include/dlfcn.h $(STAGING_DIR)/usr/include/dlfcn.h # Can I do this?
	PATH=$(STAGING_DIR)/bin:$$PATH \
	$(MAKE) -C $(ASTERISK_DIR) \
		GLOBAL_MAKEOPTS=$(BASE_DIR)/../project/astlinux/asterisk.makeopts \
		USER_MAKEOPTS= \
		ASTVARRUNDIR=/var/run/asterisk

ifeq ($(strip $(BR2_PACKAGE_UW-IMAP)),y)
	mv $(ASTERISK_DIR)/apps/app_voicemail.so $(ASTERISK_DIR)/apps/app_voicemail-file.so
	rm $(ASTERISK_DIR)/apps/app_voicemail.o
	sed -i -e 's|^MENUSELECT_OPTS_app_voicemail=.*$$|MENUSELECT_OPTS_app_voicemail=IMAP_STORAGE|' $(ASTERISK_DIR)/menuselect.makeopts
	PATH=$(STAGING_DIR)/bin:$$PATH \
	$(MAKE) -C $(ASTERISK_DIR) \
		GLOBAL_MAKEOPTS=$(BASE_DIR)/../project/astlinux/asterisk.makeopts-imap \
		USER_MAKEOPTS=menuselect.makeopts \
		ASTVARRUNDIR=/var/run/asterisk
	mv $(ASTERISK_DIR)/apps/app_voicemail.so $(ASTERISK_DIR)/apps/app_voicemail_imap.so
	mv $(ASTERISK_DIR)/apps/app_voicemail-file.so $(ASTERISK_DIR)/apps/app_voicemail.so
	sed -i -e 's|^MENUSELECT_OPTS_app_voicemail=IMAP_STORAGE|MENUSELECT_OPTS_app_voicemail=FILE_STORAGE|' $(ASTERISK_DIR)/menuselect.makeopts
endif

$(TARGET_DIR)/$(ASTERISK_TARGET_BINARY): $(ASTERISK_DIR)/$(ASTERISK_BINARY)
	# mkdir -p $(TARGET_DIR)/$(ASTERISK_MODULE_DIR)
	PATH=$(STAGING_DIR)/bin:$$PATH \
	$(MAKE1) -C $(ASTERISK_DIR) \
		GLOBAL_MAKEOPTS=$(BASE_DIR)/../project/astlinux/asterisk.makeopts \
		USER_MAKEOPTS=menuselect.makeopts \
		ASTVARRUNDIR=/var/run/asterisk \
		SOUNDS_CACHE_DIR=$(DL_DIR) \
		DESTDIR=$(TARGET_DIR) install samples
ifeq ($(strip $(BR2_PACKAGE_UW-IMAP)),y)
	cp -p $(ASTERISK_DIR)/apps/app_voicemail_imap.so $(TARGET_DIR)/$(ASTERISK_MODULE_DIR)/.
endif

	mv $(TARGET_DIR)/usr/include/asterisk.h \
	   $(TARGET_DIR)/usr/include/asterisk \
	   $(STAGING_DIR)/usr/include/
	rm -Rf $(TARGET_DIR)/usr/share/man
	$(INSTALL) -D -m 0755 package/asterisk/asterisk.init $(TARGET_DIR)/etc/init.d/asterisk
	$(INSTALL) -D -m 0755 package/asterisk/upgrade-asterisk-sounds $(TARGET_DIR)/usr/sbin/upgrade-asterisk-sounds
	$(INSTALL) -D -m 0755 package/asterisk/safe_asterisk $(TARGET_DIR)/usr/sbin/safe_asterisk
	$(INSTALL) -D -m 0755 package/asterisk/asterisk-sip-monitor $(TARGET_DIR)/usr/sbin/asterisk-sip-monitor
	$(INSTALL) -D -m 0755 package/asterisk/asterisk-sip-monitor-ctrl $(TARGET_DIR)/usr/sbin/asterisk-sip-monitor-ctrl
ifneq ($(ASTERISK_VERSION_TUPLE),1.4)
ifneq ($(ASTERISK_VERSION_TUPLE),1.6)
	$(INSTALL) -D -m 0755 $(ASTERISK_DIR)/contrib/scripts/ast_tls_cert $(TARGET_DIR)/usr/sbin/ast_tls_cert
endif
endif
	mkdir -p $(TARGET_DIR)/stat/var/lib/asterisk
	mv $(TARGET_DIR)/var/lib/asterisk/* $(TARGET_DIR)/stat/var/lib/asterisk/
	rmdir $(TARGET_DIR)/var/lib/asterisk
	rm -f $(TARGET_DIR)/stat/var/lib/asterisk/astdb
	ln -sf /tmp/astdb $(TARGET_DIR)/stat/var/lib/asterisk/astdb
	mkdir -p $(TARGET_DIR)/stat/var/spool
	mv $(TARGET_DIR)/var/spool/asterisk $(TARGET_DIR)/stat/var/spool/
	touch -c $(TARGET_DIR)/$(ASTERISK_TARGET_BINARY)
	rm -f $(TARGET_DIR)/etc/asterisk/*.old
	rm -f $(TARGET_DIR)/stat/var/lib/asterisk/mohmp3/*
# Remove unwanted MOH sound files to save space
	rm -f $(TARGET_DIR)/stat/var/lib/asterisk/moh/macroform-robot_dity.*
	rm -f $(TARGET_DIR)/stat/var/lib/asterisk/moh/macroform-cold_day.*
ifneq ($(wildcard package/asterisk/config/extensions.conf),)
	mkdir -p $(TARGET_DIR)/stat/etc/asterisk
	rsync -a --exclude=".svn" package/asterisk/config/* $(TARGET_DIR)/stat/etc/asterisk/
else
	mv $(TARGET_DIR)/etc/asterisk $(TARGET_DIR)/stat/etc/
endif
	$(INSTALL) -D -m 0755 package/asterisk/logger.conf $(TARGET_DIR)/stat/etc/asterisk/logger.conf

ifeq ($(strip $(BR2_PACKAGE_UW-IMAP)),y)
	cat package/asterisk/voicemail_modules.conf >> $(TARGET_DIR)/stat/etc/asterisk/modules.conf
endif

	chmod -R 750 $(TARGET_DIR)/stat/etc/asterisk
	rm -rf $(TARGET_DIR)/etc/asterisk
	ln -sf /tmp/etc/asterisk $(TARGET_DIR)/etc/asterisk
	ln -sf /mnt/kd/custom-sounds $(TARGET_DIR)/stat/var/lib/asterisk/sounds/custom-sounds

asterisk: $(TARGET_DIR)/$(ASTERISK_TARGET_BINARY)

asterisk-source: $(ASTERISK_DIR)/.patched

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
	-$(MAKE) -C $(ASTERISK_DIR) clean
	rm -rf $(BUILD_DIR)/asterisk
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

