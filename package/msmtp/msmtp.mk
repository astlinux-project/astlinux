#############################################################
#
# msmtp
#
#############################################################

MSMTP_VERSION = 1.8.20
MSMTP_SOURCE = msmtp-$(MSMTP_VERSION).tar.xz
MSMTP_SITE = https://marlam.de/msmtp/releases
MSMTP_DEPENDENCIES = libretls

MSMTP_CONF_OPT += \
	--with-tls=libtls \
	--without-libgsasl \
	--without-libidn \
	--without-libsecret \
	--disable-gai-idn \
	--sysconfdir=/etc

define MSMTP_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/src/msmtp $(TARGET_DIR)/usr/sbin/msmtp
	$(INSTALL) -m 0755 -D $(@D)/src/msmtpd $(TARGET_DIR)/usr/sbin/msmtpd
	$(INSTALL) -m 0755 -D package/msmtp/msmtp.init $(TARGET_DIR)/etc/init.d/msmtp
	$(INSTALL) -m 0755 -D package/msmtp/msmtpd.init $(TARGET_DIR)/etc/init.d/msmtpd
	$(INSTALL) -m 0755 -D package/msmtp/msmtpqueue.sh $(TARGET_DIR)/usr/sbin/msmtpqueue
	$(INSTALL) -m 0755 -D package/msmtp/sendmail.sh $(TARGET_DIR)/usr/sbin/sendmail
	$(INSTALL) -m 0755 -D package/msmtp/testmail.sh $(TARGET_DIR)/usr/sbin/testmail
	$(INSTALL) -m 0755 -D package/msmtp/mime-pack.sh $(TARGET_DIR)/usr/sbin/mime-pack
	$(INSTALL) -m 0755 -D package/msmtp/mail.sh $(TARGET_DIR)/bin/mail
	ln -sf ../../bin/mail $(TARGET_DIR)/usr/bin/mail
	ln -sf /tmp/etc/msmtprc $(TARGET_DIR)/etc/msmtprc
	ln -sf ../../init.d/msmtp $(TARGET_DIR)/etc/runlevels/default/S34msmtp
	ln -sf ../../init.d/msmtp $(TARGET_DIR)/etc/runlevels/default/K24msmtp
	ln -sf ../../init.d/msmtpd $(TARGET_DIR)/etc/runlevels/default/S08msmtpd
	ln -sf ../../init.d/msmtpd $(TARGET_DIR)/etc/runlevels/default/K30msmtpd
endef

define MSMTP_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/msmtp
	rm -f $(TARGET_DIR)/usr/sbin/msmtpd
	rm -f $(TARGET_DIR)/etc/init.d/msmtp
	rm -f $(TARGET_DIR)/etc/init.d/msmtpd
	rm -f $(TARGET_DIR)/usr/sbin/msmtpqueue
	rm -f $(TARGET_DIR)/usr/sbin/sendmail
	rm -f $(TARGET_DIR)/usr/sbin/testmail
	rm -f $(TARGET_DIR)/usr/sbin/mime-pack
	rm -f $(TARGET_DIR)/bin/mail
	rm -f $(TARGET_DIR)/usr/bin/mail
	rm -f $(TARGET_DIR)/etc/msmtprc
	rm -f $(TARGET_DIR)/etc/runlevels/default/S34msmtp
	rm -f $(TARGET_DIR)/etc/runlevels/default/K24msmtp
	rm -f $(TARGET_DIR)/etc/runlevels/default/S08msmtpd
	rm -f $(TARGET_DIR)/etc/runlevels/default/K30msmtpd
endef

$(eval $(call AUTOTARGETS,package,msmtp))
