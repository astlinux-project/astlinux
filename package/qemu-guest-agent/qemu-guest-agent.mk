################################################################################
#
# qemu-guest-agent
#
################################################################################

QEMU_GUEST_AGENT_VERSION = 3.1.0
QEMU_GUEST_AGENT_SOURCE = qemu-$(QEMU_GUEST_AGENT_VERSION).tar.xz
QEMU_GUEST_AGENT_SITE = https://download.qemu.org

QEMU_GUEST_AGENT_DEPENDENCIES = host-pkg-config libglib2 zlib util-linux

# Need the LIBS variable because librt and libm are
# not automatically pulled. :-(
QEMU_GUEST_AGENT_LIBS = -lrt -lm

QEMU_GUEST_AGENT_VARS = \
	LIBTOOL=$(HOST_DIR)/usr/bin/libtool

define QEMU_GUEST_AGENT_CONFIGURE_CMDS
	( cd $(@D); \
		LIBS='$(QEMU_GUEST_AGENT_LIBS)' \
		$(TARGET_CONFIGURE_OPTS) \
		$(TARGET_CONFIGURE_ARGS) \
		CPP="$(TARGET_CC) -E" \
		$(QEMU_GUEST_AGENT_VARS) \
		./configure \
			--prefix=/usr \
			--cross-prefix=$(TARGET_CROSS) \
			--sysconfdir=/etc \
			--localstatedir=/var \
			--audio-drv-list= \
			--disable-kvm \
			--disable-attr \
			--disable-vhost-net \
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
			--disable-system \
			--disable-linux-user \
			--disable-fdt \
			--disable-tools \
			--disable-vnc \
			--disable-gnutls \
			--disable-libssh2 \
	)
endef

define QEMU_GUEST_AGENT_BUILD_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) -C $(@D) qemu-ga
endef

define QEMU_GUEST_AGENT_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/qemu-ga $(TARGET_DIR)/usr/sbin/qemu-ga
	$(INSTALL) -D -m 0755 package/qemu-guest-agent/qemu-guest-agent.init $(TARGET_DIR)/etc/init.d/qemu-guest-agent
	ln -sf ../../init.d/qemu-guest-agent $(TARGET_DIR)/etc/runlevels/default/S01qemu-guest-agent
	ln -sf ../../init.d/qemu-guest-agent $(TARGET_DIR)/etc/runlevels/default/K94qemu-guest-agent
endef

define QEMU_GUEST_AGENT_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/qemu-ga
	rm -f $(TARGET_DIR)/etc/init.d/qemu-guest-agent
	rm -f $(TARGET_DIR)/etc/runlevels/default/S01qemu-guest-agent
	rm -f $(TARGET_DIR)/etc/runlevels/default/K94qemu-guest-agent
endef

$(eval $(call GENTARGETS,package,qemu-guest-agent))
