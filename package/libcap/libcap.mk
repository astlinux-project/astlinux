#############################################################
#
# libcap
#
#############################################################

LIBCAP_VERSION = 2.71
LIBCAP_SITE = https://www.kernel.org/pub/linux/libs/security/linux-privs/libcap2
LIBCAP_SOURCE = libcap-$(LIBCAP_VERSION).tar.xz

LIBCAP_DEPENDENCIES = host-gperf
LIBCAP_INSTALL_STAGING = YES

HOST_LIBCAP_DEPENDENCIES = host-gperf

LIBCAP_MAKE_FLAGS = \
	CROSS_COMPILE="$(TARGET_CROSS)" \
	BUILD_CC="$(HOSTCC)" \
	BUILD_CFLAGS="$(HOST_CFLAGS)" \
	lib=lib \
	prefix=/usr \
	SHARED=$(if $(BR2_PREFER_STATIC_LIB),,yes) \
	PTHREADS=$(if $(BR2_TOOLCHAIN_HAS_THREADS),yes,)

define LIBCAP_BUILD_CMDS
	$(TARGET_MAKE_ENV) $(TARGET_CONFIGURE_OPTS) $(MAKE) -C $(@D)/libcap \
		$(LIBCAP_MAKE_FLAGS) all
endef

define LIBCAP_INSTALL_STAGING_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) -C $(@D)/libcap $(LIBCAP_MAKE_FLAGS) \
		DESTDIR=$(STAGING_DIR) install
endef

define LIBCAP_INSTALL_TARGET_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) -C $(@D)/libcap $(LIBCAP_MAKE_FLAGS) \
		DESTDIR=$(TARGET_DIR) install
endef

HOST_LIBCAP_MAKE_FLAGS = \
	DYNAMIC=yes \
	GOLANG=no \
	lib=lib \
	prefix=$(HOST_DIR) \
	RAISE_SETFCAP=no

define HOST_LIBCAP_BUILD_CMDS
	$(HOST_MAKE_ENV) $(HOST_CONFIGURE_OPTS) $(MAKE) -C $(@D) \
		$(HOST_LIBCAP_MAKE_FLAGS)
endef

define HOST_LIBCAP_INSTALL_CMDS
	$(HOST_MAKE_ENV) $(MAKE) -C $(@D) $(HOST_LIBCAP_MAKE_FLAGS) install
endef

$(eval $(call GENTARGETS,package,libcap))
$(eval $(call GENTARGETS,package,libcap,host))
