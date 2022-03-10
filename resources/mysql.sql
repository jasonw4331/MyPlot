-- #!mysql
-- #{myplot
-- #  {init
-- #    {plots
CREATE TABLE IF NOT EXISTS plotsV2
(
    level   TEXT,
    X       INT,
    Z       INT,
    name    TEXT,
    owner   TEXT,
    helpers TEXT,
    denied  TEXT,
    biome   TEXT,
    pvp     INT,
    price   FLOAT,
    PRIMARY KEY (level, X, Z)
);
-- #    }
-- #    {mergedPlots
CREATE TABLE IF NOT EXISTS mergedPlotsV2
(
    level   TEXT,
    originX INT,
    originZ INT,
    mergedX INT,
    mergedZ INT,
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
INSERT
INTO plotsV2 (level, X, Z, name, owner, helpers, denied, biome, pvp, price)
VALUES (:level, :X, :Z, :name, :owner, :helpers, :denied, :biome, :pvp, :price)
ON DUPLICATE KEY UPDATE name    = VALUES(:name),
                        owner   = VALUES(:owner),
                        helpers = VALUES(:helpers),
                        denied  = VALUES(:denied),
                        biome   = VALUES(:biome),
                        pvp     = VALUES(:pvp),
                        price   = VALUES(:price);
-- #    }
-- #    {merge
-- #      :level string
-- #      :originX int
-- #      :originZ int
-- #      :mergedX int
-- #      :mergedZ int
INSERT IGNORE
INTO mergedPlotsV2 (`level`, `originX`, `originZ`, `mergedX`, `mergedZ`)
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
  AND X = :x
  AND Z = :z;
-- #      }
-- #    }
-- #    {all-plots
-- #      {by-owner
-- #        :owner string
SELECT level, X, Z
FROM plotsV2
WHERE owner = :owner;
-- #      }
-- #    }
-- #    {all-plots
-- #      {by-owner-and-level
-- #        :owner string
-- #        :level string
SELECT level, X, Z
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
         LEFT JOIN mergedPlotsV2
                   ON mergedPlotsV2.level = plotsV2.level
                       AND mergedX = X
                       AND mergedZ = Z
WHERE mergedPlotsV2.level = :level
  AND originX = :originX
  AND originZ = :originZ;
-- #      }
-- #    }
-- #    {merge-plots
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
         LEFT JOIN mergedPlotsV2
                   ON mergedPlotsV2.level = plotsV2.level
                       AND mergedX = X
                       AND mergedZ = Z
WHERE mergedPlotsV2.level = :level
  AND originX = (
    SELECT originX
    FROM mergedPlotsV2
    WHERE mergedX = :mergedX
      AND mergedZ = :mergedZ
      AND mergedPlotsV2.level = :level
)
  AND originZ = (
    SELECT originZ
    FROM mergedPlotsV2
    WHERE mergedX = :mergedX
      AND mergedZ = :mergedZ
      AND mergedPlotsV2.level = :level
);
-- #      }
-- #    }
-- #  }
-- #  {remove
-- #    {plot
-- #      {by-xz
-- #        :level string
-- #        :x int
-- #        :z int
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
-- #        :x int
-- #        :z int
-- #        :biome string
-- #        :pvp bool false
-- #        :price int
UPDATE plotsV2
SET name    = '',
    owner   = '',
    helpers = '',
    denied  = '',
    pvp     = :pvp,
    price   = :price
WHERE level = :level
  AND X = :x
  AND Z = :z;
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