#############################################################
#
# bind
#
#############################################################

BIND_VERSION = 9.10.3-P4
BIND_SITE = ftp://ftp.isc.org/isc/bind9/$(BIND_VERSION)
BIND_MAKE = $(MAKE1)
BIND_INSTALL_STAGING = YES
BIND_TARGET_LIBS = libbind9.so* libdns.so* libisc.so* libisccc.so* libisccfg.so* liblwres.so*
BIND_CONF_ENV = \
	BUILD_CC="$(TARGET_CC)" \
	BUILD_CFLAGS="$(TARGET_CFLAGS)"
BIND_CONF_OPT = \
	--with-libjson=no \
	--with-randomdev=/dev/urandom \
	--enable-epoll \
	--with-libtool \
	--with-gssapi=no \
	--enable-filter-aaaa

ifeq ($(BR2_PACKAGE_LIBCAP),y)
BIND_CONF_OPT += --enable-linux-caps
BIND_DEPENDENCIES += libcap
else
BIND_CONF_OPT += --disable-linux-caps
endif

# Don't enable newstats
BIND_CONF_OPT += --with-libxml2=no

ifeq ($(BR2_PACKAGE_OPENSSL),y)
BIND_DEPENDENCIES += openssl
BIND_CONF_ENV += \
	ac_cv_func_EVP_sha256=yes \
	ac_cv_func_EVP_sha384=yes \
	ac_cv_func_EVP_sha512=yes
BIND_CONF_OPT += \
	--with-openssl=$(STAGING_DIR)/usr LIBS="-lz" \
	--with-ecdsa=yes
# GOST cipher support requires openssl extra engines
ifeq ($(BR2_PACKAGE_OPENSSL_ENGINES),y)
BIND_CONF_OPT += --with-gost=yes
else
BIND_CONF_OPT += --with-gost=no
endif
else
BIND_CONF_OPT += --with-openssl=no
endif

# Used by dnssec-checkds and dnssec-coverage
BIND_CONF_OPT += --with-python=no

ifeq ($(BR2_PACKAGE_READLINE),y)
BIND_DEPENDENCIES += readline
else
BIND_CONF_OPT += --with-readline=no
endif

define BIND_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(STAGING_DIR)/usr/bin/dig $(TARGET_DIR)/usr/bin/dig
	cp -a $(addprefix $(STAGING_DIR)/usr/lib/, $(BIND_TARGET_LIBS)) $(TARGET_DIR)/usr/lib/
endef

BIND_UNINSTALL_STAGING_OPT = --version

define BIND_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/dig
	rm -f $(addprefix $(TARGET_DIR)/usr/lib/, $(BIND_TARGET_LIBS))
endef

$(eval $(call AUTOTARGETS,package,bind))
