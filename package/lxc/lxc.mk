################################################################################
#
# lxc
#
################################################################################

LXC_VERSION = 3.2.1
LXC_SITE = https://linuxcontainers.org/downloads/lxc
LXC_DEPENDENCIES = host-pkg-config
LXC_INSTALL_STAGING = YES

LXC_CONF_OPT = \
	--localstatedir=/var \
	--with-runtime-path=/var/run \
	--with-distro=buildroot \
	--disable-commands \
	--disable-apparmor \
	--disable-seccomp \
	--disable-selinux \
	--disable-werror \
	--disable-doc \
	--disable-api-docs \
	--disable-examples \
	--disable-pam \
	$(if $(BR2_PACKAGE_BASH),,--disable-bash)

ifeq ($(BR2_PACKAGE_LIBCAP),y)
LXC_CONF_OPT += --enable-capabilities
LXC_DEPENDENCIES += libcap
else
LXC_CONF_OPT += --disable-capabilities
endif

ifeq ($(BR2_PACKAGE_OPENSSL),y)
LXC_CONF_OPT += --enable-openssl
LXC_DEPENDENCIES += openssl
else
LXC_CONF_OPT += --disable-openssl
endif

define LXC_REMOVE_TARGET_FILES
	rm -f $(TARGET_DIR)/usr/bin/lxc-checkpoint
	rm -f $(TARGET_DIR)/usr/bin/lxc-execute
	rm -f $(TARGET_DIR)/usr/share/lxc/templates/lxc-local
	rm -f $(TARGET_DIR)/usr/share/lxc/templates/lxc-oci
	rm -f $(TARGET_DIR)/usr/share/lxc/config/nesting.conf
	rm -f $(TARGET_DIR)/usr/share/lxc/config/oci.common.conf
	rm -f $(TARGET_DIR)/usr/share/lxc/lxc-patch.py
	rm -rf $(TARGET_DIR)/etc/default
	rm -rf $(TARGET_DIR)/usr/libexec/lxc
	rm -rf $(TARGET_DIR)/usr/share/lxc/selinux
endef
LXC_POST_INSTALL_TARGET_HOOKS = LXC_REMOVE_TARGET_FILES

define LXC_POST_INSTALL
	$(INSTALL) -m 0644 -D package/lxc/default.conf $(TARGET_DIR)/etc/lxc/default.conf
	$(INSTALL) -m 0644 -D package/lxc/config/*.conf $(TARGET_DIR)/usr/share/lxc/config/
	$(INSTALL) -m 0755 -D package/lxc/scripts/cgroupfs-mount $(TARGET_DIR)/usr/bin/cgroupfs-mount
	$(INSTALL) -m 0755 -D package/lxc/scripts/cgroupfs-umount $(TARGET_DIR)/usr/bin/cgroupfs-umount
	$(INSTALL) -m 0755 -D package/lxc/lxc.init $(TARGET_DIR)/etc/init.d/lxc
	ln -sf ../../init.d/lxc $(TARGET_DIR)/etc/runlevels/default/S98lxc
	ln -sf ../../init.d/lxc $(TARGET_DIR)/etc/runlevels/default/K00lxc
endef
LXC_POST_INSTALL_TARGET_HOOKS += LXC_POST_INSTALL

define LXC_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/cgroupfs-mount
	rm -f $(TARGET_DIR)/usr/bin/cgroupfs-umount
	rm -f $(TARGET_DIR)/etc/init.d/lxc
	rm -f $(TARGET_DIR)/etc/runlevels/default/S98lxc
	rm -f $(TARGET_DIR)/etc/runlevels/default/K00lxc
endef

$(eval $(call AUTOTARGETS,package,lxc))
