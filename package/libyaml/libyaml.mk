#############################################################
#
# libyaml
#
#############################################################

LIBYAML_VERSION = 0.2.2
LIBYAML_SOURCE = yaml-$(LIBYAML_VERSION).tar.gz
LIBYAML_SITE = https://pyyaml.org/download/libyaml
LIBYAML_INSTALL_STAGING = YES

$(eval $(call AUTOTARGETS,package,libyaml))
