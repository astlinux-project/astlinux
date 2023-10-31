#############################################################
#
# usbutils
#
#############################################################

USBUTILS_VERSION = 017
USBUTILS_SITE = https://github.com/gregkh/usbutils/archive/v$(USBUTILS_VERSION)
USBUTILS_SOURCE = usbutils-$(USBUTILS_VERSION).tar.gz
USBUTILS_DEPENDENCIES = host-pkg-config libusb udev
# Missing configure script
USBUTILS_AUTORECONF = YES

# Build after busybox since it's got a lightweight lsusb
ifeq ($(BR2_PACKAGE_BUSYBOX),y)
USBUTILS_DEPENDENCIES += busybox
endif

define USBUTILS_TARGET_CLEANUP
	rm -f $(TARGET_DIR)/usr/bin/lsusb.py
	rm -f $(TARGET_DIR)/usr/bin/usb-devices
endef

USBUTILS_POST_INSTALL_TARGET_HOOKS += USBUTILS_TARGET_CLEANUP

$(eval $(call AUTOTARGETS,package,usbutils))
