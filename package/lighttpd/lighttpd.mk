#############################################################
#
# lighttpd
#
#############################################################

LIGHTTPD_VERSION = 1.4.49
LIGHTTPD_SITE = http://download.lighttpd.net/lighttpd/releases-1.4.x
LIGHTTPD_DEPENDENCIES = host-pkg-config
LIGHTTPD_CONF_OPT = \
	--libdir=/usr/lib/lighttpd \
	--libexecdir=/usr/lib \
	--localstatedir=/var \
	$(if $(BR2_LARGEFILE),,--disable-lfs)

LIGHTTPD_CONF_ENV = \
	ac_cv_func_sendfile=no \
	ac_cv_func_sendfile64=no

ifeq ($(BR2_PACKAGE_LIGHTTPD_OPENSSL),y)
LIGHTTPD_DEPENDENCIES += openssl
LIGHTTPD_CONF_OPT += --with-openssl
else
LIGHTTPD_CONF_OPT += --without-openssl
endif

ifeq ($(BR2_PACKAGE_LIGHTTPD_ZLIB),y)
LIGHTTPD_DEPENDENCIES += zlib
LIGHTTPD_CONF_OPT += --with-zlib
else
LIGHTTPD_CONF_OPT += --without-zlib
endif

ifeq ($(BR2_PACKAGE_LIGHTTPD_BZIP2),y)
LIGHTTPD_DEPENDENCIES += bzip2
LIGHTTPD_CONF_OPT += --with-bzip2
else
LIGHTTPD_CONF_OPT += --without-bzip2
endif

ifeq ($(BR2_PACKAGE_LIGHTTPD_PCRE),y)
LIGHTTPD_CONF_ENV += PCRECONFIG=$(STAGING_DIR)/usr/bin/pcre-config
LIGHTTPD_DEPENDENCIES += pcre
LIGHTTPD_CONF_OPT += --with-pcre
else
LIGHTTPD_CONF_OPT += --without-pcre
endif

ifeq ($(BR2_PACKAGE_LIGHTTPD_WEBDAV),y)
LIGHTTPD_DEPENDENCIES += libxml2 sqlite
LIGHTTPD_CONF_OPT += --with-webdav-props --with-webdav-locks
else
LIGHTTPD_CONF_OPT += --without-webdav-props --without-webdav-locks
endif

define LIGHTTPD_INSTALL_INITSCRIPT
	$(INSTALL) -D -m 0644 package/lighttpd/lighttpd.conf $(TARGET_DIR)/stat/etc/lighttpd.conf
	$(INSTALL) -D -m 0644 package/lighttpd/php.ini.conf $(TARGET_DIR)/stat/etc/php.ini.conf
	$(INSTALL) -D -m 0755 package/lighttpd/lighttpd.init $(TARGET_DIR)/etc/init.d/lighttpd
	$(INSTALL) -D -m 0644 package/lighttpd/lighttpd.logrotate $(TARGET_DIR)/etc/logrotate.d/lighttpd
	ln -sf /tmp/etc/lighttpd.conf $(TARGET_DIR)/etc/lighttpd.conf
endef

LIGHTTPD_POST_INSTALL_TARGET_HOOKS += LIGHTTPD_INSTALL_INITSCRIPT

define LIGHTTPD_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/lighttpd
	rm -f $(TARGET_DIR)/usr/sbin/lighttpd-angel
	rm -rf $(TARGET_DIR)/usr/lib/lighttpd
endef

$(eval $(call AUTOTARGETS,package,lighttpd))
