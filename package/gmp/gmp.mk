
################################################################################
#
# gmp
#
################################################################################

GMP_VERSION = 6.1.2
GMP_SITE = $(BR2_GNU_MIRROR)/gmp
GMP_SOURCE = gmp-$(GMP_VERSION).tar.xz
GMP_INSTALL_STAGING = YES
GMP_DEPENDENCIES = host-m4
HOST_GMP_DEPENDENCIES = host-m4

$(eval $(call AUTOTARGETS,package,gmp))
$(eval $(call AUTOTARGETS,package,gmp,host))
