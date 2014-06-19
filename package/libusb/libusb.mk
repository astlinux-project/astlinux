#############################################################
#
# libusb
#
#############################################################
LIBUSB_VERSION = 1.0.19
LIBUSB_SOURCE = libusb-$(LIBUSB_VERSION).tar.bz2
LIBUSB_SITE = http://downloads.sourceforge.net/project/libusb/libusb-1.0/libusb-$(LIBUSB_VERSION)
LIBUSB_DEPENDENCIES = host-pkg-config
LIBUSB_INSTALL_STAGING = YES

ifeq ($(BR2_ROOTFS_DEVICE_CREATION_DYNAMIC_UDEV),y)
LIBUSB_DEPENDENCIES += udev
else
LIBUSB_CONF_OPT += --disable-udev
endif

$(eval $(call AUTOTARGETS,package,libusb))
