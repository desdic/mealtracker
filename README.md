# Mealtracker

A free and open source mealtracker for tracking calories using PHP and MySQL/MariaDB.

## Table of Content
- [Goals](#goals)
- [Setup](#setup)
  - [Installing database schema](#install-required-database-schema)
  - [Setup the first user](#setup-the-first-user)
  - [Mealtypes](#mealtypes)
  - [Copy files](#copy-files)
  - [Change database credentials](#change-database-credentials)
- [Docker-compose](#docker-compose)
- [Acknowledgments](#acknowledgments)

## Goals

The main purpose of mealtracker is too count calories for loosing or gaining weight. It has a simple feature set like

- Simple interface.
- Mobile friendly interface.
- Share nutritional information with friends/family (Trusted people).
- Dish feature so a family can share a dish but only log a part of the dish.
- A local docker-compose setup is provided for testing or development.
- Basic graphs for nutrition and weight tracking.

## Setup

### Install required database schema
Create a database and add tables from [setup.sql](dbschema/setup.sql). File also contains `DROP` so be careful running it if you have added data.

### Setup the first user
Generate a password for the first user. This is an example but keep in mind the password is now in your shells history.

```sh
$ echo "<?php echo password_hash('mypass', PASSWORD_DEFAULT);?>"|php
$2y$12$KSFcXXKkLfoMS0znYio6TeO13MRaL3n6osrNVBpRde84YVrIWv48m
```
Then create the first user manually:

```sql
INSERT INTO user(username, firstname, lastname, checksum, isadmin) VALUES('myuser', 'MyFirstname', 'MyLastname', '$2y$12$KSFcXXKkLfoMS0znYio6TeO13MRaL3n6osrNVBpRde84YVrIWv48m', true);
```

### Mealtypes

Mealtypes are meals throughout the day. An example could be

```sql
INSERT INTO mealtypes(name, rank) VALUES('Breakfast', 1);
INSERT INTO mealtypes(name, rank) VALUES('Snack#1', 2);
INSERT INTO mealtypes(name, rank) VALUES('Lunch', 3);
INSERT INTO mealtypes(name, rank) VALUES('Snack#2', 4);
INSERT INTO mealtypes(name, rank) VALUES('Dinner', 5);
INSERT INTO mealtypes(name, rank) VALUES('Snack#3', 6);
```

### Copy files

All files/directory in `mealtracker` should be copied to the directory you want it installed.

### Change database credentials

`db.php` contains default credentials and should be changed (And don't use the default ones online). Default credentials is used by the docker container

## Docker-compose

If you want to test it out you can use docker-compose

```sh
$ mkdir var_mysql
$ make up
```

It will be available on [localhost:8080](http://localhost:8080) and MariaDB will be available on port 3306.

## Acknowledgments

This project benefit and use

- [PHP](https://www.php.net/)
- [MariaDB](https://mariadb.org/)
- [bootstrap](https://getbootstrap.com/)
- [chart.js](https://www.chartjs.org/)
- [jquery](https://jquery.com/)
