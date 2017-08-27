#############################################################
#
# iperf
#
#############################################################
IPERF_VERSION = 2.0.9
IPERF_SOURCE = iperf-$(IPERF_VERSION).tar.gz
IPERF_SITE = https://s3.amazonaws.com/files.astlinux-project
#IPERF_SITE = http://downloads.sourceforge.net/project/iperf2

IPERF_CONF_ENV = \
	ac_cv_func_malloc_0_nonnull=yes

IPERF_CONF_OPT = \
	--disable-dependency-tracking \
	--disable-web100

$(eval $(call AUTOTARGETS,package,iperf))
