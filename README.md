# silverstripe-travis-utility

Write a standardised `.travis.yml`

Will read the existing `.travis.yml` file to see if things like behat and npm need to be included in the new `.travis.yml` file

Assumes that you use the convention for git branches of `pulls/<version>/whatever` e.g. `pulls/2.6/travis`

## Installation
Add the following `.config` file inside this directory, update version numbers as required

```
pdo=1
phpMin=5.6
phpMax=7.4
recipeMinorMin=4.1
recipeMinorMax=4.6
recipeMajor=4
# The following is only required for development
# Use full user dir instead of ~
developmentDataDir=/Users/myuser/Modules
```

## Usage

Assuming that this program is installed in the users home directory `~`
```
cd ~/Modules/silverstripe-asset-admin
git checkout 2.6
git checkout -b pulls/2.6/travis
php ../../silverstripe-travis-utility/run.php
```

## Links
[Unify travis config across modules](https://github.com/silverstripe/silverstripe-framework/issues/9174)
