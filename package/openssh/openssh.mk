#############################################################
#
# openssh
#
#############################################################

OPENSSH_VERSION = 7.5p1
OPENSSH_SITE = http://ftp.openbsd.org/pub/OpenBSD/OpenSSH/portable
OPENSSH_CONF_ENV = LD="$(TARGET_CC)" LDFLAGS="$(TARGET_CFLAGS)"

OPENSSH_DEPENDENCIES = zlib openssl

OPENSSH_CONF_OPT = \
	--libexecdir=/usr/libexec \
	--sysconfdir=/etc/ssh \
	--disable-lastlog \
	--disable-utmp \
	--disable-utmpx \
	--disable-wtmp \
	--disable-wtmpx \
	--disable-strip

ifeq ($(BR2_PACKAGE_LIBEDIT),y)
OPENSSH_DEPENDENCIES += libedit
OPENSSH_CONF_OPT += --with-libedit="$(STAGING_DIR)/usr"
endif

OPENSSH_CONF_OPT += --without-pam

OPENSSH_CONF_OPT += --without-selinux

OPENSSH_INSTALL_TARGET_OPT = DESTDIR=$(TARGET_DIR) -C $(@D) install-nosysconf

define OPENSSH_INSTALL_INITSCRIPT
	$(INSTALL) -D -m 755 package/openssh/sshd.init $(TARGET_DIR)/etc/init.d/sshd
	ln -snf /tmp/etc/ssh $(TARGET_DIR)/etc/ssh
endef

OPENSSH_POST_INSTALL_TARGET_HOOKS += OPENSSH_INSTALL_INITSCRIPT

$(eval $(call AUTOTARGETS,package,openssh))
