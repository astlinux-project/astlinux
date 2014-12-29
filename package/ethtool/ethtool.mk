#############################################################
#
# ethtool
#
#############################################################

ETHTOOL_VERSION = 3.18
ETHTOOL_SITE = $(BR2_KERNEL_MIRROR)/software/network/ethtool

$(eval $(call AUTOTARGETS,package,ethtool))
