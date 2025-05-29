#############################################################
#
# sqlite
#
#############################################################

SQLITE_VERSION = 3500000
SQLITE_SOURCE = sqlite-autoconf-$(SQLITE_VERSION).tar.gz
SQLITE_SITE = https://www.sqlite.org/2025
SQLITE_INSTALL_STAGING = YES

SQLITE_DEPENDENCIES = zlib

SQLITE_CFLAGS += -DSQLITE_ENABLE_COLUMN_METADATA

SQLITE_CONF_OPT = \
	--disable-math \
	--disable-fts3 \
	--enable-fts4 \
	--disable-fts5 \
	--enable-geopoly \
	--enable-rtree \
	--disable-session \
	--disable-static \
	--disable-static-shell \
	--disable-readline \
	--enable-threadsafe \
	--localstatedir=/var

SQLITE_CONF_ENV = CFLAGS="$(TARGET_CFLAGS) $(SQLITE_CFLAGS)"

define SQLITE_CONFIGURE_CMDS
	(cd $(@D); $(TARGET_CONFIGURE_OPTS) $(SQLITE_CONF_ENV) ./configure \
		--prefix=/usr \
		--host="$(GNU_TARGET_NAME)" \
		--build="$(GNU_HOST_NAME)" \
		--sysroot="$(STAGING_DIR)" \
		$(SQLITE_CONF_OPT) \
	)
endef

define SQLITE_BUILD_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) -C $(@D)
endef

define SQLITE_INSTALL_STAGING_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) DESTDIR="$(STAGING_DIR)" -C $(@D) install
endef

define SQLITE_INSTALL_TARGET_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) DESTDIR="$(TARGET_DIR)" -C $(@D) install
endef

define SQLITE_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/sqlite3
	rm -f $(TARGET_DIR)/usr/lib/libsqlite3.so*
endef

define SQLITE_UNINSTALL_STAGING_CMDS
	rm -f $(STAGING_DIR)/usr/bin/sqlite3
	rm -f $(STAGING_DIR)/usr/lib/libsqlite3.so*
	rm -f $(STAGING_DIR)/usr/lib/pkgconfig/sqlite3.pc
	rm -f $(STAGING_DIR)/usr/include/sqlite3*.h
endef

$(eval $(call GENTARGETS,package,sqlite))
