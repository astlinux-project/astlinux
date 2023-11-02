################################################################################
#
# iperf3
#
################################################################################

IPERF3_VERSION = 3.15
IPERF3_SOURCE = iperf-$(IPERF3_VERSION).tar.gz
IPERF3_SITE = https://downloads.es.net/pub/iperf

ifeq ($(BR2_PACKAGE_OPENSSL),y)
IPERF3_CONF_OPT += --with-openssl=$(STAGING_DIR)/usr
IPERF3_DEPENDENCIES += openssl
else
IPERF3_CONF_OPT += --without-openssl
endif

$(eval $(call AUTOTARGETS,package,iperf3))
