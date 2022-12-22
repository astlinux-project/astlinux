#############################################################
#
# bison
#
#############################################################

BISON_VERSION = 3.7.6
BISON_SOURCE = bison-$(BISON_VERSION).tar.xz
BISON_SITE = $(BR2_GNU_MIRROR)/bison

HOST_BISON_DEPENDENCIES = host-m4
HOST_BISON_CONF_ENV = \
	ac_cv_path_M4=$(HOST_DIR)/usr/bin/m4 \
	ac_cv_libtextstyle=no
HOST_BISON_CONF_OPT = --enable-relocatable

$(eval $(call AUTOTARGETS,package,bison,host))
