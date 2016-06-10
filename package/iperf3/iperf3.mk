################################################################################
#
# iperf3
#
################################################################################

IPERF3_VERSION = 3.0.12
IPERF3_SOURCE = iperf-$(IPERF3_VERSION)-source.tar.gz
IPERF3_SITE = https://iperf.fr/download/source

$(eval $(call AUTOTARGETS,package,iperf3))
