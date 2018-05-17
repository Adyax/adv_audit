#!/bin/sh

## Set proper site UUID
#-drush --root=docroot/ -y cset system.site uuid a638feda-1543-47ed-8253-80fbd690e315

### Create Codesniffer symlink ###
# Target file.
TARGET=../../../../drupal/coder/coder_sniffer/Drupal
TARGET_DP=../../../../drupal/coder/coder_sniffer/DrupalPractice
# Link name.
LINK_NAME=vendor/squizlabs/php_codesniffer/CodeSniffer/Standards/Drupal
LINK_NAME_DP=vendor/squizlabs/php_codesniffer/CodeSniffer/Standards/DrupalPractice
# Link folder.
LINK_FOLDER=vendor/squizlabs/php_codesniffer
# Git hooks folder
GIT_HOOKS_FOLDER=.git/hooks

if [ -d "$LINK_FOLDER" ]; then
  # Create symlink Drupal standard.
  ln -sf ${TARGET} ${LINK_NAME}

  # Creates symlink to DrupalPractice.
  ln -sf ${TARGET_DP} ${LINK_NAME_DP}

  ### Git hooks ####
  if [ -d "$GIT_HOOKS_FOLDER" ]; then
    # Pre commit hook
    cp scripts/tools/pre-commit .git/hooks/pre-commit
    # Make files executable.
    chmod +x .git/hooks/pre-commit
  fi
  ### End Git hooks ####
fi
### End Create symlink ####

### Git hooks ####
if [ -d "$GIT_HOOKS_FOLDER" ]; then
  # Post merge hook.
  cp scripts/tools/post-merge .git/hooks/post-merge
  # Make files executable.
  # Used only for dev env.
  chmod +x .git/hooks/post-merge
fi
### End Git hooks ####
