#############################################################
#
# fping
#
#############################################################

FPING_VERSION = 4.1
FPING_SITE = https://fping.org/dist
FPING_SOURCE = fping-$(FPING_VERSION).tar.gz
# Fix configure warning
FPING_AUTORECONF = YES

define FPING_INSTALL_TARGET_CMDS
	# Must set SUID for zabbix user
	$(INSTALL) -D -m 4711 $(@D)/src/fping $(TARGET_DIR)/usr/sbin/fping
	ln -sf fping $(TARGET_DIR)/usr/sbin/fping6
endef

define FPING_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/fping
	rm -f $(TARGET_DIR)/usr/sbin/fping6
endef

$(eval $(call AUTOTARGETS,package,fping))
