################################################################################
#
# kmod
#
################################################################################

KMOD_VERSION = 33
KMOD_SOURCE = kmod-$(KMOD_VERSION).tar.xz
KMOD_SITE = $(BR2_KERNEL_MIRROR)/linux/utils/kernel/kmod
KMOD_INSTALL_STAGING = YES
KMOD_DEPENDENCIES = host-pkg-config
HOST_KMOD_DEPENDENCIES = host-pkg-config

KMOD_CONF_OPT = \
	--disable-static \
	--enable-shared \
	--without-zstd \
	--without-openssl

KMOD_CONF_OPT += --disable-manpages
HOST_KMOD_CONF_OPT = --disable-manpages

ifeq ($(BR2_PACKAGE_ZLIB),y)
KMOD_DEPENDENCIES += zlib
KMOD_CONF_OPT += --with-zlib
else
KMOD_CONF_OPT += --without-zlib
endif

ifeq ($(BR2_PACKAGE_XZ),y)
KMOD_DEPENDENCIES += xz
KMOD_CONF_OPT += --with-xz
else
KMOD_CONF_OPT += --without-xz
endif

ifeq ($(BR2_PACKAGE_KMOD_TOOLS),y)

define KMOD_INSTALL_TOOLS
	for i in depmod insmod lsmod modinfo modprobe rmmod; do \
		ln -sf ../usr/bin/kmod $(TARGET_DIR)/sbin/$$i; \
	done
endef

KMOD_POST_INSTALL_TARGET_HOOKS += KMOD_INSTALL_TOOLS
else
KMOD_CONF_OPT += --disable-tools
endif

# We only install depmod, since that's the only tool used for the
# host.
define HOST_KMOD_INSTALL_TOOLS
	mkdir -p $(HOST_DIR)/usr/sbin/
	ln -sf ../bin/kmod $(HOST_DIR)/usr/sbin/depmod
endef

HOST_KMOD_POST_INSTALL_HOOKS += HOST_KMOD_INSTALL_TOOLS

$(eval $(call AUTOTARGETS,package,kmod))
$(eval $(call AUTOTARGETS,package,kmod,host))
