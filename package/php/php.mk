#############################################################
#
# php
#
#############################################################

PHP_VERSION = 7.2.34
PHP_SITE = https://secure.php.net/distributions
PHP_SOURCE = php-$(PHP_VERSION).tar.xz
PHP_INSTALL_STAGING = YES
PHP_INSTALL_STAGING_OPT = INSTALL_ROOT=$(STAGING_DIR) install
PHP_INSTALL_TARGET_OPT = INSTALL_ROOT=$(TARGET_DIR) install
PHP_DEPENDENCIES = host-pkg-config

ifeq ($(BR2_PACKAGE_PHP_EXT_TIMEZONEDB),y)
PHP_TIMEZONEDB_VERSION = 2025.3
PHP_TIMEZONEDB_SITE = https://astlinux-project.org/files
PHP_TIMEZONEDB_SOURCE = timezonedb-$(PHP_TIMEZONEDB_VERSION).tar.gz

define PHP_TIMEZONEDB_DOWNLOAD
	$(call DOWNLOAD,$(PHP_TIMEZONEDB_SITE),$(PHP_TIMEZONEDB_SOURCE))
endef
PHP_POST_DOWNLOAD_HOOKS += PHP_TIMEZONEDB_DOWNLOAD

define PHP_TIMEZONEDB_EXTRACT
	mkdir -p $(@D)/timezonedb
	$(INFLATE$(suffix $(PHP_TIMEZONEDB_SOURCE))) $(DL_DIR)/$(PHP_TIMEZONEDB_SOURCE) | \
	$(TAR) $(TAR_STRIP_COMPONENTS)=1 -C $(@D)/timezonedb $(TAR_OPTIONS) -
	cp $(@D)/timezonedb/timezonedb.h $(@D)/ext/date/lib/timezonedb.h
endef
PHP_POST_EXTRACT_HOOKS += PHP_TIMEZONEDB_EXTRACT
endif

PHP_CONF_OPT = \
	--mandir=/usr/share/man \
	--infodir=/usr/share/info \
	--disable-all \
	--without-pear \
	--without-iconv \
	--with-config-file-path=/etc \
	--localstatedir=/var \
	--disable-phpdbg \
	--disable-rpath

PHP_CONF_ENV = \
	ac_cv_func_strcasestr=yes \
	EXTRA_LIBS="$(PHP_EXTRA_LIBS)"

# PHP can't be AUTORECONFed the standard way unfortunately
PHP_DEPENDENCIES += host-autoconf host-automake host-libtool
define PHP_BUILDCONF
	cd $(@D) ; $(TARGET_MAKE_ENV) ./buildconf --force
endef
PHP_PRE_CONFIGURE_HOOKS += PHP_BUILDCONF

ifeq ($(BR2_ENDIAN),"BIG")
PHP_CONF_ENV += ac_cv_c_bigendian_php=yes
else
PHP_CONF_ENV += ac_cv_c_bigendian_php=no
endif

PHP_CFLAGS = $(TARGET_CFLAGS)
PHP_CXXFLAGS = $(TARGET_CXXFLAGS)

# We need to force dl "detection"
PHP_CONF_ENV += ac_cv_func_dlopen=yes ac_cv_lib_dl_dlopen=yes
PHP_EXTRA_LIBS += -ldl

PHP_CONF_OPT += $(if $(BR2_PACKAGE_PHP_CLI),,--disable-cli)
PHP_CONF_OPT += $(if $(BR2_PACKAGE_PHP_CGI),,--disable-cgi)
PHP_CONF_OPT += $(if $(BR2_PACKAGE_PHP_FPM),--enable-fpm,--disable-fpm)

### Extensions
PHP_CONF_OPT += \
	$(if $(BR2_PACKAGE_PHP_EXT_SOCKETS),--enable-sockets) \
	$(if $(BR2_PACKAGE_PHP_EXT_POSIX),--enable-posix) \
	$(if $(BR2_PACKAGE_PHP_EXT_SESSION),--enable-session) \
	$(if $(BR2_PACKAGE_PHP_EXT_HASH),--enable-hash) \
	$(if $(BR2_PACKAGE_PHP_EXT_SIMPLEXML),--enable-simplexml) \
	$(if $(BR2_PACKAGE_PHP_EXT_XMLPARSER),--enable-xml) \
	$(if $(BR2_PACKAGE_PHP_EXT_EXIF),--enable-exif) \
	$(if $(BR2_PACKAGE_PHP_EXT_FTP),--enable-ftp) \
	$(if $(BR2_PACKAGE_PHP_EXT_JSON),--enable-json) \
	$(if $(BR2_PACKAGE_PHP_EXT_TOKENIZER),--enable-tokenizer) \
	$(if $(BR2_PACKAGE_PHP_EXT_PCNTL),--enable-pcntl) \
	$(if $(BR2_PACKAGE_PHP_EXT_SYSVMSG),--enable-sysvmsg) \
	$(if $(BR2_PACKAGE_PHP_EXT_SYSVSEM),--enable-sysvsem) \
	$(if $(BR2_PACKAGE_PHP_EXT_SYSVSHM),--enable-sysvshm) \
	$(if $(BR2_PACKAGE_PHP_EXT_ZIP),--enable-zip) \
	$(if $(BR2_PACKAGE_PHP_EXT_CTYPE),--enable-ctype) \
	$(if $(BR2_PACKAGE_PHP_EXT_FILTER),--enable-filter) \
	$(if $(BR2_PACKAGE_PHP_EXT_CALENDAR),--enable-calendar)

