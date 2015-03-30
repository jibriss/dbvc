# DataBase Version Control

**This project is still in development**

DBVC is a database schema migration tool.


## What DBVC will do for you

- Track what SQL scripts have been applied in your DB
- Rollback a SQL script even if it's no longer on disk
- Check if a SQL script has been changed on disk after being applied in DB


## What DBVC *won't* do for you

- Write your SQL scripts
- Ensure migration and rollback are consistent
- Ensure you haven't manually changed your database schema


## Installation

    cd /opt
    sudo mkdir dbvc
    sudo chown $USER:$USER dbvc
    cd dbvc
    git clone git@github.com:jibriss/dbvc.git .
    composer install
    sudo ln -s /opt/dbvc/dbvc /usr/local/bin/dbvc

There is no tag yet, just run ``git pull`` to update to the last version


## Getting started

DBVC introduces a simple workflow to handle your database migrations :

- **Tags** are stable version on your database. Each time you release a new version of your application which requires
database migration, you should create a new tag.
- **Patches** are SQL migration script of features still in development, each branch can contain a patch. Once all
feature are merged into master/trunk, you can create a new tag from the different patches. Unlike tags, patch files
can change over time.


### Project initialization

First create 2 directories in your project folder structure. 1 for patches and 1 for tags.

Then go to your project root and run ``dbvc init``. This command will create an empty configuration file ``dbvc.xml``.
Edit it to your needs and you'll be ready.


### Creating a new patch

To create a new patch, you have to manually add 2 files into your patch folder :

- awesome-feature-migration.sql
- awesome-feature-rollback.sql

DBVC will automatically recognize this new patch :

    $ dbvc status
    DBVC version beta

    Patch name        On disk   In DB
    awesome-feature   Yes       No

From here, you can apply this patch to your database by running ``dbvc patch:migrate awesome-feature``. After moving to
an other branch, you may need to rollback this patch with the command ``dbvc patch:rollback awesome-feature``

If the patch file changed after you applied it to your database, DBVC will detect it :

    $ dbvc status
    DBVC version beta

    Patch name        On disk   In DB
    awesome-feature   Changed   Yes

You can fix this with ``dbvc patch:migrate awesome-feature``


### Creating a new tag

Once few branches have been merged in master, you may need to release a new version of your application. This is a good
time to create a new tag. Run ``dbvc tag:create`` to merge all patches into a tag.

    $ dbvc status
    DBVC version beta

    Patch name        On disk   In DB
    awesome-feature   Yes       Yes
    nice-improvement  Yes       Yes

    Tag name   On disk   In DB
    1          Yes       Yes

    $ dbvc tag:create
    [...]

    $ dbvc status
    DBVC version beta

    Tag name   On disk   In DB
    1          Yes       Yes
    2          Yes       Yes

A new file ``2-migration.sql`` will appear in the tags directory.

If a developer is on tag 1, he can update his database to the new version by running ``dbvc tag:migrate``


### All in once

Let's make it easier, the magic ``dbvc update`` command will update your database will all patches and tags available on the filesystem.

