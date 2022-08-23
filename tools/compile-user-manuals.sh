#!/bin/bash

#Make all paths relative to this script location, instead of the calling working directory
scriptdir="$(dirname "$0")"
cd "$scriptdir" || exit

# Compile the user documentation
echo "Compiling user manuals ..."
pushd ../doc/catechesis-manuals
make html
popd

# Copy the built documentation into src/help/user_manual
echo "Copying user manuals into src/help ..."
rm -rf ../src/help/user_manual
mkdir -p ../src/help/user_manual
cp -r ../doc/catechesis-manuals/build/html/. ../src/help/user_manual/

echo "User documentation build done!"