#############################################################
#
# iftop
#
#############################################################

IFTOP_VERSION = 1.0pre4
IFTOP_SOURCE:=iftop-$(IFTOP_VERSION).tar.gz
IFTOP_SITE = http://www.ex-parrot.com/pdw/iftop/download
IFTOP_DEPENDENCIES = libpcap ncurses

$(eval $(call AUTOTARGETS,package,iftop))
