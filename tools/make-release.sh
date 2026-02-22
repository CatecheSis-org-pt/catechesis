#!/bin/bash

#Make all paths relative to this script location, instead of the calling working directory
scriptdir="$(dirname "$0")"
cd "$scriptdir" || exit


# ========= Configuration parameters =================================================
#currentHashOrTag=$(git describe --exact-match --tags "$(git log -n1 --pretty='%h')")
currentHashOrTag=$(git describe --tags "$(git log -n1 --pretty='%h')")
srcDirectory="../src/"                            # The main sources directory where most of the CatecheSis code is stored in this repo
catechesisDataDirectory="../catechesis_data"      # Template directory which contains the skeleton for the catechesis data directory
databaseDirectory="../database"                   # Directory where database sources are stored in this repo
dstDirectory="../build/release"                   #Destination directory to build artifacts
# ====================================================================================


# Build user manuals
./compile-user-manuals.sh

echo "Building CatecheSis release package for version $currentHashOrTag"

cp ../AUTHORS $srcDirectory/licenses/CatecheSis/AUTHORS   # Update AUTHORS inside src with eventual changes on AUTHORS in the project root

# Create destination directories
packageDirectory=$dstDirectory/CatecheSis-$currentHashOrTag
mkdir -p $dstDirectory
mkdir -p $packageDirectory

# Copy source files
cp -r $srcDirectory/. $packageDirectory/
cp ../AUTHORS $packageDirectory/licenses/CatecheSis/AUTHORS # Update AUTHORS inside src with eventual changes on AUTHORS in the project root

# Copy database sources the setup folder
rm -rf $packageDirectory/setup/db                         # Delete any development leftovers...
mkdir -p $packageDirectory/setup/db
cp -r $databaseDirectory/. $packageDirectory/setup/db/

# Copy the catechesis_data template into the setup folder
rm -rf $packageDirectory/setup/catechesis_data            # Delete any development leftovers...
mkdir -p $packageDirectory/setup/catechesis_data
cp -r $catechesisDataDirectory/. $packageDirectory/setup/catechesis_data/

# Cleanup any development files
rm $packageDirectory/core/config/catechesis_config.inc.php
rm $packageDirectory/setup/catechesis_data/config/catechesis_config.shadow.php
rm $packageDirectory/setup/catechesis_data/photos/catechumens/*.jpg
rm $packageDirectory/setup/catechesis_data/documents/*.pdf
rm $packageDirectory/setup/catechesis_data/documents/*.jpg
rm $packageDirectory/setup/catechesis_data/documents/*.png
rm $packageDirectory/setup/catechesis_data/tmp/*.png
rm $packageDirectory/setup/catechesis_data/tmp/*.jpg
rm $packageDirectory/setup/catechesis_data/tmp/*.pdf
rm $packageDirectory/setup/catechesis_data/tmp/*.docx
rm $packageDirectory/setup/catechesis_data/tmp/*.xlsx



#Create a tar.gz package
(
  cd $dstDirectory || exit
  tar -czf CatecheSis-$currentHashOrTag.tar.gz *
)