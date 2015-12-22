#############################################################
#
# strace
#
#############################################################

STRACE_VERSION = 4.11
STRACE_SOURCE = strace-$(STRACE_VERSION).tar.xz
STRACE_SITE = http://downloads.sourceforge.net/project/strace/strace/$(STRACE_VERSION)

STRACE_CONF_ENV = \
	st_cv_m32_mpers=no \
	st_cv_mx32_mpers=no \
	ac_cv_header_linux_if_packet_h=yes \
	ac_cv_header_linux_netlink_h=yes

define STRACE_REMOVE_STRACE_GRAPH
	rm -f $(TARGET_DIR)/usr/bin/strace-graph
endef

STRACE_POST_INSTALL_TARGET_HOOKS += STRACE_REMOVE_STRACE_GRAPH

$(eval $(call AUTOTARGETS,package,strace))
