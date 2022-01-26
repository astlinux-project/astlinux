#############################################################
#
# rtpproxy
#
#############################################################

RTPPROXY_VERSION = 2.0.0
RTPPROXY_SITE = https://astlinux-project.org/files
#RTPPROXY_SITE = https://github.com/sippy/rtpproxy
RTPPROXY_SOURCE = rtpproxy-$(RTPPROXY_VERSION).tar.gz

RTPPROXY_AUTORECONF = YES

define RTPPROXY_TARGET_CLEANUP
	rm -f $(TARGET_DIR)/usr/bin/rtpproxy_debug
endef
RTPPROXY_POST_INSTALL_TARGET_HOOKS += RTPPROXY_TARGET_CLEANUP

$(eval $(call AUTOTARGETS,package,rtpproxy))
