<?php
/**
 * Task Model
 *
 * Handles task and post CRUD operations with user association.
 * Tasks are linked to users via UserID foreign key.
 * Posts table is referenced by edit/delete/getTaskById methods (legacy/placeholder).
 *
 * @package    rest-api-mvc-php
 * @subpackage app/mvc/models
 * @author     rest-api-mvc-php team
 * @version    1.0
 */
class Task
{
    /** @var Database PDO connection */
    private $db;

    /**
     * Task constructor.
     *
     * Initializes database connection.
     */
    public function __construct()
    {
        $this->db = new Database;
    }

    /**
     * Add a new task.
     *
     * @param array $data {
     *     @type int    $user_id        - FK to users.id
     *     @type string $CompanyName    - Company name
     *     @type string $WorkName       - Work item name
     *     @type string $SegmentName    - Segment name
     *     @type int    $WorkCount      - Work count
     *     @type string $TaskStartTime  - Start time (DATETIME)
     *     @type string $TaskEndTime    - End time (DATETIME)
     *     @type string $FailureTask    - Failure notes
     *     @type string $Note           - Notes
     * }
     *
     * @return bool True on success, false on failure
     *
     * @flow docs/auth-flow.md section 1.login -> step 2
     */
    public function addTask($data)
    {
        // Prepare Query
        $this->db->query('INSERT INTO tasks (UserID,CompanyName,WorkName,SegmentName,WorkCount,TaskStartTime,TaskEndTime,FailureTask,Note) 
        VALUES (:user_id,:CompanyName,:WorkName,:SegmentName,:WorkCount,:TaskStartTime,:TaskEndTime,:FailureTask,:Note);');

        // Bind Values
        $this->db->bind(':user_id', $data['user_id']);
        $this->db->bind(':CompanyName', $data['CompanyName']);
        $this->db->bind(':WorkName', $data['WorkName']);
        $this->db->bind(':SegmentName', $data['SegmentName']);
        $this->db->bind(':WorkCount', $data['WorkCount']);
        $this->db->bind(':TaskStartTime', $data['TaskStartTime']);
        $this->db->bind(':TaskEndTime', $data['TaskEndTime']);
        $this->db->bind(':FailureTask', $data['FailureTask']);
        $this->db->bind(':Note', $data['Note']);
        //Execute
        if ($this->db->execute()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Update a post (legacy/placeholder).
     *
     * @param array $data {
     *     @type int    $id     - Post ID
     *     @type string $title  - Post title
     *     @type string $body   - Post content
     * }
     *
     * @return bool True on success, false on failure
     *
     * @note This operates on `posts` table (legacy). Task model uses tasks table for addTask/deleteTask/getTasks.
     */
    public function updateTask($data)
    {
        // Prepare Query
        $this->db->query('UPDATE posts SET title = :title, body = :body WHERE id = :id');

        // Bind Values
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':title', $data['title']);
        $this->db->bind(':body', $data['body']);

        //Execute
        if ($this->db->execute()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Delete a task by ID.
     *
     * @param int $id Task primary key
     *
     * @return bool True on success, false on failure
     *
     * @flow docs/auth-flow.md section 1.login -> step 5
     */
    public function deleteTask($id)
    {
        // Prepare Query
        $this->db->query('DELETE FROM tasks WHERE id = :id');

        // Bind Values
        $this->db->bind(':id', $id);

        //Execute
        if ($this->db->execute()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get all tasks with associated user data.
     *
     * @return array|false Array of task rows with userId, or false on failure
     *
     * @flow docs/auth-flow.md section 1.login -> step 6
     */
    public function getTasks()
    {
        $this->db->query("SELECT *, 
                        tasks.id as TaskId, 
                        users.id as userId
                        FROM tasks 
                        INNER JOIN users 
                        ON tasks.UserID = users.id
                        ORDER BY tasks.created_at DESC;");

        $results = $this->db->resultset();

        return $results;
    }

    /**
     * Get a post by ID (legacy/placeholder).
     *
     * @param int $id Post primary key
     *
     * @return array|false Post row, or false if not found
     *
     * @note Uses posts table (legacy artifact). See schema.md for posts table definition.
     */
    public function getTaskById($id)
    {
        $this->db->query("SELECT * FROM posts WHERE id = :id");

        $this->db->bind(':id', $id);

        $row = $this->db->single();

        return $row;
    }

}