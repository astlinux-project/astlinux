#############################################################
#
# msmtp
#
#############################################################

MSMTP_VERSION = 1.4.30
MSMTP_SOURCE = msmtp-$(MSMTP_VERSION).tar.bz2
MSMTP_SITE = http://$(BR2_SOURCEFORGE_MIRROR).dl.sourceforge.net/sourceforge/msmtp
MSMTP_DEPENDENCIES = openssl

MSMTP_CONF_OPT += \
	--with-ssl=openssl \
	--without-libgsasl \
	--without-libidn \
	--without-gnome-keyring \
	--sysconfdir=/etc

define MSMTP_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/src/msmtp $(TARGET_DIR)/usr/sbin/msmtp
	$(INSTALL) -m 0755 -D package/msmtp/msmtp.init $(TARGET_DIR)/etc/init.d/msmtp
	$(INSTALL) -m 0755 -D package/msmtp/msmtpqueue.sh $(TARGET_DIR)/usr/sbin/msmtpqueue
	$(INSTALL) -m 0755 -D package/msmtp/sendmail.sh $(TARGET_DIR)/usr/sbin/sendmail
	$(INSTALL) -m 0755 -D package/msmtp/testmail.sh $(TARGET_DIR)/usr/sbin/testmail
	$(INSTALL) -m 0755 -D package/msmtp/mime-pack.sh $(TARGET_DIR)/usr/sbin/mime-pack
	ln -sf /tmp/etc/msmtprc $(TARGET_DIR)/etc/msmtprc
endef

define MSMTP_UNINSTALL_TARGET_CMDS
        rm $(TARGET_DIR)/usr/sbin/msmtp
	rm $(TARGET_DIR)/etc/init.d/msmtp
	rm $(TARGET_DIR)/usr/sbin/msmtpqueue
	rm $(TARGET_DIR)/usr/sbin/sendmail
	rm $(TARGET_DIR)/usr/sbin/testmail
	rm $(TARGET_DIR)/usr/sbin/mime-pack
	rm $(TARGET_DIR)/etc/msmtprc
endef

$(eval $(call AUTOTARGETS,package,msmtp))

