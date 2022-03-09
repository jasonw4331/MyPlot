-- #!mysql
-- #{myplot
-- #  {init
-- #    {plots
CREATE TABLE IF NOT EXISTS plotsV2
(
    level   TEXT,
    X       INTEGER,
    Z       INTEGER,
    name    TEXT,
    owner   TEXT,
    helpers TEXT,
    denied  TEXT,
    biome   TEXT,
    pvp     INTEGER,
    price   FLOAT,
    PRIMARY KEY (level, X, Z)
);
-- #    }
-- #    {mergedPlots
CREATE TABLE IF NOT EXISTS mergedPlotsV2
(
    level   TEXT,
    originX INTEGER,
    originZ INTEGER,
    mergedX INTEGER,
    mergedZ INTEGER,
    PRIMARY KEY (level, originX, originZ, mergedX, mergedZ)
);
-- #    }
-- #  }
-- #  {add
-- #    {plot
-- #      :level string
-- #      :X int
-- #      :Z int
-- #      :name string
-- #      :owner string
-- #      :helpers string
-- #      :denied string
-- #      :biome string
-- #      :pvp bool false
-- #      :price int
INSERT OR
REPLACE
INTO plotsV2 (level, X, Z, name, owner, helpers, denied, biome, pvp, price)
VALUES (:level, :X, :Z, :name, :owner, :helpers, :denied, :biome, :pvp, :price);
-- #    }
-- #    {merge
-- #      :level string
-- #      :originX int
-- #      :originZ int
-- #      :mergedX int
-- #      :mergedZ int
INSERT OR
REPLACE
INTO mergedPlotsV2 (level, originX, originZ, mergedX, mergedZ)
VALUES (:level, :originX, :originZ, :mergedX, :mergedZ);
-- #    }
-- #  }
-- #  {get
-- #    {plot
-- #      {by-xz
-- #        :level string
-- #        :X int
-- #        :Z int
SELECT name, owner, helpers, denied, biome, pvp, price
FROM plotsV2
WHERE level = :level
  AND X = :X
  AND Z = :Z;
-- #      }
-- #    }
-- #    {all-plots
-- #      {by-owner
-- #        :owner string
SELECT *
FROM plotsV2
WHERE owner = :owner;
-- #      }
-- #    }
-- #    {all-plots
-- #      {by-owner-and-level
-- #        :owner string
-- #        :level string
SELECT *
FROM plotsV2
WHERE owner = :owner
  AND level = :level;
-- #      }
-- #    }
-- #    {highest-existing
-- #      {by-interval
-- #        :level string
-- #        :number int
SELECT X, Z
FROM plotsV2
WHERE (level = :level AND ((abs(X) = :number AND abs(Z) <= :number) OR (abs(Z) = :number AND abs(X) <= :number)));
-- #      }
-- #    }
-- #    {merge-origin
-- #      {by-merged
-- #        :level string
-- #        :mergedX int
-- #        :mergedZ int
SELECT plotsV2.level,
       X,
       Z,
       name,
       owner,
       helpers,
       denied,
       biome,
       pvp,
       price
FROM plotsV2
         LEFT JOIN mergedPlotsV2 ON mergedPlotsV2.level = plotsV2.level
WHERE mergedPlotsV2.level = :level
  AND mergedX = :mergedX
  AND mergedZ = :mergedZ;
-- #      }
-- #    }
-- #    {merge-plots
-- #      {by-origin
-- #        :level string
-- #        :originX int
-- #        :originZ int
SELECT plotsV2.level,
       X,
       Z,
       name,
       owner,
       helpers,
       denied,
       biome,
       pvp,
       price
FROM plotsV2
         LEFT JOIN mergedPlotsV2 ON mergedPlotsV2.level = plotsV2.level AND mergedPlotsV2.mergedX = plotsV2.X AND
                                    mergedPlotsV2.mergedZ = plotsV2.Z
WHERE mergedPlotsV2.level = :level
  AND originX = :originX
  AND originZ = :originZ;
-- #      }
-- #    }
-- #  }
-- #  {remove
-- #    {plot
-- #      {by-xz
-- #        :level string
-- #        :X int
-- #        :Z int
DELETE
FROM plotsV2
WHERE level = :level
  AND X = :X
  AND Z = :Z;
-- #      }
-- #    }
-- #    {merge
-- #      {by-xz
-- #        :level string
-- #        :X int
-- #        :Z int
-- #        :biome string
-- #        :pvp bool false
-- #        :price int
UPDATE plotsV2
SET name    = '',
    owner   = '',
    helpers = '',
    denied  = '',
    biome   = :biome,
    pvp     = :pvp,
    price   = :price
WHERE level = :level
  AND X = :X
  AND Z = :Z;
-- #      }
-- #    }
-- #    {merge-entry
-- #      :level string
-- #      :originX int
-- #      :originZ int
-- #      :mergedX int
-- #      :mergedZ int
DELETE
FROM mergedPlotsV2
WHERE level = :level
  AND originX = :originX
  AND originZ = :originZ
  AND mergedX = :mergedX
  AND mergedZ = :mergedZ;
-- #    }
-- #  }
-- #}