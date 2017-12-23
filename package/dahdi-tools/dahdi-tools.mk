#############################################################
#
# dahdi-tools
#
##############################################################
DAHDI_TOOLS_VERSION := 2.10.2
DAHDI_TOOLS_SOURCE := dahdi-tools-$(DAHDI_TOOLS_VERSION).tar.gz
DAHDI_TOOLS_SITE := https://downloads.asterisk.org/pub/telephony/dahdi-tools/releases
DAHDI_TOOLS_DIR := $(BUILD_DIR)/dahdi-tools-$(DAHDI_TOOLS_VERSION)
DAHDI_TOOLS_BINARY := dahdi_cfg
DAHDI_TOOLS_TARGET_BINARY := usr/sbin/dahdi_cfg
PERLLIBDIR := /usr/local/share/perl
DAHDI_TOOLS_PREREQS := libusb newt dahdi-linux
DAHDI_TOOLS_CONFIGURE_ARGS :=

# $(call ndots start,end,dotted-string)
dot:=.
empty:=
space:=$(empty) $(empty)
ndots = $(subst $(space),$(dot),$(wordlist $(1),$(2),$(subst $(dot),$(space),$3)))
##
DAHDI_TOOLS_VERSION_SINGLE := $(call ndots,1,1,$(DAHDI_TOOLS_VERSION))
DAHDI_TOOLS_VERSION_TUPLE := $(call ndots,1,2,$(DAHDI_TOOLS_VERSION))

$(DL_DIR)/$(DAHDI_TOOLS_SOURCE):
	$(WGET) -P $(DL_DIR) $(DAHDI_TOOLS_SITE)/$(DAHDI_TOOLS_SOURCE)

$(DAHDI_TOOLS_DIR)/.source: $(DL_DIR)/$(DAHDI_TOOLS_SOURCE)
	zcat $(DL_DIR)/$(DAHDI_TOOLS_SOURCE) | tar -C $(BUILD_DIR) $(TAR_OPTIONS) -
	toolchain/patch-kernel.sh $(DAHDI_TOOLS_DIR) package/dahdi-tools/ dahdi-tools-$(DAHDI_TOOLS_VERSION_SINGLE)-\*.patch
	toolchain/patch-kernel.sh $(DAHDI_TOOLS_DIR) package/dahdi-tools/ dahdi-tools-$(DAHDI_TOOLS_VERSION_TUPLE)-\*.patch
	touch $@

$(DAHDI_TOOLS_DIR)/.configured: $(DAHDI_TOOLS_DIR)/.source | $(DAHDI_TOOLS_PREREQS)
	(cd $(DAHDI_TOOLS_DIR); rm -rf config.cache; \
		$(TARGET_CONFIGURE_OPTS) \
		CC_FOR_BUILD=$(HOSTCC) \
		CFLAGS='$(TARGET_CFLAGS)' \
		LDFLAGS='$(TARGET_LDFLAGS)' \
		./configure \
		--target=$(GNU_TARGET_NAME) \
		--host=$(GNU_TARGET_NAME) \
		--build=$(GNU_HOST_NAME) \
		--prefix=/usr \
		--exec-prefix=/usr \
		--libdir=/usr/lib \
		--includedir=/usr/include \
		--datadir=/usr/share \
		--sysconfdir=/etc \
		--with-dahdi=$(STAGING_DIR)/usr \
		--with-usb=$(STAGING_DIR)/usr \
		--with-newt=$(STAGING_DIR)/usr \
		--without-selinux \
		--without-ppp \
		$(DAHDI_TOOLS_CONFIGURE_ARGS) \
	)
	touch $@

$(DAHDI_TOOLS_DIR)/menuselect.makeopts: $(DAHDI_TOOLS_DIR)/.configured
ifeq ($(DAHDI_TOOLS_VERSION_TUPLE),2.6)
	$(MAKE) -C $(DAHDI_TOOLS_DIR) CC=gcc menuselect.makeopts
else
	touch $@
endif

$(DAHDI_TOOLS_DIR)/$(DAHDI_TOOLS_BINARY): $(DAHDI_TOOLS_DIR)/menuselect.makeopts
	$(MAKE) -C $(DAHDI_TOOLS_DIR) HOSTCC=gcc CC=$(TARGET_CC) LD=$(TARGET_LD)

$(TARGET_DIR)/$(DAHDI_TOOLS_TARGET_BINARY): $(DAHDI_TOOLS_DIR)/$(DAHDI_TOOLS_BINARY)
	mkdir -p $(TARGET_DIR)$(PERLLIBDIR)
	rm -rf $(TARGET_DIR)/etc/dahdi
	$(MAKE1) -C $(DAHDI_TOOLS_DIR) HOSTCC=gcc CC=$(TARGET_CC) LD=$(TARGET_LD) \
		PERLLIBDIR=$(PERLLIBDIR) \
		install DESTDIR=$(TARGET_DIR)
	@rm -rf $(TARGET_DIR)/stat/etc/dahdi
	mv $(TARGET_DIR)/etc/dahdi $(TARGET_DIR)/stat/etc/dahdi
	ln -snf /tmp/etc/dahdi $(TARGET_DIR)/etc/dahdi
	$(INSTALL) -D -m 755 package/dahdi-tools/dahdi.init $(TARGET_DIR)/etc/init.d/dahdi
	if [ -f $(DAHDI_TOOLS_DIR)/dahdi.rules ]; then \
		$(INSTALL) -D -m 644 $(DAHDI_TOOLS_DIR)/dahdi.rules $(TARGET_DIR)/etc/udev/rules.d/ ; \
	fi

$(STAGING_DIR)/usr/lib/libtonezone.a: $(TARGET_DIR)/$(DAHDI_TOOLS_TARGET_BINARY)
	$(MAKE) -C $(DAHDI_TOOLS_DIR) HOSTCC=gcc CC=$(TARGET_CC) LD=$(TARGET_LD) \
		install-libs DESTDIR=$(STAGING_DIR)

dahdi-tools: $(TARGET_DIR)/$(DAHDI_TOOLS_TARGET_BINARY) \
	      $(STAGING_DIR)/usr/lib/libtonezone.a

dahdi-tools-source: $(DAHDI_TOOLS_DIR)/.source

dahdi-tools-clean:
	rm -rf $(TARGET_DIR)/etc/dahdi

dahdi-tools-dirclean:
	rm -rf $(DAHDI_TOOLS_DIR)

#############################################################
#
# Toplevel Makefile options
#
#############################################################
ifeq ($(strip $(BR2_PACKAGE_DAHDI_TOOLS)),y)
TARGETS+=dahdi-tools
endif
