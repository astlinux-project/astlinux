################################################################################
#
# iprange
#
################################################################################

IPRANGE_VERSION = 1.0.4
IPRANGE_SOURCE = iprange-$(IPRANGE_VERSION).tar.gz
IPRANGE_SITE = https://github.com/firehol/iprange/releases/download/v$(IPRANGE_VERSION)

$(eval $(call AUTOTARGETS,package,iprange))
