#############################################################
#
# rsync
#
#############################################################

RSYNC_VERSION = 3.1.3
RSYNC_SOURCE = rsync-$(RSYNC_VERSION).tar.gz
RSYNC_SITE = http://rsync.samba.org/ftp/rsync/src
RSYNC_DEPENDENCIES = zlib popt
RSYNC_CONF_OPT = \
	$(if $(BR2_ENABLE_DEBUG),--enable-debug,--disable-debug) \
	--with-included-zlib=no \
	--with-included-popt=no

ifeq ($(BR2_PACKAGE_ACL),y)
	RSYNC_DEPENDENCIES += acl
else
	RSYNC_CONF_OPT += --disable-acl-support
endif

$(eval $(call AUTOTARGETS,package,rsync))
