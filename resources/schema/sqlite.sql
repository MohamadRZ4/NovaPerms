-- #!sqlite

-- #{ init
-- #{ users
CREATE TABLE IF NOT EXISTS Users
(
    username      TEXT PRIMARY KEY COLLATE NOCASE,
    primary_group TEXT DEFAULT NULL
);
-- #}

-- #{ groups
CREATE TABLE IF NOT EXISTS Groups
(
    name VARCHAR(32) PRIMARY KEY NOT NULL
    );
-- #}

-- #{ user_permissions
CREATE TABLE IF NOT EXISTS UserPermissions
(
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    username   TEXT        NOT NULL,
    permission VARCHAR(200) NOT NULL,
    value      BOOLEAN     NOT NULL,
    expiry     BIGINT      NOT NULL,
    FOREIGN KEY (username) REFERENCES Users(username) ON DELETE CASCADE
    );
CREATE INDEX IF NOT EXISTS idx_user_permissions ON UserPermissions(username);
-- #}

-- #{ group_permissions
CREATE TABLE IF NOT EXISTS GroupPermissions
(
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    group_name VARCHAR(32)  NOT NULL,
    permission VARCHAR(200) NOT NULL,
    value      BOOLEAN     NOT NULL,
    expiry     BIGINT      NOT NULL,
    FOREIGN KEY (group_name) REFERENCES Groups(name) ON DELETE CASCADE
    );
CREATE INDEX IF NOT EXISTS idx_group_permissions ON GroupPermissions(group_name);
-- #}
-- #}

-- #{ data
-- #{ users
-- #{ add
-- # :username string
-- # :primary_group ?string
INSERT OR IGNORE INTO Users(username, primary_group)
            VALUES (:username, :primary_group);
        -- #}

        -- #{ get
            -- # :username string
SELECT u.*,
       up.permission, up.value, up.expiry
FROM Users u
         LEFT JOIN UserPermissions up ON u.username = up.username
WHERE u.username = :username;
-- #}

-- #{ set
-- # :username string
-- # :primary_group ?string
INSERT OR REPLACE INTO Users(username, primary_group)
            VALUES (:username, :primary_group);
        -- #}

        -- #{ setPrimaryGroup
            -- # :username string
            -- # :primary_group ?string
UPDATE Users SET primary_group = :primary_group
WHERE username = :username;
-- #}

-- #{ getAll
SELECT * FROM Users;
-- #}

-- #{ delete
-- # :username string
DELETE FROM Users WHERE username = :username;
-- #}
-- #}

-- #{ user_permissions
-- #{ deleteAll
-- # :username string
DELETE FROM UserPermissions WHERE username = :username;
-- #}

-- #{ add
-- # :username string
-- # :permission string
-- # :value bool
-- # :expiry int
INSERT INTO UserPermissions(username, permission, value, expiry)
VALUES (:username, :permission, :value, :expiry);
-- #}

-- #{ get
-- # :username string
SELECT * FROM UserPermissions WHERE username = :username;
-- #}
-- #}

-- #{ groups
-- #{ add
-- # :name string
INSERT OR IGNORE INTO Groups(name)
            VALUES (:name);
        -- #}

        -- #{ get
            -- # :name string
SELECT g.*,
       gp.permission, gp.value, gp.expiry
FROM Groups g
         LEFT JOIN GroupPermissions gp ON g.name = gp.group_name
WHERE g.name = :name;
-- #}

-- #{ getAll
SELECT g.*,
       gp.permission, gp.value, gp.expiry
FROM Groups g
         LEFT JOIN GroupPermissions gp ON g.name = gp.group_name;
-- #}

-- #{ delete
-- # :name string
DELETE FROM Groups WHERE name = :name;
-- #}
-- #}

-- #{ group_permissions
-- #{ deleteAll
-- # :group_name string
DELETE FROM GroupPermissions WHERE group_name = :group_name;
-- #}

-- #{ add
-- # :group_name string
-- # :permission string
-- # :value bool
-- # :expiry int
INSERT INTO GroupPermissions(group_name, permission, value, expiry)
VALUES (:group_name, :permission, :value, :expiry);
-- #}

-- #{ get
-- # :group_name string
SELECT * FROM GroupPermissions WHERE group_name = :group_name;
-- #}
-- #}
-- #}