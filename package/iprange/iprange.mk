################################################################################
#
# iprange
#
################################################################################

IPRANGE_VERSION = 2.0.0
IPRANGE_SOURCE = iprange-$(IPRANGE_VERSION).tar.gz
IPRANGE_SITE = https://github.com/firehol/iprange/releases/download/v$(IPRANGE_VERSION)

# patch configure.ac
IPRANGE_AUTORECONF = YES

$(eval $(call AUTOTARGETS,package,iprange))
