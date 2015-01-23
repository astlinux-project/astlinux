#############################################################
#
# bison
#
#############################################################

BISON_VERSION = 3.0.2
BISON_SOURCE = bison-$(BISON_VERSION).tar.xz
BISON_SITE = $(BR2_GNU_MIRROR)/bison
BISON_AUTORECONF = YES
HOST_BISON_AUTORECONF = YES

BISON_DEPENDENCIES = m4
BISON_CONF_ENV = ac_cv_path_M4=/usr/bin/m4

HOST_BISON_DEPENDENCIES = host-m4
HOST_BISON_CONF_ENV = ac_cv_path_M4=$(HOST_DIR)/usr/bin/m4

define BISON_DISABLE_EXAMPLES
	echo 'all install:' > $(@D)/examples/Makefile
endef

BISON_POST_CONFIGURE_HOOKS += BISON_DISABLE_EXAMPLES

$(eval $(call AUTOTARGETS,package,bison))
$(eval $(call AUTOTARGETS,package,bison,host))
