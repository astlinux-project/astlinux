#############################################################
#
# ethtool
#
#############################################################

ETHTOOL_VERSION = 3.9
ETHTOOL_SITE = http://www.kernel.org/pub/software/network/ethtool

$(eval $(call AUTOTARGETS,package,ethtool))
