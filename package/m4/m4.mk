#############################################################
#
# m4
#
#############################################################
M4_VERSION = 1.4.17
M4_SOURCE = m4-$(M4_VERSION).tar.bz2
M4_SITE = $(BR2_GNU_MIRROR)/m4

HOST_M4_CONF_OPT = --disable-static

$(eval $(call AUTOTARGETS,package,m4,host))
