#############################################################
#
# libelf
#
#############################################################
LIBELF_VERSION=0.8.12
LIBELF_SOURCE=libelf-$(LIBELF_VERSION).tar.gz
#LIBELF_SITE=http://www.mr511.de/software/
LIBELF_SITE = https://astlinux-project.org/files
LIBELF_INSTALL_STAGING = YES
LIBELF_INSTALL_STAGING_OPT = instroot=$(STAGING_DIR) install
LIBELF_INSTALL_TARGET_OPT = instroot=$(TARGET_DIR) install

LIBELF_CONF_ENV = libelf_cv_working_memmove=yes \
		mr_cv_target_elf=yes \
		libelf_64bit=yes

LIBELF_CONF_OPT = --disable-sanity-checks \
		$(if $(BR2_ENABLE_DEBUG),--enable-debug,--disable-debug) \
		$(if $(BR2_LARGEFILE),--enable-elf64) \
		--enable-shared

$(eval $(call AUTOTARGETS,package,libelf))
