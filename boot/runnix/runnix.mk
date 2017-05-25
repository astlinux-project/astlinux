#############################################################
#
# runnix
#
#############################################################

RUNNIX_VERSION = 0.5-8335
RUNNIX_SOURCE = runnix-$(RUNNIX_VERSION).tar.gz
RUNNIX_SITE = http://mirror.astlinux-project.org/runnix5

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
