# DataBase Version Control

DBVC will manage your application migration/rollback SQL files.

## Installation

You first need to create 2 versioned directories in your project, name them "patches" and "tags".

    git clone https://github.com/jibriss/dbvc.git
    cd dbvc
    composer install
    mv config.php.example config.php
    vim config.php
    ./dbvc

## Getting started

John is part of the development team of the happy-project. He's developing a feature that requires a database schema migration. He has to write 2 SQL file for migration, and rollback.

    john@laptop:~/workspace$ git checkout -b awesome-feature
    john@laptop:~/workspace$ vim hack/some/code
    john@laptop:~/workspace$ vim sql/patches/awesome-feature-migration.sql
    john@laptop:~/workspace$ vim sql/patches/awesome-feature-rollback.sql
    john@laptop:~/workspace$ dbvc patch:migrate awesome-feature

Jane is also working on the happy-projet, she's develops an other feature that also requires database migration. She write 2 SQL files, like John, in her branch.

    jane@laptop:~/workspace$ git checkout -b uber-feature
    jane@laptop:~/workspace$ vim code/some/hack
    jane@laptop:~/workspace$ vim sql/patches/uber-feature-migration.sql
    jane@laptop:~/workspace$ vim sql/patches/uber-feature-rollback.sql
    jane@laptop:~/workspace$ dbvc patch:migrate uber-feature

Now she wants to help john on his feature. She switch on his branch

    jane@laptop:~/workspace$ git checkout awsome-feature
    jane@laptop:~/workspace$ dbvc update

``dbvc update`` will update her database by rollbacking her uber-feature patch, then applying john's

When all the hack are finished, you can release a new version of the application.

    jane@laptop:~/workspace$ git checkout master
    jane@laptop:~/workspace$ git merge awesome-feature
    jane@laptop:~/workspace$ git merge uber-feature
    jane@laptop:~/workspace$ dbvc update

Now the master branch contain both patch files. After some testing, she can create a new tag. ``dbvc tag:create`` will remove all the patches and merge them into a single tag SQL file

    jane@laptop:~/workspace$ dbvc tag:create
    jane@laptop:~/workspace$ git add -A
    jane@laptop:~/workspace$ git commit -m "Database migration"
    jane@laptop:~/workspace$ git tag -a v1.1.0 'version 1.1'
