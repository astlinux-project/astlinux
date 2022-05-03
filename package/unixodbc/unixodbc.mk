#############################################################
#
# unixodbc
#
#############################################################

UNIXODBC_VERSION = 2.3.10
UNIXODBC_SOURCE = unixODBC-$(UNIXODBC_VERSION).tar.gz
UNIXODBC_SITE = http://www.unixodbc.org
UNIXODBC_DEPENDENCIES = host-bison host-flex libtool $(if $(BR2_PACKAGE_FLEX),flex)

UNIXODBC_INSTALL_STAGING = YES

UNIXODBC_CONF_OPT = \
	--disable-static \
	--disable-gui \
	--disable-readline \
	--disable-editline \
	--with-pic \
	--disable-drivers

define UNIXODBC_INSTALL_TARGET_CMDS
	cp -a $(STAGING_DIR)/usr/lib/libodbc*so* $(TARGET_DIR)/usr/lib/
	$(INSTALL) -m 0755 -D package/unixodbc/unixodbc.init $(TARGET_DIR)/etc/init.d/unixodbc
	$(INSTALL) -m 0755 -D $(STAGING_DIR)/usr/bin/isql $(TARGET_DIR)/usr/bin/isql
	$(INSTALL) -m 0755 -D $(STAGING_DIR)/usr/bin/odbcinst $(TARGET_DIR)/usr/bin/odbcinst
	ln -sf /tmp/etc/odbc.ini $(TARGET_DIR)/etc/odbc.ini
	ln -sf /tmp/etc/odbcinst.ini $(TARGET_DIR)/etc/odbcinst.ini
	ln -sf /tmp/etc/ODBCDataSources $(TARGET_DIR)/etc/ODBCDataSources
	ln -sf ../../init.d/unixodbc $(TARGET_DIR)/etc/runlevels/default/S01unixodbc
endef

define UNIXODBC_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/lib/libodbc*so*
	rm -f $(TARGET_DIR)/etc/init.d/unixodbc
	rm -f $(TARGET_DIR)/usr/bin/isql
	rm -f $(TARGET_DIR)/usr/bin/odbcinst
	rm -f $(TARGET_DIR)/etc/odbc.ini
	rm -f $(TARGET_DIR)/etc/odbcinst.ini
	rm -f $(TARGET_DIR)/etc/ODBCDataSources
	rm -f $(TARGET_DIR)/etc/runlevels/default/S01unixodbc
endef

$(eval $(call AUTOTARGETS,package,unixodbc))
