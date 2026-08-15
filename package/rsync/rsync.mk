#############################################################
#
# rsync
#
#############################################################

RSYNC_VERSION = 3.5.0
RSYNC_SOURCE = rsync-$(RSYNC_VERSION).tar.gz
RSYNC_SITE = https://rsync.samba.org/ftp/rsync/src
RSYNC_DEPENDENCIES = host-pkg-config zlib popt
# We know that our C library is modern enough for C99 vsnprintf() and mkstemp(). Since
# configure can't detect this, we tell configure that vsnprintf() and mkstemp() is safe.
RSYNC_CONF_ENV = \
	rsync_cv_HAVE_C99_VSNPRINTF=yes \
	rsync_cv_HAVE_SECURE_MKSTEMP=yes

RSYNC_CONF_OPT = \
	--disable-debug \
	--with-nobody-user=nobody \
	--with-nobody-group=nobody \
	--with-included-zlib=no \
	--with-included-popt=no \
	--disable-roll-simd \
	--disable-md5-asm \
	--disable-lz4 \
	--disable-xxhash \
	--disable-zstd

ifeq ($(BR2_PACKAGE_ACL),y)
	RSYNC_DEPENDENCIES += acl
else
	RSYNC_CONF_OPT += --disable-acl-support
endif

ifeq ($(BR2_PACKAGE_OPENSSL),y)
	RSYNC_DEPENDENCIES += openssl
	RSYNC_CONF_OPT += --enable-openssl
else
	RSYNC_CONF_OPT += --disable-openssl
endif

define RSYNC_INSTALL_TARGET_CMDS
	$(INSTALL) -D -m 0755 $(@D)/rsync $(TARGET_DIR)/usr/bin/rsync
endef

define RSYNC_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/rsync
endef

$(eval $(call AUTOTARGETS,package,rsync))
