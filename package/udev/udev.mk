#############################################################
#
# udev (eudev)
#
#############################################################

UDEV_VERSION = 3.2.12
UDEV_SOURCE = eudev-$(UDEV_VERSION).tar.gz
UDEV_SITE = https://github.com/eudev-project/eudev/releases/download/v$(UDEV_VERSION)
UDEV_INSTALL_STAGING = YES

UDEV_CONF_OPT = \
	--disable-manpages \
	--sbindir=/sbin \
	--with-rootrundir=/var/run \
	--disable-selinux \
	--disable-introspection \
	--disable-hwdb \
	--enable-rule-generator \
	--enable-kmod \
	--enable-blkid

UDEV_DEPENDENCIES = host-gperf host-pkg-config util-linux kmod

define UDEV_INSTALL_USBTTY
	$(INSTALL) -m 0644 -D package/udev/usbtty/usbtty.rules $(TARGET_DIR)/etc/udev/rules.d/usbtty.rules
	$(INSTALL) -m 0755 -D package/udev/usbtty/usb-getty $(TARGET_DIR)/usr/share/usbtty/usb-getty
	$(INSTALL) -m 0755 -D package/udev/usbtty/usb-getty-background $(TARGET_DIR)/usr/share/usbtty/usb-getty-background
endef
UDEV_POST_INSTALL_TARGET_HOOKS += UDEV_INSTALL_USBTTY

$(eval $(call AUTOTARGETS,package,udev))
