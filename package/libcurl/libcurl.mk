#############################################################
#
# libcurl
#
#############################################################

LIBCURL_VERSION = 8.9.1
LIBCURL_SOURCE = curl-$(LIBCURL_VERSION).tar.gz
LIBCURL_SITE = https://curl.haxx.se/download
LIBCURL_INSTALL_STAGING = YES

LIBCURL_DEPENDENCIES = host-pkg-config \
	$(if $(BR2_PACKAGE_ZLIB),zlib) \
	$(if $(BR2_PACKAGE_OPENLDAP),openldap)

LIBCURL_CONF_OPT = \
	--disable-verbose \
	--disable-manual \
	--disable-curldebug \
	--disable-mqtt \
	--disable-gopher \
	--disable-alt-svc \
	--disable-libcurl-option \
	--enable-symbol-hiding \
	--with-random=/dev/urandom \
	--without-libidn2 \
	--without-libpsl \
	--enable-ipv6

ifeq ($(BR2_PACKAGE_OPENSSL),y)
LIBCURL_DEPENDENCIES += openssl
LIBCURL_CONF_OPT += \
	--with-openssl=$(STAGING_DIR)/usr \
	--with-ca-bundle=/usr/lib/ssl/certs/ca-bundle.crt
else
LIBCURL_CONF_OPT += --without-ssl
endif

LIBCURL_CONF_ENV += \
	CFLAGS="" \
	CPPFLAGS='$(TARGET_CFLAGS)'

define LIBCURL_TARGET_CLEANUP
	rm -rf $(TARGET_DIR)/usr/bin/curl-config \
	       $(if $(BR2_PACKAGE_CURL),,$(TARGET_DIR)/usr/bin/curl)
endef
LIBCURL_POST_INSTALL_TARGET_HOOKS += LIBCURL_TARGET_CLEANUP

define LIBCURL_STAGING_FIXUP_CURL_CONFIG
	$(SED) "s,prefix=/usr,prefix=$(STAGING_DIR)/usr,g" $(STAGING_DIR)/usr/bin/curl-config
endef
LIBCURL_POST_INSTALL_STAGING_HOOKS += LIBCURL_STAGING_FIXUP_CURL_CONFIG

$(eval $(call AUTOTARGETS,package,libcurl))

curl: libcurl
curl-clean: libcurl-clean
curl-dirclean: libcurl-dirclean
curl-source: libcurl-source
