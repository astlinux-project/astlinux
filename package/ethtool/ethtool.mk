#############################################################
#
# ethtool
#
#############################################################

ETHTOOL_VERSION = 3.1
ETHTOOL_SITE = http://www.kernel.org/pub/software/network/ethtool
#ETHTOOL_SITE = $(BR2_KERNEL_MIRROR)/software/network/ethtool

$(eval $(call AUTOTARGETS,package,ethtool))
