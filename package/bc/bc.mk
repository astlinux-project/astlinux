#############################################################
#
# bc
#
#############################################################

BC_VERSION = 1.06
BC_SOURCE:=bc-$(BC_VERSION).tar.gz
BC_SITE = http://ftp.gnu.org/gnu/bc
BC_DEPENDENCIES = host-bison host-flex

define BC_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/bc/bc $(TARGET_DIR)/usr/bin/bc
endef

define BC_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/bc
endef

$(eval $(call AUTOTARGETS,package,bc))
