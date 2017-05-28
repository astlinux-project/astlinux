#############################################################
#
# bridge-utils
#
#############################################################

BRIDGE_UTILS_VERSION = 1.6
BRIDGE_UTILS_SOURCE = bridge-utils-$(BRIDGE_UTILS_VERSION).tar.xz
BRIDGE_UTILS_SITE = $(BR2_KERNEL_MIRROR)/linux/utils/net/bridge-utils
BRIDGE_UTILS_AUTORECONF = YES
BRIDGE_UTILS_CONF_OPT = --with-linux-headers=$(LINUX_HEADERS_DIR)

$(eval $(call AUTOTARGETS,package,bridge-utils))
