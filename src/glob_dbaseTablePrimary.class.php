<?php



/**
 * @global      string  DB_NAME
 * @property    int     $id
 * @property    string  $timeCreated
 * @property    string  $timeEdited
 */
class glob_dbaseTablePrimary {

    /**
     * @static
     * @global PDO $pdo
     * @global string DB_NAME
     * @return array|false
     */
    public static function db_getTablesFull() {

        global $pdo;

        $stmt = $pdo->prepare( 'SELECT table_name, table_comment FROM information_schema.tables WHERE table_schema = ? ORDER BY table_name ASC' );

        $stmt->execute([ DB_NAME ]);

        return $stmt->fetchAll( PDO::FETCH_NUM );

    }

    /**
     * @static
     * @global PDO $pdo
     * @param string $searchTerm
     * @param int|null $limit
     * @return array|false
     */
    public static function db_search( $searchTerm, $limit = null ) {

        global $pdo;

        $conditions         = [];
        $searchValues       = [];
        $calledClass        = get_called_class();
        $publicProperties   = get_class_vars( $calledClass );

        $sql = "SELECT * FROM `" . $calledClass . "` WHERE ";

        foreach ( $publicProperties as $property => $value ) {

            $conditions[]       = "`" . $property . "` LIKE ?";
            $searchValues[]     = "%" . $searchTerm . "%";

        }

        $sql .= implode( ' OR ', $conditions );

        if ( $limit !== null ) {

            $sql .= ' LIMIT 0,' . $limit;

        }

        $stmt = $pdo->prepare( $sql );

        $stmt->execute( $searchValues );

        return $stmt->fetchAll( PDO::FETCH_CLASS, $calledClass );

    }

    /**
     * @static
     * @global PDO $pdo
     * @global string DB_NAME
     * @param string $table
     * @return array|false
     */
    public static function db_getTableColumns( $table ) {

        global $pdo;

        $stmt = $pdo->prepare( 'SELECT COLUMN_NAME, COLUMN_TYPE, COLUMN_COMMENT, IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?' );

        $stmt->execute([ DB_NAME, $table ]);

        return $stmt->fetchAll( PDO::FETCH_ASSOC );

    }

    /**
     * @static
     * @global PDO $pdo
     * @global string DB_NAME
     * @param string $table
     * @return string|null
     */
    public static function db_getTableComment( $table ) {

        global $pdo;

        $stmt = $pdo->prepare( 'SELECT TABLE_COMMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?' );

        $stmt->execute([ DB_NAME, $table ]);

        $response = $stmt->fetchAll( PDO::FETCH_ASSOC );

        if ( $response[ 0 ][ 'TABLE_COMMENT' ] === '' ) {

            return null;

        }

        return $response[ 0 ][ 'TABLE_COMMENT' ];

    }

    /**
     * @static
     * @global PDO $pdo
     * @param string $table
     * @param string $comment
     * @return boolean
     */
    public static function db_updateTableComment( $table, $comment ) {

        global $pdo;

        $stmt = $pdo->prepare( 'ALTER TABLE ' . $table . ' COMMENT = "' . $comment . '"' );

        return $stmt->execute();

    }

