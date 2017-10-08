#############################################################
#
# ipset
#
#############################################################

IPSET_VERSION = 6.34
IPSET_SOURCE = ipset-$(IPSET_VERSION).tar.bz2
IPSET_SITE = http://ipset.netfilter.org
IPSET_DEPENDENCIES = libmnl host-pkg-config

IPSET_CONF_OPT = \
	--disable-static \
	--with-kmod=no

$(eval $(call AUTOTARGETS,package,ipset))
