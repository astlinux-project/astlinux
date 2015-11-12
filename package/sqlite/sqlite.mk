#############################################################
#
# sqlite
#
#############################################################

SQLITE_VERSION = 3090200
SQLITE_SOURCE = sqlite-autoconf-$(SQLITE_VERSION).tar.gz
SQLITE_SITE = http://www.sqlite.org/2015
SQLITE_INSTALL_STAGING = YES

SQLITE_CFLAGS += -DSQLITE_ENABLE_COLUMN_METADATA

SQLITE_CONF_ENV = CFLAGS="$(TARGET_CFLAGS) $(SQLITE_CFLAGS)"

SQLITE_CONF_OPT = \
	--enable-threadsafe \
	--localstatedir=/var

ifeq ($(BR2_PACKAGE_SQLITE_READLINE),y)
SQLITE_DEPENDENCIES += ncurses readline
else
SQLITE_CONF_OPT += --disable-readline
endif

define SQLITE_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/sqlite3
	rm -f $(TARGET_DIR)/usr/lib/libsqlite3*
endef

define SQLITE_UNINSTALL_STAGING_CMDS
	rm -f $(STAGING_DIR)/usr/bin/sqlite3
	rm -f $(STAGING_DIR)/usr/lib/libsqlite3*
	rm -f $(STAGING_DIR)/usr/lib/pkgconfig/sqlite3.pc
	rm -f $(STAGING_DIR)/usr/include/sqlite3*.h
endef

$(eval $(call AUTOTARGETS,package,sqlite))