ifeq ($(BR2_PACKAGE_PHP_EXT_OPENSSL),y)
PHP_CONF_OPT += --with-openssl=$(STAGING_DIR)/usr
PHP_DEPENDENCIES += openssl
endif

ifeq ($(BR2_PACKAGE_PHP_EXT_CURL),y)
PHP_CONF_OPT += --with-curl=$(STAGING_DIR)/usr
PHP_DEPENDENCIES += libcurl
endif

ifeq ($(BR2_PACKAGE_PHP_EXT_LIBXML2),y)
PHP_CONF_ENV += php_cv_libxml_build_works=yes
PHP_CONF_OPT += --enable-libxml --with-libxml-dir=$(STAGING_DIR)/usr --enable-dom
PHP_DEPENDENCIES += libxml2
endif

ifeq ($(BR2_PACKAGE_PHP_EXT_ZLIB),y)
PHP_CONF_OPT += --with-zlib=$(STAGING_DIR)/usr
PHP_DEPENDENCIES += zlib
endif

ifeq ($(BR2_PACKAGE_PHP_EXT_GETTEXT),y)
PHP_CONF_OPT += --with-gettext=$(STAGING_DIR)/usr
PHP_DEPENDENCIES += $(if $(BR2_NEEDS_GETTEXT),gettext)
endif

ifeq ($(BR2_PACKAGE_PHP_EXT_GMP),y)
PHP_CONF_OPT += --with-gmp=$(STAGING_DIR)/usr
PHP_DEPENDENCIES += gmp
endif

ifeq ($(BR2_PACKAGE_PHP_EXT_READLINE),y)
PHP_CONF_OPT += --with-readline=$(STAGING_DIR)/usr
PHP_DEPENDENCIES += readline
endif

### PDO
ifeq ($(BR2_PACKAGE_PHP_EXT_PDO),y)
PHP_CONF_OPT += --enable-pdo
ifeq ($(BR2_PACKAGE_PHP_EXT_PDO_SQLITE),y)
PHP_CONF_OPT += --with-pdo-sqlite=$(STAGING_DIR)/usr
PHP_DEPENDENCIES += sqlite
PHP_CFLAGS += -DSQLITE_OMIT_LOAD_EXTENSION
ifneq ($(BR2_LARGEFILE),y)
PHP_CFLAGS += -DSQLITE_DISABLE_LFS
endif
endif
ifeq ($(BR2_PACKAGE_PHP_EXT_PDO_MYSQL),y)
PHP_CONF_OPT += --with-pdo-mysql=$(STAGING_DIR)/usr
PHP_DEPENDENCIES += mysql_client
endif
endif

### Use external PCRE if it's available
ifeq ($(BR2_PACKAGE_PCRE),y)
PHP_CONF_OPT += --with-pcre-regex=$(STAGING_DIR)/usr
PHP_DEPENDENCIES += pcre
endif

### LDAP
ifeq ($(BR2_PACKAGE_OPENLDAP),y)
PHP_CONF_OPT += --with-ldap=$(STAGING_DIR)/usr
PHP_DEPENDENCIES += openldap
endif

# Fixup prefix= and exec_prefix= in php-config
define PHP_FIXUP_PHP_CONFIG
	$(SED) 's%^prefix="/usr"%prefix="$(STAGING_DIR)/usr"%' \
		-e 's%^exec_prefix="/usr"%exec_prefix="$(STAGING_DIR)/usr"%' \
		$(STAGING_DIR)/usr/bin/php-config
	$(SED) "/prefix/ s:/usr:$(STAGING_DIR)/usr:" \
		$(STAGING_DIR)/usr/bin/phpize
	$(SED) "/extension_dir/ s:/usr:$(TARGET_DIR)/usr:" \
		$(STAGING_DIR)/usr/bin/php-config
endef

PHP_POST_INSTALL_STAGING_HOOKS += PHP_FIXUP_PHP_CONFIG

define PHP_INSTALL_FIXUP
	mv $(TARGET_DIR)/usr/bin/php-cgi $(TARGET_DIR)/usr/bin/php
	rm -rf $(TARGET_DIR)/usr/lib/php/build
	rm -f $(TARGET_DIR)/usr/bin/phpize
	rm -f $(TARGET_DIR)/usr/bin/php-config
	ln -sf /tmp/etc/php.ini $(TARGET_DIR)/etc/php.ini
endef

PHP_POST_INSTALL_TARGET_HOOKS += PHP_INSTALL_FIXUP

define PHP_UNINSTALL_STAGING_CMDS
	rm -rf $(STAGING_DIR)/usr/include/php
	rm -rf $(STAGING_DIR)/usr/lib/php
	rm -f $(STAGING_DIR)/usr/bin/php*
	rm -f $(STAGING_DIR)/usr/share/man/man1/php*.1
endef

define PHP_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/etc/php.ini
	rm -f $(TARGET_DIR)/usr/bin/php*
endef

PHP_CONF_ENV += CFLAGS="$(PHP_CFLAGS)" CXXFLAGS="$(PHP_CXXFLAGS)"

$(eval $(call AUTOTARGETS,package,php))
