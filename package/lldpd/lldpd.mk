################################################################################
#
# lldpd
#
################################################################################

LLDPD_VERSION = 1.0.17
LLDPD_SITE = https://github.com/lldpd/lldpd/releases/download/$(LLDPD_VERSION)
LLDPD_DEPENDENCIES = host-pkg-config $(if $(BR2_PACKAGE_LIBCAP),libcap)

LLDPD_CONF_OPT = \
	--localstatedir=/var \
	--with-embedded-libevent \
	--with-privsep-user=nobody \
	--with-privsep-group=nobody \
	--with-privsep-chroot=/var/lib/lldpd \
	--without-snmp \
	--without-xml \
	--without-seccomp \
	--without-libbsd \
	--disable-doxygen-doc

ifeq ($(BR2_PACKAGE_READLINE),y)
LLDPD_DEPENDENCIES += readline
LLDPD_CONF_OPT += --with-readline
else ifeq ($(BR2_PACKAGE_LIBEDIT),y)
LLDPD_DEPENDENCIES += libedit
LLDPD_CONF_OPT += --with-readline
else
LLDPD_CONF_OPT += --without-readline
endif

define LLDPD_POST_INSTALL
	$(INSTALL) -m 0755 -D package/lldpd/lldpd.init $(TARGET_DIR)/etc/init.d/lldpd
	ln -sf /tmp/etc/lldpd.conf $(TARGET_DIR)/etc/lldpd.conf
	ln -sf ../../init.d/lldpd $(TARGET_DIR)/etc/runlevels/default/S12lldpd
	ln -sf ../../init.d/lldpd $(TARGET_DIR)/etc/runlevels/default/K28lldpd
	# Remove extra installed data files
	rm -rf $(TARGET_DIR)/etc/lldpd.d
	rm -rf $(TARGET_DIR)/usr/share/bash-completion
	rm -rf $(TARGET_DIR)/usr/share/zsh
endef
LLDPD_POST_INSTALL_TARGET_HOOKS += LLDPD_POST_INSTALL

define LLDPD_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/lldp*
	rm -f $(TARGET_DIR)/usr/lib/liblldpctl*
	rm -f $(TARGET_DIR)/etc/init.d/lldpd
	rm -f $(TARGET_DIR)/etc/lldpd.conf
	rm -f $(TARGET_DIR)/etc/runlevels/default/S12lldpd
	rm -f $(TARGET_DIR)/etc/runlevels/default/K28lldpd
endef

$(eval $(call AUTOTARGETS,package,lldpd))
