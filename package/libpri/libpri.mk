#############################################################
#
# libpri
#
############################################################

LIBPRI_VERSION = 1.4.13
LIBPRI_SOURCE = libpri-$(LIBPRI_VERSION).tar.gz
LIBPRI_SITE = http://downloads.asterisk.org/pub/telephony/libpri/releases
LIBPRI_INSTALL_STAGING = YES
LIBPRI_INSTALL_TARGET = YES

define LIBPRI_CONFIGURE_CMDS
        @echo "No configure"
endef

LIBPRI_MAKE_OPT = \
	OSARCH=Linux \
	INSTALL_BASE=/usr \
	$(TARGET_CONFIGURE_OPTS)

LIBPRI_INSTALL_STAGING_OPT = \
	OSARCH=Linux \
	INSTALL_BASE=/usr \
	INSTALL_PREFIX=$(STAGING_DIR) \
	$(TARGET_CONFIGURE_OPTS) \
	install

LIBPRI_INSTALL_TARGET_OPT = \
	OSARCH=Linux \
	INSTALL_BASE=/usr \
	INSTALL_PREFIX=$(TARGET_DIR) \
	$(TARGET_CONFIGURE_OPTS) \
	install

$(eval $(call AUTOTARGETS,package,libpri))

