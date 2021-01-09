-- Create users table
CREATE TABLE "users" (
	`userID`	INTEGER NOT NULL UNIQUE,
	`userName`	TEXT NOT NULL UNIQUE,
	`userType`	INTEGER NOT NULL,
	`userHash`	TEXT NOT NULL,
	`userLastLogin`	INT(11),
	`sessionID`	VARCHAR(255) UNIQUE,
	`userOldLogin`	INT(11),
	`uOptions`	TEXT,
	PRIMARY KEY(`userID`)
);

-- Create bookmark table
CREATE TABLE `bookmarks` (
	`bmID`	TEXT NOT NULL,
	`bmParentID`	TEXT NOT NULL,
	`bmIndex`	INTEGER NOT NULL,
	`bmTitle`	TEXT,
	`bmType`	TEXT NOT NULL,
	`bmURL`	TEXT,
	`bmAdded`	TEXT NOT NULL,
	`bmModified`	TEXT,
	`userID`	INTEGER NOT NULL,
	`bmAction`	INTEGER,
	PRIMARY KEY(`bmID`),
	FOREIGN KEY(`userID`) REFERENCES `users`(`userID`) ON DELETE CASCADE
);

-- Create clients table
CREATE TABLE `clients` (
	`cid`	TEXT NOT NULL UNIQUE,
	`cname`	TEXT,
	`ctype`	TEXT NOT NULL,
	`uid`	INTEGER NOT NULL,
	`lastseen`	TEXT NOT NULL,
	PRIMARY KEY(`cid`),
	FOREIGN KEY(`uid`) REFERENCES `users`(`userID`) ON DELETE CASCADE
);

-- Create notifications table
CREATE TABLE `notifications` (
	`id`	INTEGER NOT NULL,
	`title`	varchar(250) NOT NULL,
	`message`	TEXT NOT NULL,
	`ntime`	varchar(250) NOT NULL DEFAULT NULL,
	`client`	TEXT NOT NULL DEFAULT 0,
	`nloop`	INTEGER NOT NULL DEFAULT 1,
	`publish_date`	varchar(250) NOT NULL,
	`userID`	INTEGER NOT NULL,
	PRIMARY KEY(`id`),
	FOREIGN KEY(`userID`) REFERENCES `users`(`userID`) ON DELETE CASCADE
);

-- Create index
CREATE INDEX `i1` ON `bookmarks` (`bmURL`, `bmTitle`);
CREATE INDEX `i2` ON `users` ( `userID`);
CREATE INDEX `i3` ON `clients` (`cid`);

-- Create triggers
CREATE TRIGGER on_delete_set_default AFTER DELETE ON clients BEGIN
  UPDATE notifications SET client = 0 WHERE client = old.cid;
END;

PRAGMA user_version = 1;