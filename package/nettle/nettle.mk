################################################################################
#
# nettle
#
################################################################################

NETTLE_VERSION = 3.3
NETTLE_SITE = http://www.lysator.liu.se/~nisse/archive
NETTLE_DEPENDENCIES = gmp
NETTLE_INSTALL_STAGING = YES
NETTLE_LICENSE = Dual GPL-2.0+/LGPL-3.0+
NETTLE_LICENSE_FILES = COPYING.LESSERv3 COPYINGv2
# don't include openssl support for (unused) examples as it has problems
# with static linking
NETTLE_CONF_OPT = --disable-openssl

$(eval $(call AUTOTARGETS,package,nettle))
