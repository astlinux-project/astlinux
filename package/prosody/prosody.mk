#############################################################
#
# prosody
#
#############################################################

PROSODY_VERSION = 0.11.9
PROSODY_SOURCE = prosody-$(PROSODY_VERSION).tar.gz
PROSODY_SITE = https://prosody.im/downloads/source
PROSODY_DEPENDENCIES = lua libidn openssl luafilesystem luaexpat luasocket luasec

define PROSODY_CONFIGURE_CMDS
	# this is *NOT* GNU autoconf stuff
        (cd $(@D); \
                ./configure \
		--prefix=/usr \
		--with-lua-bin="$(HOST_DIR)/usr/bin" \
		--with-lua="$(STAGING_DIR)/usr" \
		--with-lua-include="$(STAGING_DIR)/usr/include" \
		--with-lua-lib="$(STAGING_DIR)/usr/lib" \
		--cflags="$(TARGET_CFLAGS) -fPIC -std=gnu99" \
		--ldflags="$(TARGET_LDFLAGS) -shared" \
		--datadir="/etc/prosody/data" \
		--c-compiler='$(TARGET_CC)' \
		--linker='$(TARGET_CC)' \
		--no-example-certs \
        )
endef

define PROSODY_POST_INSTALL
	mkdir -p $(TARGET_DIR)/stat/etc/prosody
	cp $(TARGET_DIR)/etc/prosody/prosody.cfg.lua $(TARGET_DIR)/stat/etc/prosody/prosody.cfg.lua
	rm -rf $(TARGET_DIR)/etc/prosody
	$(INSTALL) -m 0644 -D package/prosody/modules/mod_listusers.lua $(TARGET_DIR)/usr/lib/prosody/modules/
	$(INSTALL) -m 0644 -D package/prosody/modules/mod_log_auth.lua $(TARGET_DIR)/usr/lib/prosody/modules/
	$(INSTALL) -m 0644 -D package/prosody/modules/mod_pinger.lua $(TARGET_DIR)/usr/lib/prosody/modules/
	$(INSTALL) -m 0755 -D package/prosody/prosody.init $(TARGET_DIR)/etc/init.d/prosody
	$(INSTALL) -m 0644 -D package/prosody/prosody.logrotate $(TARGET_DIR)/etc/logrotate.d/prosody
	$(INSTALL) -m 0755 -D package/prosody/prosodycmd $(TARGET_DIR)/usr/bin/prosodycmd
	ln -s /tmp/etc/prosody $(TARGET_DIR)/etc/prosody
	ln -sf ../../init.d/prosody $(TARGET_DIR)/etc/runlevels/default/S58prosody
	ln -sf ../../init.d/prosody $(TARGET_DIR)/etc/runlevels/default/K02prosody
endef

PROSODY_POST_INSTALL_TARGET_HOOKS = PROSODY_POST_INSTALL

PROSODY_UNINSTALL_STAGING_OPT = --version

define PROSODY_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/prosody
	rm -f $(TARGET_DIR)/usr/bin/prosodyctl
	rm -f $(TARGET_DIR)/usr/bin/prosodycmd
	rm -rf $(TARGET_DIR)/usr/lib/prosody
	rm -rf $(TARGET_DIR)/stat/etc/prosody
	rm -f $(TARGET_DIR)/etc/prosody
	rm -f $(TARGET_DIR)/etc/init.d/prosody
	rm -f $(TARGET_DIR)/etc/runlevels/default/S58prosody
	rm -f $(TARGET_DIR)/etc/runlevels/default/K02prosody
endef

$(eval $(call AUTOTARGETS,package,prosody))
