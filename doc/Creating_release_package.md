# Creating CatecheSis release packages

## Clean the ./build directory

On the CatecheSis project root folder, run

```bash
make clean
```

## Compiling a CatecheSis release package

Assuming that the current repository status reflects the version that you want to build
(otherwise ```checkout``` the intended version first), run the following command in the project root to compile a package:

```bash
make release
```

The build artifacts are stored in folder ```./build/release/``` under the project root.
A ```tar.gz``` file is automatically generated there with the contents of the ```release``` directory.

To deploy on a production system, uncompress the ```.tar.gz``` file in the server folder where you want to
install, then access the script ```<catechesis_root>/setup/index.php``` in a browser and follow the installation instructions.


### A note on user manuals

When you launch `make release`, the script will first compile the user manuals and copy the built artifacts into `src/help`.

In order for this to work, you need to ensure that:
- the git submodule that points to the user manuals is checked-out;
- Python is installed;
- the packages listed in `doc/catechesis-manuals/requirements.txt` are available in the environment where you run `make release`;

To ensure these pre-conditions, follow the steps in [Compiling_user_manuals.md](Compiling_user_manuals.md).