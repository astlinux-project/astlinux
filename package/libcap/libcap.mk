#############################################################
#
# libcap
#
#############################################################

LIBCAP_VERSION = 2.25
LIBCAP_SITE = https://www.kernel.org/pub/linux/libs/security/linux-privs/libcap2
LIBCAP_SOURCE = libcap-$(LIBCAP_VERSION).tar.xz
LIBCAP_DEPENDENCIES = host-libcap

LIBCAP_DEPENDENCIES = host-libcap host-gperf
LIBCAP_INSTALL_STAGING = YES

HOST_LIBCAP_DEPENDENCIES = host-gperf

LIBCAP_MAKE_TARGET = all
LIBCAP_MAKE_INSTALL_TARGET = install

LIBCAP_MAKE_FLAGS = \
	BUILD_CC="$(HOSTCC)" \
	BUILD_CFLAGS="$(HOST_CFLAGS)"


define LIBCAP_BUILD_CMDS
	$(TARGET_MAKE_ENV) $(TARGET_CONFIGURE_OPTS) $(MAKE) -C $(@D)/libcap \
		$(LIBCAP_MAKE_FLAGS) $(LIBCAP_MAKE_TARGET)
endef

define LIBCAP_INSTALL_STAGING_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) -C $(@D)/libcap $(LIBCAP_MAKE_FLAGS) \
		DESTDIR=$(STAGING_DIR) prefix=/usr lib=lib $(LIBCAP_MAKE_INSTALL_TARGET)
endef

define LIBCAP_INSTALL_TARGET_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) -C $(@D)/libcap $(LIBCAP_MAKE_FLAGS) \
		DESTDIR=$(TARGET_DIR) prefix=/usr lib=lib $(LIBCAP_MAKE_INSTALL_TARGET)
	## Make executable so it is stripped later (not needed or desired)
	## chmod 755 $(TARGET_DIR)/usr/lib/libcap.so.$(LIBCAP_VERSION)
endef

define HOST_LIBCAP_BUILD_CMDS
	$(HOST_MAKE_ENV) $(HOST_CONFIGURE_OPTS) $(MAKE) -C $(@D)\
		RAISE_SETFCAP=no
endef

define HOST_LIBCAP_INSTALL_CMDS
	$(HOST_MAKE_ENV) $(MAKE) -C $(@D) DESTDIR=$(HOST_DIR) \
		RAISE_SETFCAP=no prefix=/usr lib=lib install
endef

$(eval $(call GENTARGETS,package,libcap))
$(eval $(call GENTARGETS,package,libcap,host))
