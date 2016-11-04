#############################################################
#
# iproute2
#
#############################################################

IPROUTE2_VERSION = 3.16.0
IPROUTE2_SOURCE = iproute2-$(IPROUTE2_VERSION).tar.xz
IPROUTE2_SITE = $(BR2_KERNEL_MIRROR)/linux/utils/net/iproute2
IPROUTE2_TARGET_SBINS = ip tc bridge ss rtmon ifcfg rtpr routel routef nstat ifstat rtacct lnstat genl ctstat rtstat

IPROUTE2_DEPENDENCIES += host-bison host-flex host-pkg-config

# If both iproute2 and busybox are selected, make certain we win
# the fight over who gets to have their utils actually installed.
ifeq ($(BR2_PACKAGE_BUSYBOX),y)
IPROUTE2_DEPENDENCIES += busybox
endif

# If we've got iptables enable xtables support for tc
ifeq ($(BR2_PACKAGE_IPTABLES),y)
IPROUTE2_DEPENDENCIES += iptables
define IPROUTE2_WITH_IPTABLES
	# Makefile is busted so it never passes IPT_LIB_DIR properly
	$(SED) "s/-DIPT/-DXT/" $(@D)/tc/Makefile
	echo "TC_CONFIG_XT:=y" >>$(@D)/Config
endef
endif

define IPROUTE2_CONFIGURE_CMDS
	# Cross-compile configure
	$(SED) 's/gcc/$$CC $$CFLAGS/g' $(@D)/configure
	cd $(@D) && $(TARGET_CONFIGURE_OPTS) ./configure
	# arpd needs berkeleydb
	$(SED) "/^TARGETS=/s: arpd : :" $(@D)/misc/Makefile
	echo "IPT_LIB_DIR:=/usr/lib/xtables" >>$(@D)/Config
	$(IPROUTE2_WITH_IPTABLES)
endef

define IPROUTE2_BUILD_CMDS
	$(SED) 's/$$(CCOPTS)//' $(@D)/netem/Makefile
	$(TARGET_MAKE_ENV) LDFLAGS="$(TARGET_LDFLAGS)" $(MAKE) \
		DBM_INCLUDE="$(STAGING_DIR)/usr/include" \
		SHARED_LIBS=y \
		CC="$(TARGET_CC)" CCOPTS="$(TARGET_CFLAGS) -D_GNU_SOURCE" -C $(@D)
endef

define IPROUTE2_INSTALL_TARGET_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) -C $(@D) DESTDIR="$(TARGET_DIR)" \
		SBINDIR=/sbin \
		DOCDIR=/usr/share/doc/iproute2-$(IPROUTE2_VERSION) \
		MANDIR=/usr/share/man install
	# Wants bash
	rm -f $(TARGET_DIR)/sbin/ifcfg
endef

define IPROUTE2_UNINSTALL_TARGET_CMDS
	rm -rf $(TARGET_DIR)/lib/tc
	rm -rf $(TARGET_DIR)/usr/lib/tc
	rm -rf $(TARGET_DIR)/etc/iproute2
	rm -rf $(TARGET_DIR)/var/lib/arpd
	rm -f $(addprefix $(TARGET_DIR)/sbin/, $(IPROUTE2_TARGET_SBINS))
endef

$(eval $(call GENTARGETS,package,iproute2))
