CREATE TABLE `bookmarks_tmp` (
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
INSERT INTO bookmarks_tmp SELECT * FROM bookmarks;
DROP TABLE bookmarks;
ALTER TABLE bookmarks_tmp RENAME TO bookmarks;

CREATE TABLE `clients_tmp` (
	`cid`	TEXT NOT NULL UNIQUE,
	`cname`	TEXT,
	`ctype`	TEXT NOT NULL,
	`uid`	INTEGER NOT NULL,
	`lastseen`	TEXT NOT NULL,
	PRIMARY KEY(`cid`),
	FOREIGN KEY(`uid`) REFERENCES `users`(`userID`) ON DELETE CASCADE
);
INSERT INTO clients_tmp SELECT * FROM clients;
DROP TABLE clients;
ALTER TABLE clients_tmp RENAME TO clients;

CREATE TABLE `notifications_tmp` (
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
ALTER TABLE notifications RENAME COLUMN repeat TO client;
INSERT INTO notifications_tmp SELECT * FROM notifications;
DROP TABLE notifications;
ALTER TABLE notifications_tmp RENAME TO notifications;

CREATE INDEX `i2` ON `users` (
	`userID`
);

CREATE INDEX `i3` ON `clients` (
	`cid`
);

CREATE TRIGGER on_delete_set_default AFTER DELETE ON clients BEGIN
  UPDATE notifications SET client = 0 WHERE client = old.cid;
END;