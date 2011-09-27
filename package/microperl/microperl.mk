#############################################################
#
# microperl
#
#############################################################
MICROPERL_VER:=5.10.0
MICROPERL_SOURCE:=perl-$(MICROPERL_VER).tar.gz
MICROPERL_SITE:=ftp://ftp.cpan.org/pub/CPAN/src/5.0
MICROPERL_DIR:=$(BUILD_DIR)/perl-$(MICROPERL_VER)
MICROPERL_LIB_DIR:=$(TARGET_DIR)/usr/lib/perl5/5.10

$(DL_DIR)/$(MICROPERL_SOURCE):
	$(WGET) -P $(DL_DIR) $(MICROPERL_SITE)/$(MICROPERL_SOURCE)

$(MICROPERL_DIR)/.source: $(DL_DIR)/$(MICROPERL_SOURCE)
	zcat $(DL_DIR)/$(MICROPERL_SOURCE) | tar -C $(BUILD_DIR) $(TAR_OPTIONS) -
	touch $@

$(MICROPERL_DIR)/.patch: $(MICROPERL_DIR)/.source
	toolchain/patch-kernel.sh $(MICROPERL_DIR) package/microperl microperl-\*.patch
	$(SED) 's:/usr/local/lib/perl5:/usr/lib/perl5:' \
		$(MICROPERL_DIR)/uconfig.sh
	touch $@

$(MICROPERL_DIR)/microperl: $(MICROPERL_DIR)/.patch
	$(MAKE) -f Makefile.micro CC=$(TARGET_CC) HOSTCC=gcc \
		-C $(MICROPERL_DIR) \
		regen_uconfig
	$(MAKE) -f Makefile.micro CC=$(TARGET_CC) HOSTCC=gcc \
		-C $(MICROPERL_DIR)

$(TARGET_DIR)/usr/bin/microperl: $(MICROPERL_DIR)/microperl
	install -D -m 0755 $(MICROPERL_DIR)/microperl $(TARGET_DIR)/usr/bin/microperl
	ln -s /usr/bin/microperl $(TARGET_DIR)/usr/bin/perl
	# Install very basic modules
	for i in strict warnings Carp Exporter File/Basename Getopt/Std; do \
	  mkdir -p $$(dirname $(MICROPERL_LIB_DIR)/$$i) ; \
	  cp -pf $(MICROPERL_DIR)/lib/$$i.pm $(MICROPERL_LIB_DIR)/$$i.pm ; \
	done

microperl: $(TARGET_DIR)/usr/bin/microperl

microperl-source: $(MICROPERL_DIR)/.patch

microperl-clean:
	rm -f $(TARGET_DIR)/usr/bin/microperl
	rm -f $(TARGET_DIR)/usr/bin/perl
	rm -rf $(MICROPERL_LIB_DIR)
	-$(MAKE) -C $(MICROPERL_DIR) clean

microperl-dirclean:
	rm -rf $(MICROPERL_DIR)

#############################################################
#
# Toplevel Makefile options
#
#############################################################
ifeq ($(strip $(BR2_PACKAGE_MICROPERL)),y)
TARGETS+=microperl
endif
