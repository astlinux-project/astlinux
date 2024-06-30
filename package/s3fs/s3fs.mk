################################################################################
#
# s3fs
#
################################################################################

S3FS_VERSION = 1.94
S3FS_SOURCE = s3fs-fuse-$(S3FS_VERSION).tar.gz
S3FS_SITE = https://github.com/s3fs-fuse/s3fs-fuse/archive/v$(S3FS_VERSION)
S3FS_AUTORECONF = YES
S3FS_DEPENDENCIES = libfuse libcurl libxml2 openssl

S3FS_CONF_OPT = \
	--with-openssl

define S3FS_POST_INSTALL
	$(INSTALL) -m 0644 -D package/s3fs/mime.types $(TARGET_DIR)/usr/share/mime.types
	ln -sf /usr/share/mime.types $(TARGET_DIR)/etc/mime.types
	$(INSTALL) -m 0755 -D package/s3fs/s3fs.init $(TARGET_DIR)/etc/init.d/s3fs
	ln -sf ../../init.d/s3fs $(TARGET_DIR)/etc/runlevels/default/S55s3fs
	ln -sf ../../init.d/s3fs $(TARGET_DIR)/etc/runlevels/default/K11s3fs
endef

S3FS_POST_INSTALL_TARGET_HOOKS = S3FS_POST_INSTALL

define S3FS_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/share/mime.types
	rm -f $(TARGET_DIR)/etc/mime.types
	rm -f $(TARGET_DIR)/etc/init.d/s3fs
	rm -f $(TARGET_DIR)/etc/runlevels/default/S55s3fs
	rm -f $(TARGET_DIR)/etc/runlevels/default/K11s3fs
	rm -f $(TARGET_DIR)/usr/bin/s3fs
endef

$(eval $(call AUTOTARGETS,package,s3fs))
