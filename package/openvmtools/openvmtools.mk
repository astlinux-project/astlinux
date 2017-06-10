################################################################################
#
# openvmtools
#
################################################################################

OPENVMTOOLS_VERSION = 10.1.5
OPENVMTOOLS_SOURCE = open-vm-tools-$(OPENVMTOOLS_VERSION)-5055683.tar.gz
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
	--without-procps \
	--without-pam \
	--disable-tests \
	--disable-docs \
	--disable-vgauth \
	--disable-multimon \
	--disable-grabbitmqproxy \
	--disable-deploypkg

OPENVMTOOLS_CONF_ENV += CUSTOM_DNET_CPPFLAGS=" "
OPENVMTOOLS_DEPENDENCIES = libglib2 libdnet

ifeq ($(BR2_PACKAGE_OPENSSL),y)
OPENVMTOOLS_CONF_OPT += --with-ssl
OPENVMTOOLS_DEPENDENCIES += openssl
else
OPENVMTOOLS_CONF_OPT += --without-ssl
endif

define OPENVMTOOLS_POST_INSTALL_TARGET_THINGIES
	rm -f $(TARGET_DIR)/etc/vmware-tools/scripts/vmware/network
	rm -f $(TARGET_DIR)/lib/udev/rules.d/99-vmware-scsi-udev.rules
endef

OPENVMTOOLS_POST_INSTALL_TARGET_HOOKS += OPENVMTOOLS_POST_INSTALL_TARGET_THINGIES

$(eval $(call AUTOTARGETS,package,openvmtools))
