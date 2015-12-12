################################################################################
#
# iperf3
#
################################################################################

IPERF3_VERSION = 3.0.11
IPERF3_SOURCE = iperf-$(IPERF3_VERSION)-source.tar.gz
IPERF3_SITE = https://iperf.fr/download/iperf_3.0

$(eval $(call AUTOTARGETS,package,iperf3))
