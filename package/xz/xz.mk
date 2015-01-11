#############################################################
#
# xz
#
#############################################################
XZ_VERSION = 5.2.0
XZ_SOURCE = xz-$(XZ_VERSION).tar.bz2
XZ_SITE = http://tukaani.org/xz
XZ_INSTALL_STAGING = YES
XZ_CONF_ENV = ac_cv_prog_cc_c99='-std=gnu99'

ifeq ($(BR2_TOOLCHAIN_HAS_THREADS),y)
XZ_CONF_OPT = --enable-threads
else
XZ_CONF_OPT = --disable-threads
endif

$(eval $(call AUTOTARGETS,package,xz))
$(eval $(call AUTOTARGETS,package,xz,host))
