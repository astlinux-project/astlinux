#############################################################
#
# kexec
#
#############################################################
KEXEC_VERSION = 2.0.11
KEXEC_SOURCE = kexec-tools-$(KEXEC_VERSION).tar.gz
KEXEC_SITE = $(BR2_KERNEL_MIRROR)/linux/utils/kernel/kexec

KEXEC_UNINSTALL_STAGING_OPT = --version

ifeq ($(BR2_PACKAGE_KEXEC_ZLIB),y)
KEXEC_CONF_OPT += --with-zlib
KEXEC_DEPENDENCIES = zlib
else
KEXEC_CONF_OPT += --without-zlib
endif

# Only install kexec
define KEXEC_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/build/sbin/kexec $(TARGET_DIR)/sbin/kexec
endef

#define KEXEC_REMOVE_LIB_TOOLS
#	rm -rf $(TARGET_DIR)/usr/lib/kexec-tools
#endef
#
#KEXEC_POST_INSTALL_TARGET_HOOKS += KEXEC_REMOVE_LIB_TOOLS
#
define KEXEC_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/sbin/kexec
endef

$(eval $(call AUTOTARGETS,package,kexec))
