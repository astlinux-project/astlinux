#############################################################
#
# autoconf
#
#############################################################

AUTOCONF_VERSION = 2.71
AUTOCONF_SOURCE = autoconf-$(AUTOCONF_VERSION).tar.xz
AUTOCONF_SITE = $(BR2_GNU_MIRROR)/autoconf

HOST_AUTOCONF_CONF_ENV = \
	EMACS="no" \
	ac_cv_path_M4=$(HOST_DIR)/usr/bin/m4 \
	ac_cv_prog_gnu_m4_gnu=no

HOST_AUTOCONF_DEPENDENCIES = host-m4 host-libtool

$(eval $(call AUTOTARGETS,package,autoconf,host))

# variables used by other packages
AUTOCONF = $(HOST_DIR)/usr/bin/autoconf -I "$(ACLOCAL_DIR)" -I "$(ACLOCAL_HOST_DIR)"
AUTOHEADER = $(HOST_DIR)/usr/bin/autoheader -I "$(ACLOCAL_DIR)" -I "$(ACLOCAL_HOST_DIR)"
AUTORECONF = $(HOST_CONFIGURE_OPTS) ACLOCAL="$(ACLOCAL)" \
	AUTOCONF="$(AUTOCONF)" AUTOHEADER="$(AUTOHEADER)" \
	AUTOMAKE="$(AUTOMAKE)" AUTOPOINT=/bin/true GTKDOCIZE=/bin/true \
	$(HOST_DIR)/usr/bin/autoreconf -f -i
