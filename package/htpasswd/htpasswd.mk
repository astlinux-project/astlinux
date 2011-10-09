#############################################################
#
# htpasswd
#
#############################################################
HTPASSWD_SOURCE:=package/htpasswd/src
HTPASSWD_DIR:=$(BUILD_DIR)/htpasswd
HTPASSWD_BINARY:=htpasswd
HTPASSWD_TARGET_BINARY:=usr/bin/htpasswd

$(HTPASSWD_DIR)/.unpacked:
	mkdir -p $(HTPASSWD_DIR)/
	cp -a $(HTPASSWD_SOURCE)/* $(HTPASSWD_DIR)/
	touch $@

$(HTPASSWD_DIR)/.configured: $(HTPASSWD_DIR)/.unpacked
	touch $@

$(HTPASSWD_DIR)/$(HTPASSWD_BINARY): $(HTPASSWD_DIR)/.configured
	$(MAKE) $(TARGET_CONFIGURE_OPTS) \
	CFLAGS='$(TARGET_CFLAGS)' \
	LDFLAGS='$(TARGET_LDFLAGS)' \
	-C $(HTPASSWD_DIR)

$(TARGET_DIR)/$(HTPASSWD_TARGET_BINARY): $(HTPASSWD_DIR)/$(HTPASSWD_BINARY)
	$(INSTALL) -D -m 0755 -s $(HTPASSWD_DIR)/$(HTPASSWD_BINARY) $(TARGET_DIR)/$(HTPASSWD_TARGET_BINARY)

htpasswd: $(TARGET_DIR)/$(HTPASSWD_TARGET_BINARY)

htpasswd-clean:
	rm -f $(TARGET_DIR)/$(HTPASSWD_TARGET_BINARY)
	-$(MAKE) -C $(HTPASSWD_DIR) clean

htpasswd-dirclean:
	rm -rf $(HTPASSWD_DIR)

#############################################################
#
# Toplevel Makefile options
#
#############################################################
ifeq ($(strip $(BR2_PACKAGE_HTPASSWD)),y)
TARGETS+=htpasswd
endif
