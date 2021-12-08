#############################################################
#
# ethtool
#
#############################################################

ETHTOOL_VERSION = 5.15
ETHTOOL_SITE = $(BR2_KERNEL_MIRROR)/software/network/ethtool

ETHTOOL_DEPENDENCIES = host-pkg-config

ETHTOOL_CONF_OPT = \
	--without-bash-completion-dir \
	--disable-pretty-dump

## netlink support requires Kernel 5.6+
ETHTOOL_CONF_OPT += --disable-netlink

$(eval $(call AUTOTARGETS,package,ethtool))
