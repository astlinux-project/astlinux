#############################################################
#
# e2fsprogs
#
#############################################################

E2FSPROGS_VERSION = 1.46.5
E2FSPROGS_SITE = http://downloads.sourceforge.net/project/e2fsprogs/e2fsprogs/v$(E2FSPROGS_VERSION)
E2FSPROGS_INSTALL_STAGING = YES
E2FSPROGS_INSTALL_STAGING_OPT = DESTDIR=$(STAGING_DIR) LDCONFIG=true install-libs
E2FSPROGS_INSTALL_TARGET_OPT = DESTDIR=$(TARGET_DIR) LDCONFIG=true install

E2FSPROGS_DEPENDENCIES = host-bison host-pkg-config util-linux
# we don't have a host-util-linux
HOST_E2FSPROGS_DEPENDENCIES = host-bison host-pkg-config

# e4defrag doesn't build on older systems like RHEL5.x, and we don't
# need it on the host anyway.
# Disable fuse2fs as well to avoid carrying over deps, and it's unused
HOST_E2FSPROGS_CONF_OPT += --disable-defrag --disable-fuse2fs

E2FSPROGS_CONF_OPT = \
	$(if $(BR2_PREFER_STATIC_LIB),,--enable-elf-shlibs) \
	$(if $(BR2_PACKAGE_E2FSPROGS_DEBUGFS),,--disable-debugfs) \
	$(if $(BR2_PACKAGE_E2FSPROGS_E2IMAGE),,--disable-imager) \
	--disable-defrag \
	--disable-fuse2fs \
	$(if $(BR2_PACKAGE_E2FSPROGS_RESIZE2FS),,--disable-resizer) \
	--disable-uuidd \
	--disable-libblkid \
	--disable-libuuid \
	--enable-fsck \
	--disable-e2initrd-helper \
	--disable-testio-debug \
	--disable-rpath \
	--enable-symlink-install

# Some programs are built for the host, but use definitions guessed by
# the configure script (i.e with the cross-compiler). Help them by
# saying that <sys/stat.h> is available on the host, which is needed
# for util/subst.c to build properly.
E2FSPROGS_CONF_ENV += BUILD_CFLAGS="-DHAVE_SYS_STAT_H"

# Disable use of the host magic.h, as on older hosts (e.g. RHEL 5)
# it doesn't provide definitions expected by e2fsprogs support lib.
HOST_E2FSPROGS_CONF_ENV += \
	ac_cv_header_magic_h=no \
	ac_cv_lib_magic_magic_file=no

E2FSPROGS_MAKE_OPT = \
	LDCONFIG=true

define HOST_E2FSPROGS_INSTALL_CMDS
	$(HOST_MAKE_ENV) $(MAKE) -C $(@D) install install-libs
endef

# binaries to keep or remove
E2FSPROGS_BINTARGETS_$(BR2_PACKAGE_E2FSPROGS_BADBLOCKS) += usr/sbin/badblocks
E2FSPROGS_BINTARGETS_$(BR2_PACKAGE_E2FSPROGS_CHATTR) += usr/bin/chattr
E2FSPROGS_BINTARGETS_$(BR2_PACKAGE_E2FSPROGS_DUMPE2FS) += usr/sbin/dumpe2fs
E2FSPROGS_BINTARGETS_$(BR2_PACKAGE_E2FSPROGS_E2FREEFRAG) += usr/sbin/e2freefrag
E2FSPROGS_BINTARGETS_$(BR2_PACKAGE_E2FSPROGS_E2FSCK) += usr/sbin/e2fsck
E2FSPROGS_BINTARGETS_$(BR2_PACKAGE_E2FSPROGS_E2LABEL) += usr/sbin/e2label
E2FSPROGS_BINTARGETS_$(BR2_PACKAGE_E2FSPROGS_E2UNDO) += usr/sbin/e2undo
E2FSPROGS_BINTARGETS_$(BR2_PACKAGE_E2FSPROGS_FILEFRAG) += usr/sbin/filefrag
E2FSPROGS_BINTARGETS_$(BR2_PACKAGE_E2FSPROGS_FSCK) += usr/sbin/fsck
E2FSPROGS_BINTARGETS_$(BR2_PACKAGE_E2FSPROGS_LOGSAVE) += usr/sbin/logsave
E2FSPROGS_BINTARGETS_$(BR2_PACKAGE_E2FSPROGS_LSATTR) += usr/bin/lsattr
E2FSPROGS_BINTARGETS_$(BR2_PACKAGE_E2FSPROGS_MKE2FS) += usr/sbin/mke2fs
E2FSPROGS_BINTARGETS_$(BR2_PACKAGE_E2FSPROGS_MKLOSTFOUND) += usr/sbin/mklost+found
E2FSPROGS_BINTARGETS_$(BR2_PACKAGE_E2FSPROGS_TUNE2FS) += usr/sbin/tune2fs
E2FSPROGS_BINTARGETS_ += usr/sbin/e4crypt

define E2FSPROGS_TARGET_REMOVE_UNNEEDED
	rm -f $(addprefix $(TARGET_DIR)/, $(E2FSPROGS_BINTARGETS_))
	## Remove unneeded development stuff on target
	rm -f $(TARGET_DIR)/usr/bin/compile_et $(TARGET_DIR)/usr/bin/mk_cmds
	rm -rf $(TARGET_DIR)/usr/share/et
	rm -rf $(TARGET_DIR)/usr/share/ss
endef

E2FSPROGS_POST_INSTALL_TARGET_HOOKS += E2FSPROGS_TARGET_REMOVE_UNNEEDED

# If BusyBox is included, its configuration may supply its own variant
# of ext2-related tools. Since Buildroot desires having full blown
# variants take precedence (in this case, e2fsprogs), we want to remove
# BusyBox's variant of e2fsprogs provided binaries. e2fsprogs targets
# /usr/{bin,sbin} where BusyBox targets /{bin,sbin}. We will attempt to
# remove BusyBox-generated ext2-related tools from /{bin,sbin}. We need
# to do this in the pre-install stage to ensure we do not accidentally
# remove e2fsprogs's binaries in usr-merged environments (ie. if they
# are removed, they would be re-installed in this package's install
# stage).
ifeq ($(BR2_PACKAGE_BUSYBOX),y)
E2FSPROGS_DEPENDENCIES += busybox

define E2FSPROGS_REMOVE_BUSYBOX_APPLETS
	rm -f $(TARGET_DIR)/bin/chattr
	rm -f $(TARGET_DIR)/bin/lsattr
	rm -f $(TARGET_DIR)/sbin/fsck
	rm -f $(TARGET_DIR)/sbin/tune2fs
	rm -f $(TARGET_DIR)/sbin/e2label
endef
E2FSPROGS_PRE_INSTALL_TARGET_HOOKS += E2FSPROGS_REMOVE_BUSYBOX_APPLETS
endif

$(eval $(call AUTOTARGETS,package,e2fsprogs))
$(eval $(call AUTOTARGETS,package,e2fsprogs,host))
