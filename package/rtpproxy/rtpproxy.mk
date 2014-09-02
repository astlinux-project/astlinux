#############################################################
#
# rtpproxy
#
#############################################################

RTPPROXY_VERSION = 2013-07-02
RTPPROXY_SITE = http://files.astlinux.org
#RTPPROXY_SITE = https://github.com/miconda/rtpproxy
RTPPROXY_SOURCE = rtpproxy-$(RTPPROXY_VERSION).tar.gz

$(eval $(call AUTOTARGETS,package,rtpproxy))
