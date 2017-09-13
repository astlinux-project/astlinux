################################################################################
#
# libtasn1
#
################################################################################

LIBTASN1_VERSION = 4.12
LIBTASN1_SITE = $(BR2_GNU_MIRROR)/libtasn1
LIBTASN1_DEPENDENCIES = host-bison
LIBTASN1_INSTALL_STAGING = YES
# 'missing' fallback logic botched so disable it completely
LIBTASN1_CONF_ENV = MAKEINFO="true"

$(eval $(call AUTOTARGETS,package,libtasn1))
