#############################################################
#
# vsftpd
#
#############################################################

VSFTPD_VERSION = 3.0.4
VSFTPD_SOURCE = vsftpd-$(VSFTPD_VERSION).tar.gz
VSFTPD_SITE = https://security.appspot.com/downloads

VSFTPD_LIBS = -lcrypt -lcrypto

define VSFTPD_ENABLE_SSL
	$(SED) 's/.*VSF_BUILD_SSL/#define VSF_BUILD_SSL/' $(@D)/builddefs.h
endef

ifeq ($(BR2_PACKAGE_OPENSSL),y)
VSFTPD_DEPENDENCIES += openssl
VSFTPD_LIBS += -lssl
VSFTPD_POST_CONFIGURE_HOOKS += VSFTPD_ENABLE_SSL
endif

ifeq ($(BR2_PACKAGE_LIBCAP),y)
VSFTPD_DEPENDENCIES += libcap
VSFTPD_LIBS += -lcap
endif

define VSFTPD_BUILD_CMDS
	$(TARGET_MAKE_ENV) $(MAKE) CC="$(TARGET_CC)" CFLAGS="$(TARGET_CFLAGS)" \
		LDFLAGS="$(TARGET_LDFLAGS)" LIBS="$(VSFTPD_LIBS)" -C $(@D)
endef

define VSFTPD_INSTALL_TARGET_CMDS
	install -D -m 755 $(@D)/vsftpd $(TARGET_DIR)/usr/sbin/vsftpd
	install -D -m 755 package/vsftpd/vsftpd.init $(TARGET_DIR)/etc/init.d/vsftpd
	ln -sf /tmp/etc/vsftpd.conf $(TARGET_DIR)/etc/vsftpd.conf
	install -d -m 700 $(TARGET_DIR)/usr/share/empty
endef

define VSFTPD_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/vsftpd
	rm -f $(TARGET_DIR)/etc/init.d/vsftpd
endef

define VSFTPD_CLEAN_CMDS
	-$(MAKE) -C $(@D) clean
endef

$(eval $(call GENTARGETS,package,vsftpd))
