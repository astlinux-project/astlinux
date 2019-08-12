#############################################################
#
# ethtool
#
#############################################################

ETHTOOL_VERSION = 5.2
ETHTOOL_SITE = $(BR2_KERNEL_MIRROR)/software/network/ethtool

ETHTOOL_DEPENDENCIES = host-pkg-config

ETHTOOL_CONF_OPT = \
	--without-bash-completion-dir \
	--disable-pretty-dump

$(eval $(call AUTOTARGETS,package,ethtool))
