#############################################################
#
# openssl
#
#############################################################

OPENSSL_VERSION = 1.0.2m
OPENSSL_SITE = http://www.openssl.org/source
OPENSSL_INSTALL_STAGING = YES
OPENSSL_DEPENDENCIES = zlib
OPENSSL_TARGET_ARCH = generic32
OPENSSL_CFLAGS = $(TARGET_CFLAGS)

ifeq ($(BR2_PACKAGE_OPENSSL_OCF),y)
	OPENSSL_CFLAGS += -DHAVE_CRYPTODEV -DUSE_CRYPTODEV_DIGESTS
	# Needs to be added as BR2_PACKAGE_OCF_LINUX to work
	OPENSSL_DEPENDENCIES += ocf-linux
endif

# Some architectures are optimized in OpenSSL
ifeq ($(ARCH),arm)
	OPENSSL_TARGET_ARCH = armv4
endif
ifeq ($(ARCH),powerpc)
# 4xx cores seem to have trouble with openssl's ASM optimizations
ifeq ($(BR2_powerpc_401)$(BR2_powerpc_403)$(BR2_powerpc_405)$(BR2_powerpc_405fp)$(BR2_powerpc_440)$(BR2_powerpc_440fp),)
	OPENSSL_TARGET_ARCH = ppc
endif
endif
ifeq ($(ARCH),x86_64)
	OPENSSL_TARGET_ARCH = x86_64
endif
ifeq ($(ARCH),i686)
	OPENSSL_TARGET_ARCH = elf
endif
ifeq ($(ARCH),i586)
	OPENSSL_TARGET_ARCH = elf
endif

define OPENSSL_CONFIGURE_CMDS
	(cd $(@D); \
		$(TARGET_CONFIGURE_ARGS) \
		$(TARGET_CONFIGURE_OPTS) \
		./Configure \
			linux-$(OPENSSL_TARGET_ARCH) \
			--prefix=/usr \
			--openssldir=/usr/lib/ssl \
			--libdir=/lib \
			$(if $(BR2_TOOLCHAIN_HAS_THREADS),threads,no-threads) \
			$(if $(BR2_PREFER_STATIC_LIB),no-shared,shared) \
			no-rc5 \
			enable-camellia \
			enable-mdc2 \
			enable-tlsext \
			$(if $(BR2_PREFER_STATIC_LIB),zlib,zlib-dynamic) \
			$(if $(BR2_PREFER_STATIC_LIB),no-dso) \
	)
	$(SED) "s:-march=[-a-z0-9] ::" -e "s:-mcpu=[-a-z0-9] ::g" $(@D)/Makefile
	$(SED) "s:-O[0-9]:$(OPENSSL_CFLAGS):" $(@D)/Makefile
	$(SED) "s: build_tests::" $(@D)/Makefile
endef

define OPENSSL_BUILD_CMDS
	$(TARGET_MAKE_ENV) $(MAKE1) -C $(@D)
endef

define OPENSSL_INSTALL_STAGING_CMDS
	$(TARGET_MAKE_ENV) $(MAKE1) -C $(@D) INSTALL_PREFIX=$(STAGING_DIR) install
endef

define OPENSSL_INSTALL_TARGET_CMDS
	$(TARGET_MAKE_ENV) $(MAKE1) -C $(@D) INSTALL_PREFIX=$(TARGET_DIR) install
	# Keep target /usr/lib/ssl
	rm -f $(TARGET_DIR)/usr/bin/c_rehash
endef

ifneq ($(BR2_PREFER_STATIC_LIB),y)
# libraries gets installed read only, so strip fails
define OPENSSL_INSTALL_FIXUPS_SHARED
	chmod +w $(TARGET_DIR)/usr/lib/engines/lib*.so
	for i in $(addprefix $(TARGET_DIR)/usr/lib/,libcrypto.so.* libssl.so.*); \
	do chmod +w $$i; done
endef
OPENSSL_POST_INSTALL_TARGET_HOOKS += OPENSSL_INSTALL_FIXUPS_SHARED
endif

ifeq ($(BR2_PACKAGE_PERL),)
define OPENSSL_REMOVE_PERL_SCRIPTS
	rm -f $(TARGET_DIR)/etc/ssl/misc/{CA.pl,tsget}
endef
OPENSSL_POST_INSTALL_TARGET_HOOKS += OPENSSL_REMOVE_PERL_SCRIPTS
endif

ifeq ($(BR2_PACKAGE_OPENSSL_BIN),)
define OPENSSL_REMOVE_BIN
	rm -f $(TARGET_DIR)/usr/bin/openssl
	rm -f $(TARGET_DIR)/usr/lib/ssl/misc/{CA.*,c_*}
endef
OPENSSL_POST_INSTALL_TARGET_HOOKS += OPENSSL_REMOVE_BIN
endif

ifneq ($(BR2_PACKAGE_OPENSSL_ENGINES),y)
define OPENSSL_REMOVE_OPENSSL_ENGINES
	rm -rf $(TARGET_DIR)/usr/lib/engines
endef
OPENSSL_POST_INSTALL_TARGET_HOOKS += OPENSSL_REMOVE_OPENSSL_ENGINES
endif

$(eval $(call GENTARGETS,package,openssl))
