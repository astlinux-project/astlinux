################################################################################
#
# openvmtools
#
################################################################################

OPENVMTOOLS_VERSION = 10.3.10
OPENVMTOOLS_SOURCE = open-vm-tools-$(OPENVMTOOLS_VERSION)-12406962.tar.gz
OPENVMTOOLS_SITE = https://github.com/vmware/open-vm-tools/releases/download/stable-$(OPENVMTOOLS_VERSION)

OPENVMTOOLS_AUTORECONF = YES

OPENVMTOOLS_CONF_OPT = \
	--disable-static \
	--with-pic \
	--with-dnet \
	--without-icu \
	--without-x \
	--without-gtk2 \
	--without-gtkmm \
	--without-kernel-modules \
	--without-xerces \
	--without-pam \
	--disable-tests \
	--disable-docs \
	--disable-vgauth \
	--disable-multimon \
	--disable-grabbitmqproxy \
	--disable-resolutionkms \
	--disable-deploypkg

OPENVMTOOLS_CONF_ENV += CUSTOM_DNET_CPPFLAGS=" "
OPENVMTOOLS_DEPENDENCIES = libglib2 libdnet

ifeq ($(BR2_PACKAGE_OPENSSL),y)
OPENVMTOOLS_CONF_OPT += --with-ssl
OPENVMTOOLS_DEPENDENCIES += openssl
else
OPENVMTOOLS_CONF_OPT += --without-ssl
endif

define OPENVMTOOLS_POST_INSTALL
	rm -f $(TARGET_DIR)/lib/udev/rules.d/99-vmware-scsi-udev.rules
	$(INSTALL) -m 0755 -D package/openvmtools/vmware-tools/scripts/vmware/network $(TARGET_DIR)/etc/vmware-tools/scripts/vmware/network
	$(INSTALL) -m 0644 -D package/openvmtools/vmware-tools/tools.conf $(TARGET_DIR)/etc/vmware-tools/tools.conf
	$(INSTALL) -m 0755 -D package/openvmtools/openvmtools.init $(TARGET_DIR)/etc/init.d/openvmtools
	ln -sf ../../init.d/openvmtools $(TARGET_DIR)/etc/runlevels/default/S01openvmtools
	ln -sf ../../init.d/openvmtools $(TARGET_DIR)/etc/runlevels/default/K94openvmtools
endef

OPENVMTOOLS_POST_INSTALL_TARGET_HOOKS += OPENVMTOOLS_POST_INSTALL

define OPENVMTOOLS_UNINSTALL_TARGET_CMDS
	rm -rf $(TARGET_DIR)/etc/vmware-tools
	rm -rf $(TARGET_DIR)/usr/lib/open-vm-tools
	rm -rf $(TARGET_DIR)/usr/share/open-vm-tools
	rm -f $(TARGET_DIR)/etc/init.d/openvmtools
	rm -f $(TARGET_DIR)/etc/runlevels/default/S01openvmtools
	rm -f $(TARGET_DIR)/etc/runlevels/default/K94openvmtools
endef

$(eval $(call AUTOTARGETS,package,openvmtools))
