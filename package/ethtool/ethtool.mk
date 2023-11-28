#############################################################
#
# ethtool
#
#############################################################

ETHTOOL_VERSION = 6.6
ETHTOOL_SITE = $(BR2_KERNEL_MIRROR)/software/network/ethtool

ETHTOOL_DEPENDENCIES = host-pkg-config

ETHTOOL_CONF_OPT = \
	--without-bash-completion-dir \
	--disable-pretty-dump

ifeq ($(BR2_PACKAGE_LIBMNL),y)
ETHTOOL_DEPENDENCIES += host-pkg-config libmnl
ETHTOOL_CONF_OPT += --enable-netlink
else
ETHTOOL_CONF_OPT += --disable-netlink
endif

$(eval $(call AUTOTARGETS,package,ethtool))
