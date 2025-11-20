DROP TABLE IF EXISTS dishitems;
DROP TABLE IF EXISTS dish;
DROP TABLE IF EXISTS mealitems;
DROP TABLE IF EXISTS meals;
DROP TABLE IF EXISTS mealday;
DROP TABLE IF EXISTS mealtypes;
DROP TABLE IF EXISTS food;
DROP TABLE IF EXISTS weighttrack;
DROP TABLE IF EXISTS user_preferences;
DROP TABLE IF EXISTS user;

CREATE TABLE user (
	id INT NOT NULL AUTO_INCREMENT,
	username varchar(255) not null unique,
	firstname varchar(255) not null,
	lastname varchar(255) not null,
	checksum varchar(255) not null,
	disabled bool default false,
	isadmin bool default false,
	PRIMARY KEY(id)
) ENGINE=InnoDB;

CREATE TABLE food (
	id int NOT NULL AUTO_INCREMENT,
	addedby int not null,
	title VARCHAR(255) NOT NULL,
	kcal INT NOT NULL,
	protein FLOAT(6,2) NOT NULL,
	carbs FLOAT(6,2) NOT NULL,
	fat FLOAT(6,2) NOT NULL,
	unit int not null,
	dishid int default null,
	PRIMARY KEY(id),
	foreign key(addedby) references user(id)
) ENGINE=InnoDB;

create index food_title on food(title);

create table mealday (
	id int not null AUTO_INCREMENT,
	userid int not null,
	date DATE not null default NOW(),
	primary key(id),
	foreign key(userid) references user(id)
) ENGINE=InnoDB;

create table mealtypes(
	id int not null AUTO_INCREMENT,
	name varchar(255) not null unique,
	rank int not null,
	primary key(id)
);

CREATE TABLE mealitems(
	id INT NOT NULL AUTO_INCREMENT,
	amount FLOAT(6,2) NOT NULL,
	mealday INT NOT NULL,
	fooditem INT NOT NULL,
	mealtype INT NOT NULL,
	userid int not null,

	PRIMARY KEY(id),
	FOREIGN KEY(mealday) REFERENCES mealday(id),
	FOREIGN KEY(fooditem) REFERENCES food(id),
	foreign key(mealtype) REFERENCES mealtypes(id),
	foreign key(userid) references user(id)
) ENGINE=InnoDB;

CREATE TABLE dish(
	id int not null AUTO_INCREMENT,
	name varchar(255),
	created DATETIME DEFAULT CURRENT_TIMESTAMP,
	addedby int not null,

	kcal INT NOT NULL,
	protein FLOAT(6,2) NOT NULL,
	carbs FLOAT(6,2) NOT NULL,
	fat FLOAT(6,2) NOT NULL,
	amount FLOAT(6,2) not null,

	primary key(id),
	foreign key(addedby) references user(id)
);

CREATE TABLE dishitems(
	id int not null AUTO_INCREMENT,
	amount FLOAT(6,2) NOT NULL,
	dishid INT NOT NULL,
	fooditem INT NOT NULL,
	addedby int not null,
	primary key(id),
	FOREIGN KEY(fooditem) REFERENCES food(id),
	FOREIGN KEY(dishid) REFERENCES dish(id)  ON DELETE CASCADE,
	foreign key(addedby) references user(id)
);

CREATE TABLE weighttrack(
	id int not null AUTO_INCREMENT,
	created DATE not null default NOW(),
	weight FLOAT(6.2) not null,
	userid int not null,
	primary key (id),
	foreign key(userid) references user(id)
);

CREATE TABLE user_preferences (
    user_id INT NOT NULL,
    preference_key VARCHAR(50) NOT NULL,
    preference_value VARCHAR(255) NOT NULL,
    PRIMARY KEY(user_id, preference_key),
    FOREIGN KEY(user_id) REFERENCES user(id) ON DELETE CASCADE,
) ENGINE=InnoDB;

CREATE TABLE water_intake (
	id INT not null AUTO_INCREMENT,
	userid int not null,
    mealdayid INT NOT NULL,
    cups INT not null default 0,
    PRIMARY KEY(id),
    FOREIGN KEY(mealdayid) REFERENCES mealday(id) ON DELETE CASCADE,
    FOREIGN KEY(userid) REFERENCES user(id) ON DELETE CASCADE
) ENGINE=InnoDB;
