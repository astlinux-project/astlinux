#############################################################
#
# fdisk - HOST-Only
#
# Used by: ./scripts/astlinux-makeimage
#
#############################################################
FDISK_VERSION = $(FDISK_VERSION_MAJOR).1
FDISK_VERSION_MAJOR = 2.20
FDISK_SOURCE = util-linux-$(FDISK_VERSION).tar.bz2
FDISK_SITE = http://www.kernel.org/pub/linux/utils/util-linux/v$(FDISK_VERSION_MAJOR)

HOST_FDISK_DEPENDENCIES = host-pkg-config
HOST_FDISK_CONF_ENV = scanf_cv_type_modifier=no

HOST_FDISK_CONF_OPT += --disable-rpath --disable-makeinstall-chown --without-ncurses

HOST_FDISK_CONF_OPT += \
	--disable-agetty \
	--disable-arch \
	--disable-cramfs \
	--disable-ddate \
	--disable-fallocate \
	--disable-fsck \
	--disable-kill \
	--disable-libblkid \
	--disable-libmount \
	--disable-libuuid \
	--disable-last \
	--disable-mesg \
	--disable-mount \
	--disable-partx \
	--disable-pivot_root \
	--disable-raw \
	--disable-rename \
	--disable-reset \
	--disable-schedutils \
	--disable-switch_root \
	--disable-unshare \
	--disable-uuidd \
	--disable-wall \
	--disable-write

define HOST_FDISK_INSTALL_CMDS
	$(INSTALL) -m 0755 -D $(@D)/fdisk/fdisk $(HOST_DIR)/usr/sbin/fdisk
endef

$(eval $(call AUTOTARGETS,package,fdisk,host))
