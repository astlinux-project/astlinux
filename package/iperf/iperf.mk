#############################################################
#
# iperf
#
#############################################################

IPERF_VERSION = 2.1.7
IPERF_SOURCE = iperf-$(IPERF_VERSION).tar.gz
IPERF_SITE = http://downloads.sourceforge.net/project/iperf2

IPERF_CONF_OPT = \
	--disable-dependency-tracking \
	--disable-web100

$(eval $(call AUTOTARGETS,package,iperf))
