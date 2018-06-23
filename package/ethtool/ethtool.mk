#############################################################
#
# ethtool
#
#############################################################

ETHTOOL_VERSION = 4.17
ETHTOOL_SITE = $(BR2_KERNEL_MIRROR)/software/network/ethtool

ETHTOOL_CONF_OPT = \
	--disable-pretty-dump

$(eval $(call AUTOTARGETS,package,ethtool))
