#############################################################
#
# prosody
#
#############################################################

PROSODY_VERSION = 0.8.2
PROSODY_SOURCE = prosody-$(PROSODY_VERSION).tar.gz
PROSODY_SITE = http://prosody.im/downloads/source
PROSODY_DEPENDENCIES = host-lua lua libidn openssl luafilesystem luaexpat luasocket luasec

define PROSODY_CONFIGURE_CMDS
	# this is *NOT* GNU autoconf stuff
        (cd $(@D); \
                ./configure \
		--prefix=/usr \
		--with-lua="$(HOST_DIR)/usr" \
		--with-lua-include="$(STAGING_DIR)/usr/include" \
		--with-lua-lib="$(STAGING_DIR)/usr/lib" \
		--cflags="$(TARGET_CFLAGS) $(TARGET_CPPFLAGS) -fPIC -std=gnu99" \
		--ldflags="$(TARGET_LDFLAGS) -shared" \
		--datadir="/etc/prosody/data" \
		--c-compiler='$(TARGET_CC)' \
		--linker='$(TARGET_LD)' \
        )
endef

$(eval $(call AUTOTARGETS,package,prosody))
