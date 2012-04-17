#############################################################
#
# webinterface
#
#############################################################
WEBINTERFACE_TARGET_DIR=stat/var/www

ifeq ($(strip $(BR2_PACKAGE_WEBINTERFACE_v1)),y)
ifeq ($(strip $(BR2_PACKAGE_WEBINTERFACE_v2)),y)
$(error Web interface versions are mutually exlusive; pick only one)
endif
endif

ifeq ($(strip $(BR2_PACKAGE_ASTERISK)),y)
ifeq ($(strip $(BR2_PACKAGE_ASTERISK_GUI)),y)
webinterface-install: asterisk asterisk-gui
else
webinterface-install: asterisk
endif
else
webinterface-install: 
endif
	mkdir -p $(TARGET_DIR)/$(WEBINTERFACE_TARGET_DIR)

ifeq ($(strip $(BR2_PACKAGE_WEBINTERFACE_v1)),y)
	rsync -a --exclude=".svn" package/webinterface/www $(TARGET_DIR)/stat/var/www/
endif

ifeq ($(strip $(BR2_PACKAGE_WEBINTERFACE_v2)),y)
	rsync -a --exclude=".svn" package/webinterface/altweb/ $(TARGET_DIR)/stat/var/www/
	$(INSTALL) -D -m 644 package/webinterface/altweb/php.ini $(TARGET_DIR)/etc/php.ini
	$(INSTALL) -D -m 644 package/webinterface/www/admin/.htpasswd $(TARGET_DIR)/stat/var/www/admin/.htpasswd

ifeq ($(strip $(BR2_PACKAGE_ASTERISK)),y)
	$(INSTALL) -D -m 700 package/webinterface/ast-vmpass $(TARGET_DIR)/usr/sbin/ast-vmpass
	NL=$$'\\\n' ; \
	$(SED) '/^\[general\]$$/,/^$$/{s/^enabled = no$$/enabled = yes/}' \
	   -e  '/^\[webinterface\]$$/,/^$$/d' \
	   -e  "/^;\[mark\]$$/i $${NL}[webinterface]$${NL}secret = webinterface$${NL}deny = 0.0.0.0/0.0.0.0$${NL}permit = 127.0.0.1/255.255.255.255$${NL}read = command$${NL}write = command$${NL}" \
		$(TARGET_DIR)/stat/etc/asterisk/manager.conf
	$(SED) '/^\[general\]$$/,/^\[/{s:^;externpass=/usr/bin/myapp$$:externpass=/usr/sbin/ast-vmpass:}' \
		$(TARGET_DIR)/stat/etc/asterisk/voicemail.conf
endif
endif


#
# make x-clean should undo all changes done by 'x'.  No exceptions.
#

webinterface-clean:
	rm -rf $(TARGET_DIR)/stat/var/www
	rm -f $(TARGET_DIR)/usr/sbin/ast-vmpass
	rm -f $(TARGET_DIR)/etc/php.ini
ifeq ($(strip $(BR2_PACKAGE_ASTERISK)),y)
	$(SED) '/^\[general\]$$/,/^$$/{s/^enabled = yes$$/enabled = no/}' \
	   -e  '/^\[webinterface\]$$/,/^$$/d' \
		$(TARGET_DIR)/stat/etc/asterisk/manager.conf
	$(SED) '/^\[general\]$$/,/^\[/{s:^externpass=/usr/sbin/ast-vmpass$$:;externpass=/usr/bin/myapp:}' \
		$(TARGET_DIR)/stat/etc/asterisk/voicemail.conf
endif

webinterface-dirclean:

ifeq ($(strip $(BR2_PACKAGE_WEBINTERFACE_v1)),y)
	rm -rf $(TARGET_DIR)/stat/var/www
endif
ifeq ($(strip $(BR2_PACKAGE_WEBINTERFACE_v2)),y)
	rm -rf $(TARGET_DIR)/stat/var/www
endif

# We want to always be applied *after* asterisk and asterisk-gui
# (assuming the later is configured as well).

webinterface: webinterface-install
 
#############################################################
#
# Toplevel Makefile options
#
#############################################################
ifeq ($(strip $(BR2_PACKAGE_WEBINTERFACE)),y)
TARGETS+=webinterface
endif
