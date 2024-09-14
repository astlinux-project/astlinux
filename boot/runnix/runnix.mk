#############################################################
#
# runnix
#
#############################################################

RUNNIX_VERSION = 0.6.19
RUNNIX_SOURCE = runnix-$(RUNNIX_VERSION).tar.gz
RUNNIX_SITE = https://astlinux-project.org/mirror/runnix6
RUNNIX_DEPENDENCIES = host-fdisk

RUNFS_DIR = $(BUILD_DIR)/runfs

define RUNNIX_RUNFS_EXTRACT
	mkdir -p $(RUNFS_DIR)
	cp -af $(@D)/rootfs_vfat/* $(RUNFS_DIR)
	rm -f $(RUNFS_DIR)/*.sample
endef
RUNNIX_POST_EXTRACT_HOOKS += RUNNIX_RUNFS_EXTRACT

define RUNNIX_CLEAN_CMDS
	rm -rf $(RUNFS_DIR)
endef

$(eval $(call GENTARGETS,boot,runnix))

runfs: runnix
runfs-clean: runnix-clean
runfs-dirclean: runnix-dirclean
