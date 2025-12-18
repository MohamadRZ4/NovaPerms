-- #!sqlite

-- #{ init
	-- #{ users
		CREATE TABLE IF NOT EXISTS Users
		(
			username       VARCHAR(32) PRIMARY KEY NOT NULL,
			permissions    TEXT        DEFAULT '[]'
		);
	-- #}
	-- #{ groups
		CREATE TABLE IF NOT EXISTS Groups
		(
			name        VARCHAR(32) PRIMARY KEY NOT NULL,
			permissions TEXT        DEFAULT '[]'
		);
	-- #}
-- #}

-- #{ data
	-- #{ users
		-- #{ add
			-- # :username string
			-- # :permissions string "[]"
			INSERT OR IGNORE INTO Users(username, permissions)
			VALUES (:username, :permissions);
		-- #}
		-- #{ get
			-- # :username string
			SELECT * FROM Users WHERE username = :username;
		-- #}
        -- #{ getOrCreate
            -- # :username string
            -- # :permissions string "[]"
            INSERT OR IGNORE INTO Users(username, permissions) VALUES (:username, :permissions);
            SELECT * FROM Users WHERE username = :username;
        -- #}
		-- #{ set
			-- # :username string
			-- # :permissions string "[]"
			INSERT OR REPLACE INTO Users(username, permissions)
			VALUES (:username, :permissions);
		-- #}
		-- #{ getAll
			SELECT * FROM Users;
		-- #}
		-- #{ setPermissions
			-- # :username string
			-- # :permissions string
			INSERT INTO Users(username, permissions)
			VALUES(:username, :permissions)
			ON CONFLICT(username) DO UPDATE SET permissions = :permissions;
		-- #}
		-- #{ delete
			-- # :username string
			DELETE FROM Users WHERE username = :username;
		-- #}
	-- #}

	-- #{ groups
		-- #{ add
			-- # :name string
			-- # :permissions string "[]"
			INSERT OR IGNORE INTO Groups(name, permissions)
			VALUES (:name, :permissions);
		-- #}
		-- #{ get
			-- # :name string
			SELECT * FROM Groups WHERE name = :name;
		-- #}
		-- #{ set
			-- # :name string
			-- # :permissions string "[]"
			INSERT OR REPLACE INTO Groups(name, permissions)
			VALUES (:name, :permissions);
		-- #}
		-- #{ getAll
			SELECT * FROM Groups;
		-- #}
		-- #{ setPermissions
			-- # :name string
			-- # :permissions string
			INSERT INTO Groups(name, permissions)
			VALUES(:name, :permissions)
			ON CONFLICT(name) DO UPDATE SET permissions = :permissions;
		-- #}
		-- #{ delete
			-- # :name string
			DELETE FROM Groups WHERE name = :name;
		-- #}
	-- #}
-- #}

