#!/bin/bash

#Make all paths relative to this script location, instead of the calling working directory
scriptdir="$(dirname "$0")"
cd "$scriptdir" || exit

if [[ $# -eq 0 ]]; then
  echo 'Please run this script with an argument stating the git hash or tag of the previous version.'
  echo 'Ex: ./make-upgrade.sh v1.10.0'
  exit 0
fi


# ========= Configuration parameters =================================================
#currentHashOrTag=$(git describe --exact-match --tags "$(git log -n1 --pretty='%h')")
currentHashOrTag=$(git describe --tags "$(git log -n1 --pretty='%h')")
previousHash=$(git rev-list -n 1 $1)
srcDirectory="../src/"                            # The main sources directory where most of the CatecheSis code is stored in this repo
catechesisDataDirectory="../catechesis_data"      # Template directory which contains the skeleton for the catechesis data directory
databaseDirectory="../database"                   # Directory where database sources are stored in this repo
dstDirectory="../build/upgrade" #Destination directory to build artifacts
# ====================================================================================


# =================================== Auxiliary functions =============================================================

# Generates an SQL script with statements to upgrade the database, based on the differences between the old and
#  new versions of an SQL source file.
# Arguments:
#  - $1 : sql file to upgrade (located in database folder)
#  - $2 : name of the sql upgrade script to generate (located in the build folder)
function upgrade_sql_file()
{
  #Create temporary files
  git show "$previousHash":"$databaseDirectory/$1" > "$dstDirectory/previous_db.sql"
  cp "$databaseDirectory/$1" "$dstDirectory/new_db.sql"

  #Hack: Remove strings that are unsupported by schemalex
  sed -i 's/DELIMITER ;//' "$dstDirectory/previous_db.sql"
  sed -i 's/DELIMITER ;//' "$dstDirectory/new_db.sql"
  sed -i 's/data/batata/' "$dstDirectory/previous_db.sql"
  sed -i 's/data/batata/' "$dstDirectory/new_db.sql"

  #Run diff with schemalex
  #go run /mnt/linux-data/goncalo/Experiencias/Schemalex/schemalex/cmd/schemalex/schemalex.go -o $dstDirectory/db_upgrade_construtor.sql $dstDirectory/previous_db.sql "Base de dados/construtor.sql"
  ./schemalex -o "$dstDirectory/$2" "$dstDirectory/previous_db.sql" "$dstDirectory/new_db.sql"

  #Hack: Restore removed strings
  sed -i '1s/^/DELIMITER ;\n/' "$dstDirectory/$2"
  sed -i 's/batata/data/' "$dstDirectory/$2"

  #Cleanup temporary files
  rm "$dstDirectory/previous_db.sql"
  rm "$dstDirectory/new_db.sql"
}



# Generates an SQL script with the plain diff between the old and new versions of an SQL source file.
# Arguments:
#  - $1 : sql file to upgrade (located in database folder)
#  - $2 : name of the diff file to generate (located in the build folder)
function diff_sql_file()
{
  #Create temporary files
  git show "$previousHash":"$databaseDirectory/$1" > "$dstDirectory/previous_db.sql"
  cp "$databaseDirectory/$1" "$dstDirectory/new_db.sql"

  diff -rupP "$dstDirectory/previous_db.sql" "$dstDirectory/new_db.sql" > "$dstDirectory/$2"

  #Cleanup temporary files
  rm "$dstDirectory/previous_db.sql"
  rm "$dstDirectory/new_db.sql"
}


# Generates a file with the plain diff between the old and new versions of a source file.
# Arguments:
#  - $1 : file to upgrade
#  - $2 : name of the diff file to generate (located in the build folder)
function diff_file()
{
  #Create temporary files
  git show "$previousHash":"$1" > "$dstDirectory/file_previous_version"
  cp "$1" "$dstDirectory/file_new_version"

  mkdir -p "$dstDirectory/${2%/*}"
  diff -rupP "$dstDirectory/file_previous_version" "$dstDirectory/file_new_version" > "$dstDirectory/$2"

  #Cleanup temporary files
  rm "$dstDirectory/file_previous_version"
  rm "$dstDirectory/file_new_version"
}



# ====================================================== Script beginning =============================================


echo "Generating upgrade from version $1 to version $currentHashOrTag"

# Load list of careful update files
IFS=$'\n' read -d '' -r -a careful_files < careful_update_files.txt


mkdir -p $dstDirectory
cp ../AUTHORS $srcDirectory/licenses/CatecheSis/AUTHORS   # Update AUTHORS inside src with eventual changes on AUTHORS in the project root
./compile-user-manuals.sh                                 # Build user manuals

# Create null terminated file names for copies below (-z option)
git diff --name-only -z "$1" HEAD $srcDirectory >$dstDirectory/all_diff.txt
git diff --name-only -z "$1" HEAD $catechesisDataDirectory >> $dstDirectory/all_diff.txt
git diff --diff-filter=ACMRTUXB --name-only -z "$1" HEAD $srcDirectory >$dstDirectory/changed_files.txt
git diff --diff-filter=ACMRTUXB --name-only -z "$1" HEAD $catechesisDataDirectory >> $dstDirectory/changed_files.txt
git diff --diff-filter=D --name-only -z "$1" HEAD $srcDirectory >$dstDirectory/deleted_files.txt
git diff --diff-filter=D --name-only -z "$1" HEAD $catechesisDataDirectory >> $dstDirectory/deleted_files.txt

# Copy the changed files to folder build/changes
mkdir -p $dstDirectory/changes/dummy
mkdir -p $dstDirectory/careful_changes/dummy
while read -d $'\0' file; do
  if [[ " ${careful_files[@]} " =~ " ${file} " ]]; then
      echo "Be careful when upgrading file $file"
      diff_file "../$file" "careful_changes/$file.patch"
      cp --parents "../$file" $dstDirectory/careful_changes/dummy;
  else
      cp --parents "../$file" $dstDirectory/changes/dummy;
  fi
done < $dstDirectory/changed_files.txt #dummy directory needed because the file path contains ../
rm -r $dstDirectory/changes/dummy
rm -r $dstDirectory/careful_changes/dummy
rm -r $dstDirectory/changes/src/setup # We don't want to include the setup wizard on a system that is already installed in production

# Copy the documentation always (we cannot check for changes in the git submodule)
cp -r $srcDirectory/help/. $dstDirectory/changes/src/help/

# Write the same file names in a more user-readable format
git diff --name-only "$1" HEAD $srcDirectory >$dstDirectory/all_diff.txt
git diff --name-only "$1" HEAD $catechesisDataDirectory >> $dstDirectory/all_diff.txt
git diff --diff-filter=ACMRTUXB --name-only "$1" HEAD $srcDirectory >$dstDirectory/changed_files.txt
git diff --diff-filter=ACMRTUXB --name-only "$1" HEAD $catechesisDataDirectory >> $dstDirectory/changed_files.txt
git diff --diff-filter=D --name-only "$1" HEAD $srcDirectory >$dstDirectory/deleted_files.txt
git diff --diff-filter=D --name-only "$1" HEAD $catechesisDataDirectory >> $dstDirectory/deleted_files.txt

# Create script to delete old files
echo "#!/bin/bash" >$dstDirectory/delete_old_files.sh
echo "#Put this file in the CatecheSis root folder of your server!" >>$dstDirectory/delete_old_files.sh
echo "" >>$dstDirectory/delete_old_files.sh
while read -r filename; do
  echo "rm ${filename#$srcDirectory}" >> $dstDirectory/delete_old_files.sh #Write file names except the leading src directory
done < $dstDirectory/deleted_files.txt
echo "echo \"Done! Some empty folders may have been left...\"" >>$dstDirectory/delete_old_files.sh

# Create SQL upgrade scripts
upgrade_sql_file "catechesis_database.sql" "db_upgrade_catechesis_database.sql"
diff_sql_file "users.sql" "db_upgrade_users.patch"

# Copy upgrade recipe
cp ./update_recipe.php $dstDirectory/update_recipe.php

#Create a tar.gz package
(
  cd $dstDirectory || exit
  tar -czf upgrade-$1-to-$currentHashOrTag.tar.gz *
)
