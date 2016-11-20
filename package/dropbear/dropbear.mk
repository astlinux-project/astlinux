#############################################################
#
# dropbear
#
#############################################################

DROPBEAR_VERSION = 2016.74
DROPBEAR_SITE = http://matt.ucc.asn.au/dropbear/releases
DROPBEAR_SOURCE = dropbear-$(DROPBEAR_VERSION).tar.bz2
DROPBEAR_TARGET_BINS = dbclient dropbearkey dropbearconvert scp ssh
DROPBEAR_MAKE = \
	$(MAKE) MULTI=1 SCPPROGRESS=1 \
	PROGRAMS="dropbear dbclient dropbearkey dropbearconvert scp"

ifeq ($(BR2_PREFER_STATIC_LIB),y)
DROPBEAR_MAKE += STATIC=1
endif

define DROPBEAR_FIX_XAUTH
	$(SED) 's,^#define XAUTH_COMMAND.*/xauth,#define XAUTH_COMMAND "/usr/bin/xauth,g' $(@D)/options.h
endef

DROPBEAR_POST_EXTRACT_HOOKS += DROPBEAR_FIX_XAUTH

define DROPBEAR_ENABLE_REVERSE_DNS
	$(SED) 's:.*\(#define DO_HOST_LOOKUP\).*:\1:' $(@D)/options.h
endef

define DROPBEAR_BUILD_SMALL
	$(SED) 's:.*\(#define NO_FAST_EXPTMOD\).*:\1:' $(@D)/options.h
endef

define DROPBEAR_BUILD_FEATURED
	$(SED) 's:^#define DROPBEAR_SMALL_CODE::' $(@D)/options.h
	$(SED) 's:.*\(#define DROPBEAR_BLOWFISH\).*:\1:' $(@D)/options.h
	$(SED) 's:.*\(#define DROPBEAR_TWOFISH128\).*:\1:' $(@D)/options.h
	$(SED) 's:.*\(#define DROPBEAR_TWOFISH256\).*:\1:' $(@D)/options.h
endef

ifeq ($(BR2_PACKAGE_DROPBEAR_DISABLE_REVERSEDNS),)
DROPBEAR_POST_EXTRACT_HOOKS += DROPBEAR_ENABLE_REVERSE_DNS
endif

ifeq ($(BR2_PACKAGE_DROPBEAR_SMALL),y)
DROPBEAR_POST_EXTRACT_HOOKS += DROPBEAR_BUILD_SMALL
DROPBEAR_CONF_OPT += --disable-zlib
else
DROPBEAR_POST_EXTRACT_HOOKS += DROPBEAR_BUILD_FEATURED
DROPBEAR_DEPENDENCIES += zlib
endif

DROPBEAR_CONF_OPT += --disable-wtmp

DROPBEAR_CONF_OPT += --disable-lastlog

define DROPBEAR_INSTALL_TARGET_CMDS
	$(INSTALL) -m 755 $(@D)/dropbearmulti $(TARGET_DIR)/usr/sbin/dropbear
	for f in $(DROPBEAR_TARGET_BINS); do \
		ln -snf ../sbin/dropbear $(TARGET_DIR)/usr/bin/$$f ; \
	done
	ln -snf /tmp/etc/dropbear $(TARGET_DIR)/etc/dropbear
endef

define DROPBEAR_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/dropbear
	rm -f $(TARGET_DIR)/etc/dropbear
	rm -f $(addprefix $(TARGET_DIR)/usr/bin/, $(DROPBEAR_TARGET_BINS))
endef

$(eval $(call AUTOTARGETS,package,dropbear))
