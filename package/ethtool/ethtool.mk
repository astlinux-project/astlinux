#############################################################
#
# ethtool
#
#############################################################

ETHTOOL_VERSION = 3.16
ETHTOOL_SITE = $(BR2_KERNEL_MIRROR)/software/network/ethtool

$(eval $(call AUTOTARGETS,package,ethtool))
