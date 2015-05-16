#############################################################
#
# asterisk-gui
#
##############################################################
ASTERISK_GUI_SVN_VER := 5217
ASTERISK_GUI_VERSION := $(ASTERISK_GUI_SVN_VER)
ASTERISK_GUI_SOURCE := asterisk-gui-$(ASTERISK_GUI_VERSION).tar.gz
ASTERISK_GUI_SITE := http://svn.digium.com/svn/asterisk-gui/branches/2.0
ASTERISK_GUI_DIR := $(BUILD_DIR)/asterisk-gui-$(ASTERISK_GUI_VERSION)
ASTERISK_GUI_BINARY := tools/ztscan
ASTERISK_GUI_TARGET_BINARY := stat/var/lib/asterisk/static-http/config/index.html
ASTERISK_GUI_COMPILE := makeopts
ASTERISK_GUI_PREREQS :=
ASTERISK_GUI_CONFIG :=

ifeq ($(strip $(BR2_PACKAGE_DAHDI_LINUX)),y)
ASTERISK_GUI_PREREQS += dahdi-tools
ASTERISK_GUI_CONFIG += \
		--with-dahdi=$(STAGING_DIR)/usr
endif

$(DL_DIR)/$(ASTERISK_GUI_SOURCE):
	svn co -r $(ASTERISK_GUI_SVN_VER) $(ASTERISK_GUI_SITE) $(DL_DIR)/asterisk-gui-$(ASTERISK_GUI_VERSION)
	(cd $(DL_DIR); tar czf $(ASTERISK_GUI_SOURCE) asterisk-gui-$(ASTERISK_GUI_VERSION))

$(ASTERISK_GUI_DIR)/.source: $(DL_DIR)/$(ASTERISK_GUI_SOURCE)
	zcat $(DL_DIR)/$(ASTERISK_GUI_SOURCE) | tar -C $(BUILD_DIR) $(TAR_OPTIONS) -
	toolchain/patch-kernel.sh $(ASTERISK_GUI_DIR) package/asterisk-gui/ asterisk-gui\*.patch
	touch $@

$(ASTERISK_GUI_DIR)/.configured: $(ASTERISK_GUI_DIR)/.source | asterisk \
					$(ASTERISK_GUI_PREREQS)
	(cd $(ASTERISK_GUI_DIR); rm -rf config.cache; \
		$(TARGET_CONFIGURE_OPTS) CC_FOR_BUILD=$(HOSTCC) \
		CFLAGS='$(TARGET_CFLAGS)' \
		./configure \
		--target=$(GNU_TARGET_NAME) \
		--host=$(GNU_TARGET_NAME) \
		--build=$(GNU_HOST_NAME) \
		--prefix=/ \
		--exec-prefix=/usr \
		--libdir=/usr/lib \
		--includedir=/usr/include \
		--datadir=/usr/share \
		--sysconfdir=/etc \
		$(ASTERISK_GUI_CONFIG) \
        )
	touch $@

$(ASTERISK_GUI_DIR)/$(ASTERISK_GUI_COMPILE): $(ASTERISK_GUI_DIR)/.configured
	$(MAKE) -C $(ASTERISK_GUI_DIR) \
		HOSTCC=gcc $(TARGET_CONFIGURE_OPTS) \
		CFLAGS="$(TARGET_CFLAGS)" \
		ASTETCDIR=$(TARGET_DIR)/stat/etc/asterisk \
		ASTVARLIBDIR=$(TARGET_DIR)/stat/var/lib/asterisk

$(TARGET_DIR)/$(ASTERISK_GUI_TARGET_BINARY): $(ASTERISK_GUI_DIR)/$(ASTERISK_GUI_COMPILE)
	$(MAKE1) -C $(ASTERISK_GUI_DIR) \
		HOSTCC=gcc $(TARGET_CONFIGURE_OPTS) \
		CFLAGS="$(TARGET_CFLAGS)" \
		ASTETCDIR=$(TARGET_DIR)/stat/etc/asterisk \
		ASTVARLIBDIR=$(TARGET_DIR)/stat/var/lib/asterisk install
	NL=$$'\\\n'; \
	$(SED) "/^; Third party application call management/i $${NL}; Modified for use with asterisk-gui on AstLinux$${NL};$${NL}; THIS IS INSECURE! CHANGE THE PASSWORD!!!$${NL};" \
	    -e 's/^enabled = no$$/enabled = yes/' \
	    -e 's/^;webenabled = yes$$/webenabled = yes/' \
		$(TARGET_DIR)/stat/etc/asterisk/manager.conf
	$(SED) 's/^;enabled=yes$$/enabled=yes/' \
	    -e 's/^;enablestatic=yes$$/enablestatic=yes/' \
	    -e 's/^bindaddr=127.0.0.1$$/bindaddr=0.0.0.0/' \
		$(TARGET_DIR)/stat/etc/asterisk/http.conf
		ln -snf /var/tmp/asterisk-gui \
		$(TARGET_DIR)/stat/var/lib/asterisk/static-http/config/tmp

asterisk-gui: $(TARGET_DIR)/$(ASTERISK_GUI_TARGET_BINARY)

asterisk-gui-source: $(ASTERISK_GUI_DIR)/.source

asterisk-gui-unpack: $(ASTERISK_GUI_DIR)/.configured

asterisk-gui-clean:
	rm -rf $(TARGET_DIR)/stat/var/lib/asterisk/static-http/config
	$(SED) "/^; Modified for use with asterisk-gui on AstLinux$$/,+3d" \
	    -e 's/^enabled = yes$$/enabled = no/' \
	    -e 's/^webenabled = yes$$/;webenabled = yes/' \
		$(TARGET_DIR)/stat/etc/asterisk/manager.conf
	$(SED) 's/^enabled=yes$$/;enabled=yes/' \
	    -e 's/^enablestatic=yes$$/;enablestatic=yes/' \
	    -e 's/^bindaddr=0.0.0.0$$/bindaddr=127.0.0.1/' \
		$(TARGET_DIR)/stat/etc/asterisk/http.conf

asterisk-gui-dirclean:
	rm -rf $(ASTERISK_GUI_DIR)

#############################################################
#
# Toplevel Makefile options
#
#############################################################
ifeq ($(strip $(BR2_PACKAGE_ASTERISK_GUI)),y)
TARGETS+=asterisk-gui
endif
