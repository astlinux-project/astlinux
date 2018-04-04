#############################################################
#
# fdisk - HOST-Only
#
# Used by: ./scripts/astlinux-makeimage
#
#############################################################

FDISK_VERSION_MAJOR = 2.28
FDISK_VERSION = $(FDISK_VERSION_MAJOR).2
FDISK_SOURCE = util-linux-$(FDISK_VERSION).tar.xz
FDISK_SITE = $(BR2_KERNEL_MIRROR)/linux/utils/util-linux/v$(FDISK_VERSION_MAJOR)

HOST_FDISK_DEPENDENCIES = host-pkg-config
HOST_FDISK_CONF_ENV = scanf_cv_type_modifier=no

HOST_FDISK_CONF_OPT += \
	--enable-static \
	--disable-shared \
	--disable-rpath \
	--disable-makeinstall-chown \
	--disable-nls \
	--disable-bash-completion \
	--with-bashcompletiondir="" \
	--without-tinfo \
	--without-readline \
	--without-utempter \
	--without-cap-ng \
	--without-user \
	--without-audit \
	--without-udev \
	--without-python \
	--without-btrfs \
	--without-systemd \
	--without-systemdsystemunitdir \
	--without-ncurses

HOST_FDISK_CONF_OPT += \
	--enable-all-programs \
	--enable-libblkid \
	--enable-libfdisk \
	--enable-libsmartcols \
	--enable-libuuid \
	--disable-libmount \
	--disable-agetty \
	--disable-bfs \
	--disable-cal \
	--disable-chfn-chsh \
	--disable-cramfs \
	--disable-eject \
	--disable-fallocate \
	--disable-fdformat \
	--disable-fsck \
	--disable-hwclock \
	--disable-ipcrm \
	--disable-ipcs \
	--disable-kill \
	--disable-last \
	--disable-line \
	--disable-logger \
	--disable-last --disable-login --disable-runuser --disable-su --disable-sulogin \
	--disable-losetup \
	--disable-lslogins \
	--disable-mesg \
	--disable-minix \
	--disable-more \
	--disable-mount \
	--disable-mountpoint \
	--disable-newgrp \
	--disable-nologin \
	--disable-nsenter \
	--disable-partx \
	--disable-pg \
	--disable-pivot_root \
	--disable-raw \
	--disable-rename \
	--disable-reset \
	--disable-schedutils \
	--disable-setpriv \
	--disable-setterm \
	--disable-switch_root \
	--disable-tunelp \
	--disable-ul \
	--disable-unshare \
	--disable-utmpdump \
	--disable-uuidd \
	--disable-vipw \
	--disable-wall \
	--disable-wdctl \
	--disable-write \
	--disable-zramctl

define HOST_FDISK_INSTALL_CMDS
	$(INSTALL) -m 0755 -D $(@D)/fdisk $(HOST_DIR)/usr/sbin/fdisk
endef

$(eval $(call AUTOTARGETS,package,fdisk,host))
