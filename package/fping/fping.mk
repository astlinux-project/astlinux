#############################################################
#
# fping
#
#############################################################
FPING_VERSION = 3.12
FPING_SITE = http://fping.org/dist
FPING_SOURCE = fping-$(FPING_VERSION).tar.gz

FPING_CONF_OPT = \
	--enable-ipv6

define FPING_INSTALL_TARGET_CMDS
	# Must set SUID for zabbix user
	$(INSTALL) -D -m 4711 $(@D)/src/fping $(TARGET_DIR)/usr/sbin/
	$(INSTALL) -D -m 4711 $(@D)/src/fping6 $(TARGET_DIR)/usr/sbin/
endef

define FPING_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/fping
	rm -f $(TARGET_DIR)/usr/sbin/fping6
endef

$(eval $(call AUTOTARGETS,package,fping))
