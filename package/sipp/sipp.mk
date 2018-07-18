################################################################################
#
# sipp
#
################################################################################

SIPP_VERSION = 3.5.2
SIPP_SOURCE = sipp-$(SIPP_VERSION).tar.gz
SIPP_SITE = https://github.com/SIPp/sipp/releases/download/v$(SIPP_VERSION)
SIPP_DEPENDENCIES = libpcap ncurses openssl

SIPP_CONF_OPT = \
	--with-pcap \
	--with-openssl

$(eval $(call AUTOTARGETS,package,sipp))
