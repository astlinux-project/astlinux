#############################################################
#
# kexec
#
#############################################################

KEXEC_VERSION = 2.0.23
KEXEC_SOURCE = kexec-tools-$(KEXEC_VERSION).tar.xz
KEXEC_SITE = $(BR2_KERNEL_MIRROR)/linux/utils/kernel/kexec

ifeq ($(BR2_PACKAGE_KEXEC_ZLIB),y)
KEXEC_CONF_OPT += --with-zlib
KEXEC_DEPENDENCIES = zlib
else
KEXEC_CONF_OPT += --without-zlib
endif

ifeq ($(BR2_PACKAGE_XZ),y)
KEXEC_CONF_OPT += --with-lzma
KEXEC_DEPENDENCIES += xz
else
KEXEC_CONF_OPT += --without-lzma
endif

# Only install kexec
define KEXEC_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/build/sbin/kexec $(TARGET_DIR)/sbin/kexec
endef

KEXEC_UNINSTALL_STAGING_OPT = --version

define KEXEC_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/sbin/kexec
endef

$(eval $(call AUTOTARGETS,package,kexec))
