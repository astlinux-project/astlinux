#############################################################
#
# strace
#
#############################################################

STRACE_VERSION = 4.19
STRACE_SOURCE = strace-$(STRACE_VERSION).tar.xz
STRACE_SITE = http://downloads.sourceforge.net/project/strace/strace/$(STRACE_VERSION)

define STRACE_REMOVE_STRACE_GRAPH
	rm -f $(TARGET_DIR)/usr/bin/strace-graph
endef

STRACE_POST_INSTALL_TARGET_HOOKS += STRACE_REMOVE_STRACE_GRAPH

$(eval $(call AUTOTARGETS,package,strace))
