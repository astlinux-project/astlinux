#############################################################
#
# openssl
#
#############################################################

OPENSSL_VERSION = 1.1.1l
OPENSSL_SITE = https://www.openssl.org/source
OPENSSL_INSTALL_STAGING = YES
OPENSSL_DEPENDENCIES = zlib
OPENSSL_TARGET_ARCH = linux-generic32
OPENSSL_CFLAGS = $(TARGET_CFLAGS)

ifeq ($(BR2_TOOLCHAIN_HAS_THREADS),y)
OPENSSL_CFLAGS += -DOPENSSL_THREADS
endif

ifeq ($(BR2_PACKAGE_OPENSSL_OCF),y)
OPENSSL_DEPENDENCIES += ocf-linux
endif

ifeq ($(BR2_PREFER_STATIC_LIB),y)
# Use "gcc" minimalistic target to disable DSO
	OPENSSL_TARGET_ARCH = gcc
else
# Some architectures are optimized in OpenSSL
ifeq ($(ARCH),arm)
	OPENSSL_TARGET_ARCH = linux-armv4
endif
ifeq ($(ARCH),powerpc)
# 4xx cores seem to have trouble with openssl's ASM optimizations
ifeq ($(BR2_powerpc_401)$(BR2_powerpc_403)$(BR2_powerpc_405)$(BR2_powerpc_405fp)$(BR2_powerpc_440)$(BR2_powerpc_440fp),)
	OPENSSL_TARGET_ARCH = linux-ppc
endif
endif
ifeq ($(ARCH),x86_64)
	OPENSSL_TARGET_ARCH = linux-x86_64
endif
ifeq ($(ARCH),i686)
	OPENSSL_TARGET_ARCH = linux-elf
endif
ifeq ($(ARCH),i586)
	OPENSSL_TARGET_ARCH = linux-elf
endif
endif

define OPENSSL_CONFIGURE_CMDS
	(cd $(@D); \
		$(TARGET_CONFIGURE_ARGS) \
		$(TARGET_CONFIGURE_OPTS) \
		./Configure \
			$(OPENSSL_TARGET_ARCH) \
			--prefix=/usr \
			--openssldir=/usr/lib/ssl \
			$(if $(BR2_TOOLCHAIN_HAS_THREADS),-lpthread threads, no-threads) \
			$(if $(BR2_PREFER_STATIC_LIB),no-shared,shared) \
			$(if $(BR2_PACKAGE_OPENSSL_OCF),enable-devcryptoeng) \
			no-rc5 \
			enable-camellia \
			enable-mdc2 \
			no-tests \
			no-fuzz-libfuzzer \
			no-fuzz-afl \
			$(if $(BR2_PREFER_STATIC_LIB),zlib,zlib-dynamic) \
	)
	$(SED) "s:-march=[-a-z0-9] ::" -e "s:-mcpu=[-a-z0-9] ::g" $(@D)/Makefile
	$(SED) "s:-O[0-9s]:$(OPENSSL_CFLAGS):" $(@D)/Makefile
	$(SED) "s: build_tests::" $(@D)/Makefile
endef

define OPENSSL_BUILD_CMDS
	$(TARGET_MAKE_ENV) $(MAKE1) -C $(@D)
endef

define OPENSSL_INSTALL_STAGING_CMDS
	$(TARGET_MAKE_ENV) $(MAKE1) -C $(@D) DESTDIR=$(STAGING_DIR) install
endef

define OPENSSL_INSTALL_TARGET_CMDS
	$(TARGET_MAKE_ENV) $(MAKE1) -C $(@D) DESTDIR=$(TARGET_DIR) install
	# Keep target /usr/lib/ssl
	rm -f $(TARGET_DIR)/usr/bin/c_rehash
endef

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
	rm -rf $(TARGET_DIR)/usr/lib/engines-1.1
endef
OPENSSL_POST_INSTALL_TARGET_HOOKS += OPENSSL_REMOVE_OPENSSL_ENGINES
endif

$(eval $(call GENTARGETS,package,openssl))
