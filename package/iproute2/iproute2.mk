#############################################################
#
# iproute2
#
#############################################################

IPROUTE2_VERSION = 6.0.0
IPROUTE2_SOURCE = iproute2-$(IPROUTE2_VERSION).tar.xz
IPROUTE2_SITE = $(BR2_KERNEL_MIRROR)/linux/utils/net/iproute2
IPROUTE2_TARGET_SBINS = ip tc bridge ss rtmon routel nstat ifstat rtacct lnstat genl ctstat rtstat tipc devlink rdma vdpa dcb

IPROUTE2_DEPENDENCIES = host-bison host-flex host-pkg-config \
	$(if $(BR2_PACKAGE_LIBMNL),libmnl) \
	$(if $(BR2_PACKAGE_LIBCAP),libcap)

# If both iproute2 and busybox are selected, make certain we win
# the fight over who gets to have their utils actually installed.
ifeq ($(BR2_PACKAGE_BUSYBOX),y)
IPROUTE2_DEPENDENCIES += busybox
endif

# If we've got iptables enable xtables support for tc
ifeq ($(BR2_PACKAGE_IPTABLES),y)
IPROUTE2_DEPENDENCIES += iptables
else
define IPROUTE2_DISABLE_IPTABLES
	# m_xt.so is built unconditionally
	echo "TC_CONFIG_XT:=n" >>$(@D)/config.mk
endef
endif

define IPROUTE2_CONFIGURE_CMDS
	cd $(@D) && $(TARGET_CONFIGURE_OPTS) ./configure
	$(IPROUTE2_DISABLE_IPTABLES)
endef

define IPROUTE2_BUILD_CMDS
	$(TARGET_MAKE_ENV) LDFLAGS="$(TARGET_LDFLAGS)" \
		CFLAGS="$(TARGET_CFLAGS) -DXT_LIB_DIR=\\\"/usr/lib/xtables\\\"" \
		CBUILD_CFLAGS="$(HOST_CFLAGS)" $(MAKE) V=1 LIBDB_LIBS=-lpthread \
		DBM_INCLUDE="$(STAGING_DIR)/usr/include" \
		SHARED_LIBS=y \
	-C $(@D)
endef

define IPROUTE2_INSTALL_TARGET_CMDS
	$(TARGET_MAKE_ENV) DESTDIR="$(TARGET_DIR)" $(MAKE) -C $(@D) install
	# Requires python3
	rm -f $(TARGET_DIR)/sbin/routel
endef

define IPROUTE2_UNINSTALL_TARGET_CMDS
	rm -rf $(TARGET_DIR)/usr/lib/tc
	rm -rf $(TARGET_DIR)/etc/iproute2
	rm -f $(addprefix $(TARGET_DIR)/sbin/, $(IPROUTE2_TARGET_SBINS))
endef

$(eval $(call GENTARGETS,package,iproute2))
