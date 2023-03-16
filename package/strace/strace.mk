#############################################################
#
# strace
#
#############################################################

STRACE_VERSION = 6.2
STRACE_SOURCE = strace-$(STRACE_VERSION).tar.xz
STRACE_SITE = https://github.com/strace/strace/releases/download/v$(STRACE_VERSION)

STRACE_CONF_OPT = \
	--enable-mpers=no

define STRACE_REMOVE_STRACE_GRAPH
	rm -f $(TARGET_DIR)/usr/bin/strace-graph
endef

STRACE_POST_INSTALL_TARGET_HOOKS += STRACE_REMOVE_STRACE_GRAPH

$(eval $(call AUTOTARGETS,package,strace))