    /**
     * @static
     * @global PDO $pdo
     * @param string $table
     * @param string $column
     * @param string $comment
     * @return boolean
     */
    public static function db_updateColumnComment( $table, $column, $comment ) {

        global $pdo;

        $stmt = $pdo->prepare("
            SELECT COLUMN_TYPE 
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = :database 
                AND TABLE_NAME = :table 
                AND COLUMN_NAME = :column
        ");

        $response = $stmt->execute([
            'database' => DB_NAME,
            'table' => $table,
            'column' => $column
        ]);

        if ( $response === false ) {

            return false;

        }

        $response = $stmt->fetch( PDO::FETCH_ASSOC );

        if ( $response === false ) {

            return false;

        }

        $columnType = $response[ 'COLUMN_TYPE' ];

        $stmt = $pdo->prepare("ALTER TABLE $table MODIFY COLUMN $column $columnType COMMENT '$comment'");

        return $stmt->execute();

    }

    /**
     * @static
     * @global PDO $pdo
     * @param string $table
     * @param string $id
     * @param string $name
     * @param string $value
     * @return boolean
     */
    public static function db_updateCell( $table, $id, $name, $value ) {

        global $pdo;

        $stmt = $pdo->prepare( "UPDATE " . $table . " SET " . $name . " = ? WHERE id = ?" );

        return $stmt->execute([ $value, $id ]);

    }

    /**
     * @static
     * @global PDO $pdo
     * @global string DB_NAME
     * @param string $table
     * @param int $id
     * @return boolean
     */
    public static function db_getRowById( $table, $id ) {

        global $pdo;

        $stmt = $pdo->prepare( "SELECT COLUMN_NAME, COLUMN_TYPE, COLUMN_COMMENT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?");

        $stmt->execute([ DB_NAME, $table ]);

        $columns = $stmt->fetchAll( PDO::FETCH_ASSOC );

        if ( $columns === false || is_array( $columns ) === false ) {

            return false;

        }

        $stmt = $pdo->prepare( "SELECT * FROM $table WHERE id = ?" );

        $stmt->execute([ $id ]);

        $row = $stmt->fetch( PDO::FETCH_ASSOC ); 

        if ( $row === false || is_array( $row ) === false ) {

            return false;

        }

        if ( count( $columns ) !== count( $row ) ) {

            return false;

        }

        for ( $i = 0 ; $i < count( $columns ) ; $i++ ) {

            $columns[ $i ][ 'VALUE' ] = $row[ $columns[ $i ][ 'COLUMN_NAME' ] ];

        }

        return $columns;

    }

    /**
     * @static
     * @global PDO $pdo
     * @param string $table
     * @param int $id
     * @return boolean
     */
    public static function db_deleteTableRow( $table, $id ) {

        global $pdo;

        $stmt = $pdo->prepare(
            'DELETE
            FROM ' . $table . '
            WHERE id = ?
        ');

        return $stmt->execute([ $id ]);

    }

    /**
     * @static
     * @global PDO $pdo
     * @return int
     */
    public static function db_countAll() {

        global $pdo;

        $query = 'SELECT COUNT(*) AS count FROM ' . get_called_class();

        $stmt = $pdo->prepare( $query );

        $stmt->execute();

        $raw = $stmt->fetchAll();

        return intval( $raw[ 0 ][ 'count' ] );

    }

    /**
     * @static
     * @global PDO $pdo
     * @param string $whereColumn
     * @param string $whereValue
     * @return int
     */
    public static function db_countWhere( $whereColumn, $whereValue ) {

        global $pdo;

        $query = 'SELECT COUNT(*) AS count FROM ' . get_called_class() . ' WHERE `' . $whereColumn . '` = ?';

        $stmt = $pdo->prepare( $query );

        $stmt->execute([ $whereValue ]);

        $raw = $stmt->fetchAll();

        return intval( $raw[ 0 ][ 'count' ] );

    }

    /**
     * @static
     * @global PDO $pdo
     * @param string $whereColumn
     * @param string $whereValue
     * @param string|null $orderBy
     * @param string|'ASC' $orderDirection
     * @param int|null $limit
     * @return object[]|false
     */
    public static function db_getAllWhere( $whereColumn, $whereValue, $orderBy = null, $orderDirection = 'ASC', $limit = null ) {

        global $pdo;

        $query = 'SELECT * FROM ' . get_called_class() . ' WHERE `' . $whereColumn . '` = ?';

        if ( $orderBy !== null ) {

            $query .= ' ORDER BY `' . $orderBy . '` ' . $orderDirection;

        }

        if ( $limit !== null ) {

            $query .= ' LIMIT 0,' . $limit;

        }

        $stmt = $pdo->prepare( $query );

        $stmt->execute([ $whereValue ]);

        return $stmt->fetchAll( PDO::FETCH_CLASS, get_called_class() );

    }

    /**
     * @static
     * @global PDO $pdo
     * @param string $whereColumn
     * @param string $whereValue
     * @return object|false
     */
    public static function db_getSingleWhere( $whereColumn, $whereValue ) {

        global $pdo;

        $query = 'SELECT * FROM ' . get_called_class() . ' WHERE `' . $whereColumn . '` = ?';

        $stmt = $pdo->prepare( $query );

        $stmt->execute([ $whereValue ]);

        $raw = $stmt->fetchAll( PDO::FETCH_CLASS, get_called_class() );

        if ( count( $raw ) === 1 ) {

            return $raw[ 0 ];

        } else {

            return false;

        }

    }

    /**
     * @static
     * @global PDO $pdo
     * @param int|null $limit
     * @param string|null $orderBy
     * @param string|'ASC' $orderDirection
     * @return object[]|false
     */
    public static function db_getAll( $limit = null, $orderBy = null, $orderDirection = 'ASC' ) {

        global $pdo;

        $query = 'SELECT * FROM ' . get_called_class();

        if ( $orderBy !== null ) {

            $query .= ' ORDER BY `' . $orderBy . '` ' . $orderDirection;

        }

        if ( $limit !== null ) {

            $query .= ' LIMIT 0,' . $limit;

        }

        $stmt = $pdo->prepare( $query );

        $stmt->execute();

        return $stmt->fetchAll( PDO::FETCH_CLASS, get_called_class() );

    }

    /**
     * @static
     * @global PDO $pdo
     * @param string $id
     * @return object|false
     */
    public static function db_get( $id ) {

        global $pdo;

        $stmt = $pdo->prepare(
            'SELECT *
            FROM ' . get_called_class() . '
            WHERE id = ?'
        );

        $stmt->execute([ $id ]);

        $raw = $stmt->fetchAll( PDO::FETCH_CLASS, get_called_class() );

        if ( count( $raw ) === 1 ) {

            return $raw[ 0 ];

        } else {

            return false;

        }

    }

    /**
     * @global PDO $pdo
     * @return object
     */
    public function db_insert() {

        global $pdo;

        if ( property_exists( $this, 'timeCreated' ) ) {

            $this->timeCreated = ( new DateTime() )->format( 'Y-m-d H:i:s' );

        }

        if ( property_exists( $this, 'timeEdited' ) ) {

            $this->timeEdited = $this->timeCreated;

        }

        $publicProperties = call_user_func( 'get_object_vars', $this );

        $intoParts = [];
        $valueParts = [];

        foreach( $publicProperties as $propertyName => $propertyValue ) {

            $intoParts[] = '`' . $propertyName . '`';
            $valueParts[] = ':' . $propertyName;

        }

        $prepareStatementText = 'INSERT INTO ' . get_class( $this ) . ' ( ' . implode( ', ', $intoParts ) . ' ) VALUES ( ' . implode( ', ', $valueParts ) . ' )';

        $stmt = $pdo->prepare( $prepareStatementText );

        $stmt->execute( $publicProperties );

        $this->id = $pdo->lastInsertId();

        return $this;

    }

    /**
     * @global PDO $pdo
     * @return void
     */
    public function db_updateAll() {

        global $pdo;

        if ( property_exists( $this, 'timeEdited' ) ) {

            $this->timeEdited = ( new DateTime() )->format( 'Y-m-d H:i:s' );

        }

        $publicProperties = call_user_func( 'get_object_vars', $this );

        $setParts = [];

        foreach( $publicProperties as $propertyName => $propertyValue ) {

            if ( $propertyName === 'id' ) {

                continue;

            }

            $setParts[] = '`' . $propertyName . '` = :' . $propertyName;

        }

        $prepareStatementText = 'UPDATE ' . get_class( $this ) . ' SET ' . implode( ', ', $setParts ) . ' WHERE `id` = :id';

        $stmt = $pdo->prepare( $prepareStatementText );

        $stmt->execute( $publicProperties );

    }

    /**
     * @global PDO $pdo
     * @return void
     */
    public function db_delete() {

        global $pdo;

        $stmt = $pdo->prepare(
            'DELETE
            FROM ' . get_called_class() . '
            WHERE id = ?
        ');

        $stmt->execute([ $this->id ]);

    }

    /**
     * @return  void
     */
    public function set_timeCreated() {

        $this->timeCreated = ( new DateTime() )->format( 'Y-m-d H:i:s' );

    }

    /**
     * @return  void
     */
    public function set_timeEdited() {

        $this->timeEdited = ( new DateTime() )->format( 'Y-m-d H:i:s' );

    }

    /**
     * @return array
     */
    public function getPublicData() {

        $reflection         = new ReflectionClass( $this );
        $properties         = $reflection->getProperties( ReflectionProperty::IS_PUBLIC );
        $publicProperties   = array();

        foreach ( $properties as $property ) {

            $propertyName = $property->getName();

            $publicProperties[ $propertyName ] = $this->$propertyName;

        }

        unset( $publicProperties[ 'id' ] );

        return $publicProperties;

    }

}