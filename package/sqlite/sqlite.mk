#############################################################
#
# sqlite
#
#############################################################

SQLITE_VERSION = 3430100
SQLITE_SOURCE = sqlite-autoconf-$(SQLITE_VERSION).tar.gz
SQLITE_SITE = https://www.sqlite.org/2023
SQLITE_INSTALL_STAGING = YES

# sqlite-0001-editline-configure-fix.patch
SQLITE_AUTORECONF = YES

SQLITE_DEPENDENCIES = zlib

SQLITE_CFLAGS += -DSQLITE_ENABLE_COLUMN_METADATA

SQLITE_CONF_ENV = CFLAGS="$(TARGET_CFLAGS) $(SQLITE_CFLAGS)"

SQLITE_CONF_OPT = \
	--disable-math \
	--disable-fts5 \
	--disable-static-shell \
	--enable-threadsafe \
	--localstatedir=/var

ifeq ($(BR2_PACKAGE_READLINE),y)
SQLITE_DEPENDENCIES += readline
SQLITE_CONF_OPT += --disable-editline --enable-readline
else ifeq ($(BR2_PACKAGE_LIBEDIT),y)
SQLITE_DEPENDENCIES += libedit
SQLITE_CONF_OPT += --enable-editline --disable-readline
else
SQLITE_CONF_OPT += --disable-editline --disable-readline
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
