################################################################################
#
# tarsnap
#
################################################################################

TARSNAP_VERSION = 1.0.39
TARSNAP_SOURCE = tarsnap-autoconf-$(TARSNAP_VERSION).tgz
TARSNAP_SITE = https://www.tarsnap.com/download

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
endef

TARSNAP_POST_INSTALL_TARGET_HOOKS = TARSNAP_POST_INSTALL

$(eval $(call AUTOTARGETS,package,tarsnap))
