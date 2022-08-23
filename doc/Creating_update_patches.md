# Creating CatecheSis update patches

## Install Go language and compile schemalex

1. Follow the instructions on [https://golang.org/doc/install](https://golang.org/doc/install) to install the Go language.
This is a dependency to produce SQL diff patches.

2. Clone the repository [https://github.com/schemalex/schemalex](https://github.com/schemalex/schemalex)

3. `cd` to the schemalex repository root and compile it with:
```go build cmd/schemalex/schemalex.go```

4. Copy the produced `schemalex` binary, which is at the schemalex repository root, to the CatecheSis repository folder `./tools`.

## Clean the ./build directory

On the CatecheSis project root folder, run 

```
make clean
```

## Create an update patch from version X

Assuming that the current repository status reflects the version that you want to upgrade to
(otherwise ```checkout``` the intended version first), run the following command in the project root to produce a patch:

```
make upgrade X
```

where X is the git tag or git hash corresponding to the version currently installed in the production system.

For example, if the production system has v1.10.0 installed, and the current version on the repository is v1.11.0,
then running

```
make upgrade v1.10.0
```

will produce an upgrade patch from version v1.10.0 to version v1.11.0.

The build artifacts are stored in folder ```./build/upgrade/``` under the project root.
A ```tar.gz``` file is automatically generated there with the contents of the ```upgrade``` directory.


## Applying the patch on a production system

Carry the ```tar.gz``` file to the production system and uncompress it.

Then, on the production system:

1. Run the MySQL script `db_upgrade_catechesis_database.sql` to upgrade the database schema.

2. Manually add the necessary users and permissions to the database,
 by consulting the file `db_upgrade_users.patch` which shows the differences.
 
3. Run the script ```delete_old_files.sh``` included in the patch, to remove obsolete files.

    **Note:** Some folders may become empty, and you may want to manually delete them since the script does not delete
     any folders automatically.

4. Replace the files in the production system by the newer ones under the patch folder ```./builder/upgrade/changes```.

5. Carefully apply the changes to the files under `./build/upgrade/careful_changes` since they may contain configuration
values that may differ in the produciton system (e.g. URL, domain names, databases, passwords).
In this directory, for each file, there is a corresponding `.patch` file showing the changes.
