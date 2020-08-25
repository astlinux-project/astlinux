#############################################################
#
# iptables
#
#############################################################

IPTABLES_VERSION = 1.8.4
IPTABLES_SOURCE = iptables-$(IPTABLES_VERSION).tar.bz2
IPTABLES_SITE = https://netfilter.org/projects/iptables/files
IPTABLES_INSTALL_STAGING = YES
IPTABLES_DEPENDENCIES = host-pkg-config

IPTABLES_CONF_OPT = \
	--libexecdir=/usr/lib \
	--with-kernel=$(STAGING_DIR)/usr \
	--with-xt-lock-name=/var/lock/xtables.lock \
	--disable-nftables \
	--disable-connlabel \
	--disable-bpf-compiler \
	--disable-nfsynproxy

define IPTABLES_TARGET_SYMLINK_CREATE
	ln -sf xtables-legacy-multi $(TARGET_DIR)/usr/sbin/iptables
	ln -sf xtables-legacy-multi $(TARGET_DIR)/usr/sbin/iptables-save
	ln -sf xtables-legacy-multi $(TARGET_DIR)/usr/sbin/iptables-restore
endef

define IPTABLES_TARGET_IPV6_SYMLINK_CREATE
	ln -sf xtables-legacy-multi $(TARGET_DIR)/usr/sbin/ip6tables
	ln -sf xtables-legacy-multi $(TARGET_DIR)/usr/sbin/ip6tables-save
	ln -sf xtables-legacy-multi $(TARGET_DIR)/usr/sbin/ip6tables-restore
endef

define IPTABLES_TARGET_IPV6_REMOVE
	rm -f $(TARGET_DIR)/usr/lib/libip6tc.*
endef

define IPTABLES_TARGET_INSTALL
	$(INSTALL) -D -m 0755 package/iptables/iptables.init $(TARGET_DIR)/etc/init.d/iptables
endef

IPTABLES_POST_INSTALL_TARGET_HOOKS += IPTABLES_TARGET_SYMLINK_CREATE

ifeq ($(BR2_INET_IPV6),y)
IPTABLES_POST_INSTALL_TARGET_HOOKS += IPTABLES_TARGET_IPV6_SYMLINK_CREATE
else
IPTABLES_POST_INSTALL_TARGET_HOOKS += IPTABLES_TARGET_IPV6_REMOVE
endif

IPTABLES_POST_INSTALL_TARGET_HOOKS += IPTABLES_TARGET_INSTALL

define IPTABLES_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/iptables-xml
	rm -f $(TARGET_DIR)/usr/sbin/iptables* $(TARGET_DIR)/usr/sbin/ip6tables*
	rm -f $(TARGET_DIR)/usr/sbin/xtables-legacy-multi
	rm -rf $(TARGET_DIR)/usr/lib/xtables
endef

$(eval $(call AUTOTARGETS,package,iptables))
