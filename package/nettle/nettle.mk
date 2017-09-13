################################################################################
#
# nettle
#
################################################################################

NETTLE_VERSION = 3.3
NETTLE_SITE = http://www.lysator.liu.se/~nisse/archive
NETTLE_DEPENDENCIES = gmp
NETTLE_INSTALL_STAGING = YES
# don't include openssl support for (unused) examples as it has problems
# with static linking
NETTLE_CONF_OPT = --disable-openssl

$(eval $(call AUTOTARGETS,package,nettle))
