################################################################################
#
# qemu
#
################################################################################

QEMU_VERSION = 3.1.0
QEMU_SOURCE = qemu-$(QEMU_VERSION).tar.xz
QEMU_SITE = https://download.qemu.org

QEMU_DEPENDENCIES = host-pkg-config libglib2 zlib pixman util-linux

# Need the LIBS variable because librt and libm are
# not automatically pulled. :-(
QEMU_LIBS = -lrt -lm

QEMU_OPTS =

QEMU_VARS = \
	LIBTOOL=$(HOST_DIR)/usr/bin/libtool

# If we want to specify only a subset of targets, we must still enable all
# of them, so that QEMU properly builds its list of default targets, from
# which it then checks if the specified sub-set is valid. That's what we
# do in the first part of the if-clause.
# Otherwise, if we do not want to pass a sub-set of targets, we then need
# to either enable or disable -user and/or -system emulation appropriately.
# That's what we do in the else-clause.
ifneq ($(call qstrip,$(BR2_PACKAGE_QEMU_CUSTOM_TARGETS)),)
QEMU_OPTS += --enable-system --enable-linux-user
QEMU_OPTS += --target-list="$(call qstrip,$(BR2_PACKAGE_QEMU_CUSTOM_TARGETS))"
else

ifeq ($(BR2_PACKAGE_QEMU_SYSTEM),y)
QEMU_OPTS += --enable-system
else
QEMU_OPTS += --disable-system
endif

ifeq ($(BR2_PACKAGE_QEMU_LINUX_USER),y)
QEMU_OPTS += --enable-linux-user
else
QEMU_OPTS += --disable-linux-user
endif

endif

ifeq ($(BR2_PACKAGE_QEMU_SDL),y)
QEMU_OPTS += --enable-sdl
QEMU_DEPENDENCIES += sdl
QEMU_VARS += SDL_CONFIG=$(BR2_STAGING_DIR)/usr/bin/sdl-config
else
QEMU_OPTS += --disable-sdl
endif

QEMU_OPTS += --disable-fdt

ifeq ($(BR2_PACKAGE_QEMU_SYSTEM_TOOLS),y)
QEMU_OPTS += --enable-tools
else
QEMU_OPTS += --disable-tools
endif

ifeq ($(BR2_PACKAGE_QEMU_SYSTEM_VNC),y)
QEMU_OPTS += --enable-vnc
else
QEMU_OPTS += --disable-vnc
endif

ifeq ($(BR2_PACKAGE_GNUTLS),y)
QEMU_OPTS += --enable-gnutls
else
QEMU_OPTS += --disable-gnutls
endif

define QEMU_CONFIGURE_CMDS
	( cd $(@D); \
		LIBS='$(QEMU_LIBS)' \
		$(TARGET_CONFIGURE_OPTS) \
		$(TARGET_CONFIGURE_ARGS) \
		CPP="$(TARGET_CC) -E" \
		$(QEMU_VARS) \
		./configure \
			--prefix=/usr \
			--cross-prefix=$(TARGET_CROSS) \
			--sysconfdir=/etc \
			--audio-drv-list= \
			--enable-kvm \
			--enable-attr \
			--enable-vhost-net \
			--disable-bsd-user \
			--disable-xen \
			--disable-slirp \
			--disable-virtfs \
			--disable-brlapi \
			--disable-curses \
			--disable-curl \
			--disable-bluez \
			--disable-vde \
			--disable-linux-aio \
			--disable-cap-ng \
			--disable-docs \
			--disable-spice \
			--disable-rbd \
			--disable-libiscsi \
			--disable-usb-redir \
			--disable-strip \
			--disable-seccomp \
			--disable-sparse \
			$(QEMU_OPTS) \
	)
endef

define QEMU_BUILD_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) -C $(@D)
endef

define QEMU_INSTALL_TARGET_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) -C $(@D) $(QEMU_MAKE_ENV) DESTDIR=$(TARGET_DIR) install
	mkdir -p $(TARGET_DIR)/stat/etc/qemu
	ln -s /tmp/etc/qemu $(TARGET_DIR)/etc/qemu
	$(INSTALL) -m 0644 -D package/qemu/bridge.conf $(TARGET_DIR)/stat/etc/qemu/bridge.conf
	if [ -f $(TARGET_DIR)/usr/bin/qemu-system-x86_64 ]; then \
	  ln -sf qemu-system-x86_64 $(TARGET_DIR)/usr/bin/qemu ; \
	fi
	$(INSTALL) -D -m 0755 package/qemu/qemu.init $(TARGET_DIR)/etc/init.d/qemu
	ln -sf ../../init.d/qemu $(TARGET_DIR)/etc/runlevels/default/S95qemu
	ln -sf ../../init.d/qemu $(TARGET_DIR)/etc/runlevels/default/K04qemu
endef

define QEMU_UNINSTALL_TARGET_CMDS
	rm -rf $(TARGET_DIR)/stat/etc/qemu
	rm -f $(TARGET_DIR)/etc/qemu
	rm -f $(TARGET_DIR)/usr/bin/qemu
	rm -f $(TARGET_DIR)/usr/bin/qemu-*
	rm -f $(TARGET_DIR)/etc/init.d/qemu
	rm -f $(TARGET_DIR)/etc/runlevels/default/S95qemu
	rm -f $(TARGET_DIR)/etc/runlevels/default/K04qemu
endef

$(eval $(call GENTARGETS,package,qemu))
