################################################################################
#
# tarsnap
#
################################################################################

TARSNAP_VERSION = 1.0.40
TARSNAP_SOURCE = tarsnap-autoconf-$(TARSNAP_VERSION).tgz
TARSNAP_SITE = https://www.tarsnap.com/download

## Tarsnap license:
## Redistribution and use in source and binary forms, without modification,
## is permitted for the sole purpose of using the "tarsnap" backup service
## provided by Colin Percival.

TARSNAP_DEPENDENCIES = openssl zlib e2fsprogs

TARSNAP_CONF_OPT = \
	--disable-xattr \
	--disable-acl \
	--without-bz2lib \
	--without-lzmadec \
	--without-lzma \
	--with-conf-no-sample

define TARSNAP_POST_INSTALL
	$(INSTALL) -m 0644 -D package/tarsnap/tarsnap.conf $(TARGET_DIR)/etc/tarsnap.conf
	$(INSTALL) -m 0755 -D package/tarsnap/tarsnap-backup.sh $(TARGET_DIR)/usr/bin/tarsnap-backup
endef

TARSNAP_POST_INSTALL_TARGET_HOOKS = TARSNAP_POST_INSTALL

define TARSNAP_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/etc/tarsnap.conf
	rm -f $(TARGET_DIR)/usr/bin/tarsnap*
endef

$(eval $(call AUTOTARGETS,package,tarsnap))
