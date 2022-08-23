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

In order for this to work, Python must be installed and the packages listed in `doc/catechesis-manuals/requirements.txt` must be available in the environment where you run `make release`.

**We recomend that you create a _conda environment_ and install those Python packages in that environment, by running these commands in a shell located in the directory `doc/catechesis-manuals`:**

```bash
conda create --name sphinx python=3.9
conda activate sphinx
pip install -r requirements.txt
```

Then, before building a release package, initialise the conda environment on that shell:

```bash
conda activate sphinx
make release
```