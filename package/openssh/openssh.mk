#############################################################
#
# openssh
#
#############################################################

OPENSSH_VERSION = 6.1p1
OPENSSH_SITE = http://ftp.openbsd.org/pub/OpenBSD/OpenSSH/portable
OPENSSH_CONF_ENV = LD="$(TARGET_CC)" LDFLAGS="$(TARGET_CFLAGS)"
OPENSSH_CONF_OPT = --libexecdir=/usr/libexec --disable-lastlog --disable-utmp \
		--disable-utmpx --disable-wtmp --disable-wtmpx --disable-strip \
		--sysconfdir=/etc/ssh

OPENSSH_DEPENDENCIES = zlib openssl

OPENSSH_INSTALL_TARGET_OPT = DESTDIR=$(TARGET_DIR) -C $(@D) install-nosysconf

define OPENSSH_INSTALL_INITSCRIPT
	$(INSTALL) -D -m 755 package/openssh/sshd.init $(TARGET_DIR)/etc/init.d/sshd
	ln -snf /tmp/etc/ssh $(TARGET_DIR)/etc/ssh
endef

OPENSSH_POST_INSTALL_TARGET_HOOKS += OPENSSH_INSTALL_INITSCRIPT

$(eval $(call AUTOTARGETS,package,openssh))
