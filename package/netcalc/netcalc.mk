################################################################################
#
# netcalc
#
################################################################################

NETCALC_VERSION = 2.1.7
NETCALC_SOURCE = netcalc-$(NETCALC_VERSION).tar.gz
NETCALC_SITE = https://github.com/troglobit/netcalc/releases/download/v$(NETCALC_VERSION)

NETCALC_CONF_OPT = \
	--disable-ipcalc-symlink

$(eval $(call AUTOTARGETS,package,netcalc))
