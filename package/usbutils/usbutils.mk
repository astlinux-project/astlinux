#############################################################
#
# usbutils
#
#############################################################

USBUTILS_VERSION = 003
USBUTILS_SITE = $(BR2_KERNEL_MIRROR)/linux/utils/usb/usbutils
USBUTILS_DEPENDENCIES = host-pkg-config libusb
USBUTILS_INSTALL_STAGING = YES

USBUTILS_CONF_OPT = --disable-zlib

define USBUTILS_TARGET_CLEANUP
	rm -f $(TARGET_DIR)/usr/bin/usb-devices
	rm -f $(TARGET_DIR)/usr/bin/lsusb.py
	rm -f $(TARGET_DIR)/usr/sbin/update-usbids.sh
	rm -f $(TARGET_DIR)/usr/share/pkgconfig/usbutils.pc
endef

USBUTILS_POST_INSTALL_TARGET_HOOKS += USBUTILS_TARGET_CLEANUP

define USBUTILS_REMOVE_COMPRESSED_IDS
	rm -f $(TARGET_DIR)/usr/share/usb.ids.gz
endef

USBUTILS_POST_INSTALL_TARGET_HOOKS += USBUTILS_REMOVE_COMPRESSED_IDS

define USBUTILS_REMOVE_DEVFILES
	rm -f $(TARGET_DIR)/usr/bin/libusb-config
endef

ifneq ($(BR2_HAVE_DEVFILES),y)
USBUTILS_POST_INSTALL_TARGET_HOOKS += USBUTILS_REMOVE_DEVFILES
endif

$(eval $(call AUTOTARGETS,package,usbutils))
