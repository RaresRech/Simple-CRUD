<?php

class CRUD
{
    private $pdo;

    /**
     * Constructor to establish a database connection.
     *
     * @param string $host
     * @param string $username
     * @param string $password
     * @param string $database
     */
    public function __construct($host, $username, $password, $database)
    {
        try {
            $this->pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Execute a prepared query with optional parameters.
     *
     * @param string $query
     * @param array $params
     * @return PDOStatement
     * @throws RuntimeException
     */
    public function executeQuery($query, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            // Log or throw custom exception
            throw new RuntimeException("Query execution failed: " . $e->getMessage());
        }
    }

    /**
     * Begin a database transaction.
     */
    public function beginTransaction()
    {
        $this->pdo->beginTransaction();
    }

    /**
     * Commit the database transaction.
     */
    public function commit()
    {
        $this->pdo->commit();
    }

    /**
     * Rollback the database transaction.
     */
    public function rollback()
    {
        $this->pdo->rollBack();
    }

    /**
     * Create a new record in the database.
     *
     * @param string $table
     * @param array $data
     * @return PDOStatement
     */
    public function create($table, $data)
    {
        // Validate $data here...

        $keys = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $query = "INSERT INTO $table ($keys) VALUES ($placeholders)";
        return $this->executeQuery($query, $data);
    }

    /**
     * Read records from the database based on optional conditions.
     *
     * @param string $table
     * @param string $condition
     * @param bool $fetch
     * @return array|PDOStatement
     */
    public function read($table, $condition = "", $fetch = true)
    {
        $query = "SELECT * FROM $table";
        if (!empty($condition)) {
            $query .= " WHERE $condition";
        }

        $stmt = $this->executeQuery($query);

        if ($fetch) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return $stmt;
        }
    }

    /**
     * Update records in the database based on a given condition.
     *
     * @param string $table
     * @param array $data
     * @param string $condition
     * @return PDOStatement
     */
    public function update($table, $data, $condition)
    {
        $setClause = [];
        $params = [];
        foreach ($data as $key => $value) {
            $setClause[] = "$key = :$key";
            $params[":$key"] = $value;
        }
        $setClause = implode(', ', $setClause);

        $query = "UPDATE $table SET $setClause WHERE $condition";

        return $this->executeQuery($query, $params);
    }

    /**
     * Delete records from the database based on a given condition.
     *
     * @param string $table
     * @param string $condition
     * @return PDOStatement
     */
    public function delete($table, $condition)
    {
        $query = "DELETE FROM $table WHERE $condition";
        return $this->executeQuery($query, ['condition' => $condition]);
    }

    /**
     * Soft delete records by marking them as deleted in the database.
     *
     * @param string $table
     * @param string $condition
     * @return PDOStatement
     */
    public function softDelete($table, $condition)
    {
        $query = "UPDATE $table SET deleted_at = NOW() WHERE $condition";
        return $this->executeQuery($query);
    }

    /**
     * Read records from the database with soft delete support.
     *
     * @param string $table
     * @param string $condition
     * @param bool $fetch
     * @return array|PDOStatement
     */
    public function readWithSoftDelete($table, $condition = "", $fetch = true)
    {
        $query = "SELECT * FROM $table WHERE deleted_at IS NULL";
        if (!empty($condition)) {
            $query .= " AND $condition";
        }

        $stmt = $this->executeQuery($query);

        if ($fetch) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return $stmt;
        }
    }

    /**
     * Read records from the database with pagination support.
     *
     * @param string $table
     * @param string $condition
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function readWithPagination($table, $condition = "", $page = 1, $perPage = 10)
    {
        $offset = ($page - 1) * $perPage;
        $query = "SELECT * FROM $table";
        if (!empty($condition)) {
            $query .= " WHERE $condition";
        }
        $query .= " LIMIT $perPage OFFSET $offset";

        return $this->executeQuery($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Close the database connection.
     */
    public function close()
    {
        $this->pdo = null;
    }
}
