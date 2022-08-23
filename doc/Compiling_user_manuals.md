# Compiling user manuals

## Checkout the git submodule

The user manuals live in a [separate repository](https://github.com/CatecheSis-org-pt/catechesis-user-manual). However, they are linked to in this repository through a [git submodule](https://git-scm.com/book/en/v2/Git-Tools-Submodules), so that any update to the user manuals are automatically included in new releases.
The contents of that submodule are checked-out into the directory `doc/catechesis-manuals` in this repository.

In order for this to work, though, you have to manually checkout the submodule.
In a shell located in the root folder of *this* (main CatecheSis) repository run: 

```bash
git submodule update --init --recursive
```

Do this every time you pull new code from the main repo, to keep the submodule in sync.


## Create a Python environment _[optional, highly recommended]_

In order to compile the user manuals, Python must be installed and the packages listed in `doc/catechesis-manuals/requirements.txt` must be available in the environment where you compile user manuals and/or CatecheSis releases.

**We recomend that you create a _conda environment_ and install those Python packages in that environment, by running these commands in a shell located in the directory `doc/catechesis-manuals`:**

```bash
conda create --name sphinx python=3.9
conda activate sphinx
pip install -r requirements.txt
```

Then, before compiling the user manuals and/or a CatecheSis release package, initialise the conda environment on that shell:

```bash
conda activate sphinx
```


## Compiling user manuals

Open a shell in the root folder of *this* (main CatecheSis) repository.

Make sure the Python dependencies are installed, or the correct environment is activated (_see previous section_).

Run the script:

```bash
./tools/compile-user-manuals.sh
```

This will compile the user manuals and also copy the built artifacts into `src/help`, so that they can be accessed through a browser when you run the application in your development machine.