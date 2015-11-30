#############################################################
#
# uw-imap
#
#############################################################
UW_IMAP_VERSION = 2007f
UW_IMAP_SITE = ftp://ftp.cac.washington.edu/imap
UW_IMAP_SOURCE = imap-$(UW_IMAP_VERSION).tar.gz
UW_IMAP_INSTALL_STAGING = NO
UW_IMAP_INSTALL_TARGET = YES

UW_IMAP_DEPENDENCIES = openssl

define UW_IMAP_CONFIGURE_CMDS
        @echo "No configure"
endef

UW_IMAP_MAKE_OPT = slx CC='$(TARGET_CC)' \
		   EXTRACFLAGS="-I$(STAGING_DIR)/usr/include/openssl -fPIC" \
		   SSLLIB=$(STAGING_DIR)/lib \
		   SSLTYPE=nopwd \
		   SHLIBBASE=c-client \
		   SHLIBNAME=libc-client.so.1 \
		   SSLDIR=$(TARGET_DIR)/usr/lib/ssl \
		   LD='$(TARGET_LD)' \
		   -C $(@D) c-client

define UW_IMAP_INSTALL_TARGET_CMDS
	@echo "Nothing to install"
endef

$(eval $(call AUTOTARGETS,package,uw-imap))
