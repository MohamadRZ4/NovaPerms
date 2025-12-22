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