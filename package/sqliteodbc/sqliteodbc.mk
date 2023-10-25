#############################################################
#
# sqliteodbc
#
#############################################################

SQLITEODBC_VERSION = 0.99991
SQLITEODBC_SOURCE = sqliteodbc-$(SQLITEODBC_VERSION).tar.gz
SQLITEODBC_SITE = http://www.ch-werner.de/sqliteodbc
SQLITEODBC_SUBDIR = sqliteodbc-$(SQLITEODBC_VERSION)
SQLITEODBC_DEPENDENCIES = sqlite unixodbc libxml2

SQLITEODBC_INSTALL_STAGING = YES

SQLITEODBC_CONF_OPT = \
	--disable-static \
	--with-pic \
	--with-sqlite="$(STAGING_DIR)/usr" \
	--with-sqlite3="$(STAGING_DIR)/usr" \
	--with-sqlite4="$(STAGING_DIR)/usr" \
	--with-odbc="$(STAGING_DIR)/usr" \
	--with-libxml2="$(STAGING_DIR)/usr/bin/xml2-config"

define SQLITEODBC_INSTALL_TARGET_CMDS
        cp -a $(STAGING_DIR)/usr/lib/libsqlite3odbc*.so $(TARGET_DIR)/usr/lib/
        #cp -a $(STAGING_DIR)/usr/lib/libsqlite3_mod_*.so $(TARGET_DIR)/usr/lib/
endef

define SQLITEODBC_UNINSTALL_TARGET_CMDS
        rm -f $(TARGET_DIR)/usr/lib/libsqlite3odbc*.so
        #rm -f $(TARGET_DIR)/usr/lib/libsqlite3_mod_*.so
endef

$(eval $(call AUTOTARGETS,package,sqliteodbc))
